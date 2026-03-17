<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Part\TextPart;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payroll.view')->only(['view', 'pendingSend']);
        $this->middleware('permission:payroll.send')->only(['sendBulk']);
        $this->middleware('permission:payroll.create')->only(['assignEmployee']);
        $this->middleware('permission:payroll.delete')->only(['destroy', 'cancelPending', 'removePending']);
    }

    private function getPendingFromTokenOrSession(Request $request): array
    {
        $token = $request->input('pending_token') ?: $request->query('token') ?: $request->cookie('pending_payroll_token') ?: $request->session()->get('pending_payrolls_token');
        if ($token) {
            $cached = Cache::get('pending_payrolls_' . $token);
            if ($cached !== null) {
                $request->session()->put('pending_payrolls_token', $token);
                return $cached;
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('pending_payroll_uploads')) {
                $row = DB::table('pending_payroll_uploads')->where('token', $token)->where('expires_at', '>', now())->first();
                if ($row) {
                    $request->session()->put('pending_payrolls_token', $token);
                    return json_decode($row->payload, true) ?: [];
                }
            }
        }
        return $request->session()->get('pending_payrolls', []);
    }

    private function forgetPendingByToken(string $token): void
    {
        Cache::forget('pending_payrolls_' . $token);
        if (\Illuminate\Support\Facades\Schema::hasTable('pending_payroll_uploads')) {
            DB::table('pending_payroll_uploads')->where('token', $token)->delete();
        }
    }

    private function putPendingAndToken(Request $request, string $token, array $pending): void
    {
        if (empty($pending)) {
            $this->forgetPendingByToken($token);
            $request->session()->forget('pending_payrolls_token');
        } else {
            $expiresAt = now()->addHours(1);
            Cache::put('pending_payrolls_' . $token, $pending, $expiresAt);
            if (\Illuminate\Support\Facades\Schema::hasTable('pending_payroll_uploads')) {
                DB::table('pending_payroll_uploads')->updateOrInsert(
                    ['token' => $token],
                    ['payload' => json_encode($pending), 'expires_at' => $expiresAt]
                );
            }
            $request->session()->put(['pending_payrolls_token' => $token, 'pending_payrolls' => $pending]);
        }
    }

    public function cancelPending(Request $request)
    {
        $token = $request->input('pending_token') ?: $request->cookie('pending_payroll_token') ?: $request->session()->get('pending_payrolls_token');
        $pending = $this->getPendingFromTokenOrSession($request);
        foreach ($pending as $item) {
            $path = $item['temp_path'] ?? null;
            if ($path && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
        if ($token && Storage::disk('local')->exists('temp_payrolls/' . $token)) {
            Storage::disk('local')->deleteDirectory('temp_payrolls/' . $token);
        }
        if ($token) {
            $this->forgetPendingByToken($token);
        }
        $request->session()->forget(['pending_payrolls', 'pending_payrolls_token']);
        return redirect()->route('employees.index')->with('success', 'Envío cancelado. No se ha guardado ninguna nómina.')
            ->cookie('pending_payroll_token', '', -1, '/');
    }

    public function removePending(Request $request, int $index)
    {
        $pending = $this->getPendingFromTokenOrSession($request);
        if (!isset($pending[$index])) {
            $token = $request->session()->get('pending_payrolls_token');
            return redirect()->route('payroll.pending-send', $token ? ['token' => $token] : [])->with('error', 'Elemento no encontrado.');
        }
        $path = $pending[$index]['temp_path'] ?? null;
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
        array_splice($pending, $index, 1);
        $token = $request->session()->get('pending_payrolls_token');
        if (empty($pending)) {
            if ($token) {
                $this->forgetPendingByToken($token);
                if (Storage::disk('local')->exists('temp_payrolls/' . $token)) {
                    Storage::disk('local')->deleteDirectory('temp_payrolls/' . $token);
                }
            }
            $request->session()->forget(['pending_payrolls', 'pending_payrolls_token']);
            return redirect()->route('employees.index')->with('info', 'No quedan nóminas pendientes.')
                ->cookie('pending_payroll_token', '', -1, '/');
        }
        if ($token) {
            $this->putPendingAndToken($request, $token, $pending);
        } else {
            $request->session()->put('pending_payrolls', $pending);
        }
        return redirect()->route('payroll.pending-send', $token ? ['token' => $token] : [])->with('success', 'Nómina quitada de la lista.');
    }

    public function pendingSend(Request $request, $token = null)
    {
        $token = $token ?? $request->query('token') ?? $request->cookie('pending_payroll_token') ?? $request->session()->get('pending_payrolls_token');
        $pending = [];
        if ($token) {
            $pending = Cache::get('pending_payrolls_' . $token);
            if ($pending === null && \Illuminate\Support\Facades\Schema::hasTable('pending_payroll_uploads')) {
                try {
                    $row = DB::table('pending_payroll_uploads')
                        ->where('token', $token)
                        ->where('expires_at', '>', now())
                        ->first();
                    if ($row && isset($row->payload)) {
                        $decoded = is_string($row->payload) ? json_decode($row->payload, true) : $row->payload;
                        $pending = is_array($decoded) ? $decoded : [];
                    }
                } catch (\Throwable $e) {
                    // fallback to session below
                }
            }
        }
        if (!is_array($pending) || empty($pending)) {
            $pending = $request->session()->get('pending_payrolls', []);
        }
        if (empty($pending)) {
            return redirect()->route('employees.index')->with('info', 'No hay nóminas pendientes de envío.');
        }
        if ($token) {
            $request->session()->put('pending_payrolls_token', $token);
        }
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        if ($companyId === null && !empty($pending)) {
            foreach ($pending as $item) {
                $first = Employee::find($item['employee_id'] ?? null);
                if ($first) {
                    $companyId = $first->company_id;
                    $request->session()->put('company_id', $companyId);
                    break;
                }
            }
        }
        $employees = $companyId ? Employee::where('company_id', $companyId)->orderBy('full_name')->get(['id', 'full_name', 'email']) : collect();
        $employeeMap = $employees->keyBy('id');
        $pendingRows = [];
        foreach ($pending as $i => $item) {
            $emp = $employeeMap->get($item['employee_id']) ?? Employee::find($item['employee_id']);
            if (!$emp || ($companyId !== null && $emp->company_id != $companyId)) {
                continue;
            }
            $pendingRows[] = (object) [
                'index' => $i,
                'employee_id' => $item['employee_id'],
                'file_name' => $item['file_name'],
                'month' => $item['month'],
                'year' => $item['year'],
                'employee' => $emp,
                'temp_path' => $item['temp_path'],
            ];
        }
        $templates = $companyId ? EmailTemplate::where('company_id', $companyId)->where('type', 'payroll')->orderBy('name')->get() : collect();
        $company = $companyId ? Company::find($companyId) : null;
        $pendingToken = $token;
        return view('payroll.pending-send', compact('pendingRows', 'templates', 'company', 'employees', 'pendingToken'));
    }

    public function sendBulk(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|min:0',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
        ]);
        $ids = array_map('intval', $request->input('ids', []));
        if (empty($ids)) {
            $token = $request->session()->get('pending_payrolls_token');
            return redirect()->route('payroll.pending-send', $token ? ['token' => $token] : [])->with('info', 'No se seleccionó ninguna nómina para enviar.');
        }
        $pending = $this->getPendingFromTokenOrSession($request);
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        $subject = $request->input('subject');
        $body = $request->input('body');
        $sent = 0;
        $errors = [];
        $createdPayrolls = [];
        foreach ($ids as $index) {
            if (!isset($pending[$index])) {
                continue;
            }
            $item = $pending[$index];
            $employeeId = (int) ($request->input('employee_id_' . $index) ?? $item['employee_id']);
            $email = trim((string) $request->input('email_' . $index));
            $employee = Employee::where('company_id', $companyId)->find($employeeId);
            if (!$employee) {
                $errors[] = 'Fila ' . ($index + 1) . ': empleado no válido.';
                continue;
            }
            if (!$email) {
                $email = $employee->email;
            }
            if (!$email) {
                $errors[] = 'Nómina ' . ($item['file_name'] ?? $index) . ': sin email.';
                continue;
            }
            $customFileName = trim((string) $request->input('file_name_' . $index));
            $fileName = $customFileName !== '' ? $customFileName : $item['file_name'];
            $tempPath = $item['temp_path'];
            if (!$tempPath || !Storage::disk('local')->exists($tempPath)) {
                $errors[] = 'Nómina ' . $fileName . ': archivo no encontrado.';
                continue;
            }
            if ($employee->payrolls()->where('month', $item['month'])->where('year', $item['year'])->exists()) {
                $errors[] = 'Nómina ' . $fileName . ': ya existe una nómina para ese mes/año para ' . $employee->full_name . '.';
                continue;
            }
            $finalDir = 'payrolls/' . $employee->id;
            $finalPath = $finalDir . '/' . $fileName;
            $fullPath = Storage::disk('local')->path($tempPath);
            Storage::disk('local')->put($finalPath, file_get_contents($fullPath));
            Storage::disk('local')->delete($tempPath);
            $payroll = $employee->payrolls()->create([
                'file_name' => $fileName,
                'date' => $item['date'],
                'month' => $item['month'],
                'year' => $item['year'],
                'file_path' => $finalPath,
                'base64_content' => null,
                'matched_by' => null,
            ]);
            try {
                $this->sendPayrollEmail($payroll, $email, $subject, $body);
                $payroll->update(['sent_at' => now(), 'sent_by' => Auth::id()]);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $errors[] = 'Nómina ' . $fileName . ': ' . $e->getMessage();
            }
        }
        $token = $request->session()->get('pending_payrolls_token');
        $remaining = array_diff_key($pending, array_flip($ids));
        if (empty($remaining)) {
            if ($token) {
                $this->forgetPendingByToken($token);
                if (Storage::disk('local')->exists('temp_payrolls/' . $token)) {
                    Storage::disk('local')->deleteDirectory('temp_payrolls/' . $token);
                }
            }
            $request->session()->forget(['pending_payrolls', 'pending_payrolls_token']);
        } else {
            $remaining = array_values($remaining);
            if ($token) {
                $this->putPendingAndToken($request, $token, $remaining);
            } else {
                $request->session()->put('pending_payrolls', $remaining);
            }
        }
        if (!empty($errors)) {
            $msg = $sent > 0
                ? "Se guardaron y enviaron {$sent} nómina(s). Errores: " . implode(' ', $errors)
                : 'No se pudo enviar ningún correo. ' . implode(' ', array_slice($errors, 0, 3));
            return redirect()->route($sent > 0 ? 'employees.index' : 'payroll.pending-send', $token ? ['token' => $token] : [])->with('error', $msg);
        }
        return redirect()->route('employees.index')->with('success', $sent . ' nómina(s) guardada(s) y enviada(s) correctamente.');
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

        $mailer = null;
        if (!empty($company->rrhh_mail_smtp_host)) {
            Config::set('mail.mailers.rrhh_dynamic', [
                'transport' => 'smtp',
                'host' => $company->rrhh_mail_smtp_host,
                'port' => (int) ($company->rrhh_mail_smtp_port ?? 587),
                'encryption' => $company->rrhh_mail_encryption ?: 'tls',
                'username' => $company->rrhh_mail_smtp_username,
                'password' => $company->rrhh_mail_smtp_password,
                'timeout' => null,
            ]);
            $mailer = 'rrhh_dynamic';
        }

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

        $callback = function ($message) use ($to, $subject, $body, $path, $payroll, $fromAddress, $fromName) {
            $message->from($fromAddress, $fromName)->to($to)->subject($subject)->setBody(new TextPart($body));
            if ($path) {
                $message->attach($path, ['as' => $payroll->file_name ?? 'nomina.pdf', 'mime' => 'application/pdf']);
            }
        };

        if ($mailer) {
            Mail::mailer($mailer)->send([], [], $callback);
        } else {
            $driver = config('mail.default');
            if (in_array($driver, ['log', 'array'], true)) {
                throw new \RuntimeException('Configura la sección "Configuración correo RRHH" en Ajustes → Empresa (SMTP) para poder enviar nóminas por correo.');
            }
            Mail::send([], [], $callback);
        }

        if ($path && str_starts_with($path, sys_get_temp_dir())) {
            @unlink($path);
        }
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
