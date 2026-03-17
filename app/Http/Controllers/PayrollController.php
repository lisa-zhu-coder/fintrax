<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Part\TextPart;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payroll.view')->only(['view', 'pendingSend']);
        $this->middleware('permission:payroll.send')->only(['sendBulk']);
        $this->middleware('permission:payroll.create')->only(['assignEmployee', 'pendingAssignEmployee']);
        $this->middleware('permission:payroll.delete')->only(['destroy', 'cancelPending', 'pendingRemove']);
    }

    public function pendingAssignEmployee(Request $request)
    {
        $request->validate(['index' => 'required|integer|min:0', 'employee_id' => 'required|exists:employees,id']);
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        $newEmployee = Employee::where('id', $request->employee_id)->where('company_id', $companyId)->first();
        if (!$newEmployee) {
            return response()->json(['success' => false], 403);
        }
        $pending = $request->session()->get('pending_payroll_uploads', []);
        $index = (int) $request->input('index');
        if (!isset($pending[$index])) {
            return response()->json(['success' => false], 404);
        }
        $pending[$index]['employee_id'] = $newEmployee->id;
        $pending[$index]['file_name'] = $this->pendingSuggestedFileName($pending[$index]['original_base'] ?? '', $newEmployee->full_name);
        $request->session()->put('pending_payroll_uploads', $pending);
        return response()->json(['success' => true, 'email' => $newEmployee->email, 'file_name' => $pending[$index]['file_name']]);
    }

    public function pendingRemove(Request $request)
    {
        $request->validate(['index' => 'required|integer|min:0']);
        $pending = $request->session()->get('pending_payroll_uploads', []);
        $index = (int) $request->input('index');
        if (!isset($pending[$index])) {
            return response()->json(['success' => false], 404);
        }
        $tempPath = $pending[$index]['temp_path'] ?? null;
        if ($tempPath && Storage::disk('local')->exists($tempPath)) {
            Storage::disk('local')->delete($tempPath);
        }
        unset($pending[$index]);
        $request->session()->put('pending_payroll_uploads', $pending);
        if (empty($pending)) {
            $uploadId = $request->session()->get('pending_payroll_upload_id');
            if ($uploadId) {
                Storage::disk('local')->deleteDirectory('temp_payrolls/' . $uploadId);
            }
            $request->session()->forget(['pending_payroll_uploads', 'pending_payroll_upload_id']);
            return response()->json(['success' => true, 'redirect' => route('employees.index')]);
        }
        return response()->json(['success' => true]);
    }

    public function cancelPending(Request $request)
    {
        $uploadId = $request->session()->get('pending_payroll_upload_id');
        if ($uploadId) {
            Storage::disk('local')->deleteDirectory('temp_payrolls/' . $uploadId);
            $request->session()->forget(['pending_payroll_uploads', 'pending_payroll_upload_id']);
        }
        $ids = $request->session()->get('pending_payroll_ids', []);
        if (!empty($ids)) {
            $companyId = session('company_id') ?? Auth::user()?->company_id;
            $payrolls = Payroll::with('employee')->whereIn('id', $ids)->get();
            foreach ($payrolls as $payroll) {
                if (!$payroll->employee || $payroll->employee->company_id != $companyId) {
                    continue;
                }
                if ($payroll->file_path && Storage::disk('local')->exists($payroll->file_path)) {
                    Storage::disk('local')->delete($payroll->file_path);
                }
                $payroll->forceDelete();
            }
        }
        $request->session()->forget('pending_payroll_ids');
        return redirect()->route('employees.index')->with('success', 'Envío cancelado. No se ha guardado ninguna nómina.');
    }

    public function pendingSend(Request $request)
    {
        $pending = $request->session()->get('pending_payroll_uploads', []);
        if (empty($pending)) {
            return redirect()->route('employees.index')->with('info', 'No hay nóminas pendientes de envío.');
        }
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        $employees = Employee::where('company_id', $companyId)->orderBy('full_name')->get(['id', 'full_name', 'email']);
        $pendingRows = [];
        foreach ($pending as $index => $item) {
            $employee = $employees->firstWhere('id', $item['employee_id']);
            $pendingRows[] = (object) [
                'index' => $index,
                'employee' => $employee,
                'file_name' => $item['file_name'],
                'email' => $employee ? $employee->email : '',
                'original_base' => $item['original_base'] ?? '',
            ];
        }
        $templates = EmailTemplate::where('company_id', $companyId)->where('type', 'payroll')->orderBy('name')->get();
        $company = Company::find($companyId);
        return view('payroll.pending-send', compact('pendingRows', 'templates', 'company', 'employees'));
    }

    public function sendBulk(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|min:0',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
        ]);
        $ids = array_values($request->input('ids', []));
        if (empty($ids)) {
            return redirect()->route('payroll.pending-send')->with('info', 'No se seleccionó ninguna nómina para enviar.');
        }
        $subject = $request->input('subject');
        $body = $request->input('body');
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        $pending = $request->session()->get('pending_payroll_uploads', []);
        $sent = 0;
        $errors = [];
        if (!empty($pending)) {
            foreach ($ids as $index) {
                if (!isset($pending[$index])) {
                    continue;
                }
                $item = $pending[$index];
                $employeeId = (int) $request->input('employee_id_' . $index, $item['employee_id']);
                $employee = Employee::where('id', $employeeId)->where('company_id', $companyId)->first();
                if (!$employee) {
                    $errors[] = 'Fila ' . ($index + 1) . ': empleado no válido.';
                    continue;
                }
                if ($employee->payrolls()->where('month', $item['month'])->where('year', $item['year'])->exists()) {
                    $errors[] = 'Ya existe una nómina de ' . $employee->full_name . ' para ese periodo.';
                    continue;
                }
                $email = trim((string) $request->input('email_' . $index, $employee->email));
                if ($email === '') {
                    $errors[] = 'Nómina ' . ($item['file_name'] ?? '') . ': sin email.';
                    continue;
                }
                $customFileName = trim((string) $request->input('file_name_' . $index, $item['file_name']));
                if ($customFileName === '') {
                    $customFileName = $this->pendingSuggestedFileName($item['original_base'] ?? '', $employee->full_name);
                }
                if (!str_ends_with(strtolower($customFileName), '.pdf')) {
                    $customFileName .= '.pdf';
                }
                $tempPath = $item['temp_path'];
                if (!Storage::disk('local')->exists($tempPath)) {
                    $errors[] = 'Archivo temporal no encontrado.';
                    continue;
                }
                $dir = 'payrolls/' . $employee->id;
                $finalPath = $dir . '/' . $customFileName;
                Storage::disk('local')->makeDirectory($dir);
                Storage::disk('local')->move($tempPath, $finalPath);
                $date = isset($item['date']) ? \Carbon\Carbon::parse($item['date']) : now();
                $payroll = $employee->payrolls()->create([
                    'file_name' => $customFileName,
                    'date' => $date,
                    'month' => $item['month'],
                    'year' => $item['year'],
                    'file_path' => $finalPath,
                    'base64_content' => null,
                    'matched_by' => 'manual',
                ]);
                try {
                    $this->sendPayrollEmail($payroll, $email, $subject, $body);
                    $payroll->update(['sent_at' => now(), 'sent_by' => Auth::id()]);
                    $sent++;
                } catch (\Throwable $e) {
                    report($e);
                    $errors[] = 'Nómina ' . $customFileName . ': ' . $e->getMessage();
                }
            }
            $uploadId = $request->session()->get('pending_payroll_upload_id');
            if ($uploadId) {
                Storage::disk('local')->deleteDirectory('temp_payrolls/' . $uploadId);
            }
            $request->session()->forget(['pending_payroll_uploads', 'pending_payroll_upload_id']);
        } else {
            $payrollIds = array_filter($ids, fn ($id) => is_numeric($id));
            $payrolls = Payroll::with('employee')->whereIn('id', $payrollIds)->whereNull('sent_at')->get();
            $payrolls = $payrolls->filter(fn ($p) => $p->employee && $p->employee->company_id == $companyId);
            foreach ($payrolls as $payroll) {
                $email = $request->input('email_' . $payroll->id) ?: $payroll->employee->email;
                if (!$email) {
                    $errors[] = 'Nómina ' . ($payroll->file_name ?? $payroll->id) . ': sin email.';
                    continue;
                }
                $customFileName = $request->input('file_name_' . $payroll->id);
                if (is_string($customFileName) && trim($customFileName) !== '') {
                    $payroll->update(['file_name' => trim($customFileName)]);
                }
                try {
                    $this->sendPayrollEmail($payroll, $email, $subject, $body);
                    $payroll->update(['sent_at' => now(), 'sent_by' => Auth::id()]);
                    $sent++;
                } catch (\Throwable $e) {
                    report($e);
                    $errors[] = 'Nómina ' . ($payroll->file_name ?? $payroll->id) . ': ' . $e->getMessage();
                }
            }
            $request->session()->forget('pending_payroll_ids');
        }
        if (!empty($errors)) {
            $msg = $sent > 0
                ? "Se enviaron {$sent} nómina(s). Errores: " . implode(' ', $errors)
                : 'No se pudo enviar ningún correo. ' . implode(' ', array_slice($errors, 0, 3));
            return redirect()->route('employees.index')->with('error', $msg);
        }
        return redirect()->route('employees.index')->with('success', $sent . ' nómina(s) enviada(s) correctamente.');
    }

    private function pendingSuggestedFileName(string $originalBase, string $employeeFullName): string
    {
        $base = trim(preg_replace('/[\\\\\/:*?"<>|]/', '', $originalBase));
        $name = trim(preg_replace('/\s+/', ' ', $employeeFullName));
        return trim(preg_replace('/\s+/', ' ', $base . ' ' . $name . '.pdf'));
    }

    private function sendPayrollEmail(Payroll $payroll, string $to, string $subject, string $body): void
    {
        $company = $payroll->employee->company ?? Company::find(session('company_id'));
        $name = $payroll->employee->full_name ?? '';
        $monthName = $this->spanishMonth($payroll->month);
        $empresa = $company->name ?? '';
        $subject = str_replace(['{{nombre}}', '{{mes}}', '{{empresa}}'], [$name, $monthName, $empresa], $subject);
        $body = str_replace(['{{nombre}}', '{{mes}}', '{{empresa}}'], [$name, $monthName, $empresa], $body);
        $fromAddress = $company->rrhh_mail_from_address ?? config('mail.from.address');
        $fromName = $company->rrhh_mail_from_name ?? config('mail.from.name');
        $mailer = $this->configurePayrollMailer($company);
        $path = null;
        if ($payroll->file_path && Storage::disk('local')->exists($payroll->file_path)) {
            $path = Storage::disk('local')->path($payroll->file_path);
        } elseif (!empty($payroll->base64_content)) {
            $decoded = base64_decode($payroll->base64_content, true);
            if ($decoded !== false) {
                $tmp = tempnam(sys_get_temp_dir(), 'payroll_');
                if ($tmp && file_put_contents($tmp, $decoded) !== false) {
                    $path = $tmp;
                }
            }
        }
        Mail::mailer($mailer)->raw($body, function ($message) use ($to, $subject, $path, $payroll, $fromAddress, $fromName) {
            $message->from($fromAddress, $fromName)
                ->to($to)
                ->subject($subject);

            if ($path) {
                $message->attach($path, [
                    'as' => $payroll->file_name ?? 'nomina.pdf',
                    'mime' => 'application/pdf',
                ]);
            }
        });
        if ($path && str_starts_with($path, sys_get_temp_dir())) {
            @unlink($path);
        }
    }

    private function configurePayrollMailer(?Company $company): string
    {
        $smtpHost = $company?->rrhh_mail_smtp_host;
        if (empty($smtpHost)) {
            return config('mail.default', 'log');
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $smtpHost);
        Config::set('mail.mailers.smtp.port', (int) ($company->rrhh_mail_smtp_port ?: config('mail.mailers.smtp.port', 587)));
        Config::set('mail.mailers.smtp.encryption', $company->rrhh_mail_encryption ?: config('mail.mailers.smtp.encryption'));
        Config::set('mail.mailers.smtp.username', $company->rrhh_mail_smtp_username ?: config('mail.mailers.smtp.username'));
        Config::set('mail.mailers.smtp.password', $company->rrhh_mail_smtp_password ?: config('mail.mailers.smtp.password'));
        Config::set('mail.from.address', $company->rrhh_mail_from_address ?: config('mail.from.address'));
        Config::set('mail.from.name', $company->rrhh_mail_from_name ?: config('mail.from.name'));

        return 'smtp';
    }

    private function spanishMonth(?int $month): string
    {
        $m = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
        return $m[$month] ?? 'Enero';
    }

    public function assignEmployee(Request $request, Payroll $payroll)
    {
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        if (!$payroll->employee || $payroll->employee->company_id != $companyId) {
            abort(404);
        }
        $request->validate(['employee_id' => 'required|exists:employees,id']);
        $newEmployee = Employee::findOrFail($request->employee_id);
        if ($newEmployee->company_id != $companyId) {
            abort(403);
        }
        if ($payroll->sent_at) {
            return response()->json(['success' => false, 'message' => 'No se puede cambiar el empleado de una nómina ya enviada.'], 422);
        }
        $payroll->update(['employee_id' => $newEmployee->id]);
        $date = $payroll->date ?? now();
        $monthName = $this->spanishMonth($payroll->month);
        $newFileName = "Nomina {$newEmployee->full_name} {$monthName} {$payroll->year}.pdf";
        if ($payroll->file_path && Storage::disk('local')->exists($payroll->file_path)) {
            $newPath = 'payrolls/' . $newEmployee->id . '/' . $newFileName;
            Storage::disk('local')->move($payroll->file_path, $newPath);
            $payroll->update(['file_path' => $newPath, 'file_name' => $newFileName]);
        } else {
            $payroll->update(['file_name' => $newFileName]);
        }
        return response()->json(['success' => true, 'email' => $newEmployee->email, 'file_name' => $newFileName]);
    }

    public function destroy(Request $request, Payroll $payroll)
    {
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        if (!$payroll->employee || $payroll->employee->company_id != $companyId) {
            abort(404);
        }
        if ($payroll->file_path && Storage::disk('local')->exists($payroll->file_path)) {
            Storage::disk('local')->delete($payroll->file_path);
        }
        $payroll->forceDelete();
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Nómina eliminada.');
    }

    public function view(Payroll $payroll)
    {
        if ($payroll->file_path && Storage::disk('local')->exists($payroll->file_path)) {
            $pdfContent = Storage::disk('local')->get($payroll->file_path);
        } else {
            $base64 = $payroll->base64_content ?? '';
            $pdfContent = base64_decode($base64) ?: '';
        }
        if ($pdfContent === '') {
            abort(404, 'Archivo no encontrado.');
        }
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . ($payroll->file_name ?? 'nomina.pdf') . '"');
    }
}
