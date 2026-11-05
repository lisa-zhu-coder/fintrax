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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\Mime\Part\TextPart;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if ($user && ($user->isSuperAdmin() || $user->isAdmin() || $user->canAccessPayrollArea())) {
                return $next($request);
            }
            abort(403, 'No tienes permisos para acceder a esta página.');
        })->only(['pendingSend', 'processStatus']);
        $this->middleware('permission:hr.payroll.send')->only(['sendBulk']);
        $this->middleware('permission:hr.payroll.upload')->only(['assignEmployee', 'pendingAssignEmployee', 'rename']);
        $this->middleware('permission:hr.payroll.delete')->only(['destroy', 'cancelPending', 'pendingRemove']);
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
        $payrollToken = $request->input('payroll_token');
        if (is_string($payrollToken) && $payrollToken !== '') {
            Cache::forget('payroll_result_' . $payrollToken);
            Cache::forget('payroll_error_' . $payrollToken);
        }

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

    public function processStatus(string $token)
    {
        $error = Cache::get('payroll_error_' . $token);
        if ($error !== null) {
            return response()->json(['status' => 'error', 'message' => $error['message'] ?? 'Error al procesar el PDF.']);
        }
        $result = Cache::get('payroll_result_' . $token);
        if ($result !== null) {
            return response()->json([
                'status' => 'done',
                'redirect' => route('payroll.pending-send', ['token' => $token]),
            ]);
        }
        return response()->json(['status' => 'processing']);
    }

    public function pendingSend(Request $request)
    {
        $token = $request->query('token');
        if ($token !== null && $token !== '') {
            $error = Cache::get('payroll_error_' . $token);
            if ($error !== null) {
                Cache::forget('payroll_error_' . $token);
                return redirect()->route('employees.index')->with('error', $error['message'] ?? 'Error al procesar el PDF.');
            }
            $result = Cache::get('payroll_result_' . $token);
            if ($result !== null) {
                $request->session()->put('pending_payroll_uploads', $result['pending']);
                $request->session()->put('pending_payroll_upload_id', $result['upload_id']);
                // No borrar la caché aquí: el POST de "Guardar y enviar" puede ir a otro servidor y no tener sesión; recuperaremos por token
                $pending = $result['pending'];
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
                return view('payroll.pending-send', compact('pendingRows', 'templates', 'company', 'employees', 'token'))->with('success', $result['message'] ?? '');
            }
            return redirect()->route('employees.index')->with('error', 'No se encontraron los datos del PDF (puede haber expirado). Sube el PDF de nuevo.');
        }

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
        $token = null;
        return view('payroll.pending-send', compact('pendingRows', 'templates', 'company', 'employees', 'token'));
    }

    public function sendBulk(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|min:0',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
        ]);
        $ids = array_values($request->input('ids', [])); // Solo las filas marcadas para enviar por correo
        $subject = $request->input('subject');
        $body = $request->input('body');
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        $pending = $request->session()->get('pending_payroll_uploads', []);
        $uploadId = $request->session()->get('pending_payroll_upload_id');

        // Si la sesión no tiene los datos (p. ej. otro servidor), recuperar desde caché con el token del formulario
        $payrollToken = $request->input('payroll_token');
        if (empty($pending) && $payrollToken !== null && $payrollToken !== '') {
            $result = Cache::get('payroll_result_' . $payrollToken);
            if ($result !== null) {
                $pending = $result['pending'];
                $uploadId = $result['upload_id'];
            }
        }

        $saved = 0;
        $sent = 0;
        $errors = [];
        if (empty($pending) && !empty($ids) && max($ids) < 1000) {
            return redirect()->route('employees.index')->with('error', 'La sesión de las nóminas ha expirado o se perdió. Por favor, sube el PDF de nuevo.');
        }
        if (!empty($pending)) {
            $payrollsByIndex = [];
            // 1) Guardar TODAS las nóminas en la ficha de cada empleado (marcada o no la casilla de envío)
            foreach ($pending as $index => $item) {
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
                $customFileName = trim((string) $request->input('file_name_' . $index, $item['file_name']));
                if ($customFileName === '') {
                    $customFileName = $this->pendingSuggestedFileName($item['original_base'] ?? '', $employee->full_name);
                }
                $customFileName = $this->sanitizePayrollStorageFileName($customFileName);
                $tempPath = $item['temp_path'];
                if (!Storage::disk('local')->exists($tempPath)) {
                    $errors[] = 'Archivo temporal no encontrado (fila ' . ($index + 1) . ').';
                    continue;
                }
                $dir = 'payrolls/' . $employee->id;
                $finalPath = $dir . '/' . $customFileName;
                Storage::disk('local')->makeDirectory($dir);
                if (! $this->persistPayrollPdfFromTemp($tempPath, $finalPath)) {
                    $errors[] = 'No se pudo guardar el PDF en el servidor (fila ' . ($index + 1) . ').';
                    continue;
                }
                $pdfBinary = Storage::disk('local')->get($finalPath) ?: '';
                if ($pdfBinary === '') {
                    $errors[] = 'El PDF quedó vacío tras guardarlo (fila ' . ($index + 1) . ').';
                    continue;
                }
                $date = isset($item['date']) ? \Carbon\Carbon::parse($item['date']) : now();
                $payroll = $employee->payrolls()->create([
                    'file_name' => $customFileName,
                    'date' => $date,
                    'month' => $item['month'],
                    'year' => $item['year'],
                    'file_path' => $finalPath,
                    // El PDF ya está en disco; no duplicar en BD (evita error 1406 por TEXT/LONGTEXT).
                    'base64_content' => null,
                    'matched_by' => 'manual',
                ]);
                $payrollsByIndex[$index] = $payroll;
                $saved++;
            }
            // 2) Enviar correo solo a las filas cuya casilla "Enviar" estaba marcada (y que tengan email)
            foreach ($ids as $index) {
                if (!isset($payrollsByIndex[$index])) {
                    continue;
                }
                $payroll = $payrollsByIndex[$index];
                $email = trim((string) $request->input('email_' . $index, $payroll->employee->email ?? ''));
                if ($email === '') {
                    $errors[] = 'Nómina ' . ($payroll->file_name ?? '') . ': sin email, no se envía correo.';
                    continue;
                }
                try {
                    $this->sendPayrollEmail($payroll, $email, $subject, $body);
                    $payroll->update(['sent_at' => now(), 'sent_by' => Auth::id()]);
                    $sent++;
                } catch (\Throwable $e) {
                    report($e);
                    $errors[] = 'Nómina ' . ($payroll->file_name ?? '') . ': ' . $e->getMessage();
                }
            }
            if ($uploadId) {
                Storage::disk('local')->deleteDirectory('temp_payrolls/' . $uploadId);
            }
            $request->session()->forget(['pending_payroll_uploads', 'pending_payroll_upload_id']);
            if ($payrollToken !== null && $payrollToken !== '') {
                Cache::forget('payroll_result_' . $payrollToken);
            }
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
            $msg = $saved > 0 || $sent > 0
                ? ($saved > 0 ? "Se guardaron {$saved} nómina(s) en las fichas. " : '') . ($sent > 0 ? "Se enviaron {$sent} correo(s). " : '') . 'Errores: ' . implode(' ', array_slice($errors, 0, 3))
                : implode(' ', array_slice($errors, 0, 3));
            return redirect()->route('employees.index')->with('error', $msg);
        }
        $msg = $saved > 0 ? "Se han guardado {$saved} nómina(s) en las fichas." : '';
        if ($sent > 0) {
            $msg .= ($msg ? ' ' : '') . "Se han enviado {$sent} correo(s).";
        }
        return redirect()->route('employees.index')->with('success', $msg ?: 'Hecho.');
    }

    private function pendingSuggestedFileName(string $originalBase, string $employeeFullName): string
    {
        $base = trim(preg_replace('/[\\\\\/:*?"<>|]/', '', $originalBase));
        $name = trim(preg_replace('/\s+/', ' ', $employeeFullName));

        return $this->sanitizePayrollStorageFileName(trim(preg_replace('/\s+/', ' ', $base . ' ' . $name . '.pdf')));
    }

    private function sanitizePayrollStorageFileName(string $fileName): string
    {
        $fileName = trim(preg_replace('/[\\\\\/:*?"<>|]/', '', $fileName));
        $fileName = trim(preg_replace('/\s+/', ' ', $fileName));
        if ($fileName === '') {
            return 'nomina.pdf';
        }
        if (! str_ends_with(strtolower($fileName), '.pdf')) {
            $fileName .= '.pdf';
        }

        return $fileName;
    }

    private function persistPayrollPdfFromTemp(string $tempPath, string $finalPath): bool
    {
        $disk = Storage::disk('local');
        if ($disk->exists($finalPath)) {
            $disk->delete($finalPath);
        }
        if ($disk->move($tempPath, $finalPath)) {
            return $disk->exists($finalPath);
        }

        $contents = $disk->get($tempPath);
        if ($contents === null || $contents === '') {
            return false;
        }

        $saved = $disk->put($finalPath, $contents);
        if ($saved) {
            $disk->delete($tempPath);
        }

        return $saved && $disk->exists($finalPath);
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

    public function rename(Request $request, Payroll $payroll)
    {
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        if (!$payroll->employee || $payroll->employee->company_id != $companyId) {
            abort(404);
        }

        $request->validate([
            'file_name' => 'required|string|max:255',
        ]);

        $newFileName = $this->sanitizePayrollStorageFileName((string) $request->input('file_name'));

        if ($payroll->file_path && Storage::disk('local')->exists($payroll->file_path)) {
            $dir = 'payrolls/' . $payroll->employee_id;
            $newPath = $dir . '/' . $newFileName;
            if ($newPath !== $payroll->file_path) {
                Storage::disk('local')->makeDirectory($dir);
                if (Storage::disk('local')->exists($newPath)) {
                    Storage::disk('local')->delete($newPath);
                }
                Storage::disk('local')->move($payroll->file_path, $newPath);
                $payroll->update(['file_path' => $newPath, 'file_name' => $newFileName]);
            } else {
                $payroll->update(['file_name' => $newFileName]);
            }
        } else {
            $payroll->update(['file_name' => $newFileName]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'file_name' => $newFileName]);
        }

        return redirect()->back()->with('success', 'Nombre de la nómina actualizado.');
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

    public function view(int $payroll)
    {
        $payroll = Payroll::withTrashed()->find($payroll);
        if (! $payroll) {
            return response('No se encontró esta nómina.', 404);
        }

        $this->authorizePayrollView($payroll);

        $absolutePath = $this->locatePayrollPdfPath($payroll);
        $pdfContent = $absolutePath ? '' : $this->resolvePayrollPdfContent($payroll);
        if ($absolutePath === null && $pdfContent === '') {
            return response('No se encontró el archivo PDF de esta nómina. Elimínala y vuelve a subirla.', 404);
        }

        if ($pdfContent !== '') {
            $this->ensurePayrollFileOnDisk($payroll, $pdfContent);
            if ($payroll->file_path) {
                $rewritten = $this->absolutePathIfReadable($payroll->file_path);
                if ($rewritten !== null) {
                    $absolutePath = $rewritten;
                    $pdfContent = '';
                }
            }
        }

        $fileName = $this->dispositionFileName($payroll->file_name ?: 'nomina.pdf');
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $fileName,
                $this->asciiFallbackFileName($fileName)
            ),
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($absolutePath !== null) {
            return response()->file($absolutePath, $headers);
        }

        return response($pdfContent, 200, $headers + [
            'Content-Length' => (string) strlen($pdfContent),
        ]);
    }

    private function dispositionFileName(string $fileName): string
    {
        $fileName = trim(str_replace(['/', '\\'], '', $fileName));

        return $fileName !== '' ? $fileName : 'nomina.pdf';
    }

    private function asciiFallbackFileName(string $fileName): string
    {
        $ascii = Str::ascii($this->dispositionFileName($fileName));
        $ascii = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $ascii) ?? '';
        $ascii = trim($ascii, " ._");
        if ($ascii === '') {
            $ascii = 'nomina';
        }
        if (! str_ends_with(strtolower($ascii), '.pdf')) {
            $ascii .= '.pdf';
        }

        return $ascii;
    }

    private function authorizePayrollView(Payroll $payroll): void
    {
        $user = Auth::user();
        $employee = Employee::withoutGlobalScopes()
            ->withTrashed()
            ->with('stores')
            ->find($payroll->employee_id);

        if (! $employee) {
            abort(404, 'Empleado no encontrado para esta nómina.');
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return;
        }

        $companyId = session('company_id') ?? $user->company_id;
        if ($employee->company_id !== null && $companyId !== null
            && (int) $employee->company_id !== (int) $companyId) {
            abort(404);
        }

        if ($user->canViewPayrollPdf($employee)) {
            $isOwn = (int) $employee->user_id === (int) $user->id;
            if (! $isOwn && $user->hasPermission('hr.employees.view_store')) {
                $enforcedStoreId = $user->getEnforcedStoreId();
                if ($enforcedStoreId !== null && ! $employee->stores->contains('id', $enforcedStoreId)) {
                    abort(403, 'No tienes acceso a los datos de este empleado.');
                }
            }

            return;
        }

        abort(403, 'No tienes permiso para ver esta nómina.');
    }

    private function locatePayrollPdfPath(Payroll $payroll): ?string
    {
        foreach ($this->payrollPathCandidates($payroll) as $candidate) {
            $absolute = $this->absolutePathIfReadable($candidate);
            if ($absolute !== null) {
                $this->rememberPayrollPath($payroll, $absolute);

                return $absolute;
            }
        }

        $targetKey = $this->payrollNameKey($payroll->file_name);
        if ($targetKey === '') {
            return null;
        }

        foreach ($this->payrollStorageRoots() as $root) {
            $base = $root.DIRECTORY_SEPARATOR.'payrolls';
            if (! is_dir($base)) {
                continue;
            }

            $preferred = $payroll->employee_id
                ? $base.DIRECTORY_SEPARATOR.$payroll->employee_id
                : null;
            $directories = $preferred && is_dir($preferred) ? [$preferred] : [];
            if (strlen($targetKey) >= 10) {
                foreach (scandir($base) ?: [] as $entry) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }
                    $dir = $base.DIRECTORY_SEPARATOR.$entry;
                    if (is_dir($dir) && $dir !== $preferred) {
                        $directories[] = $dir;
                    }
                }
            }

            foreach ($directories as $dir) {
                foreach (scandir($dir) ?: [] as $entry) {
                    if (! str_ends_with(strtolower($entry), '.pdf')) {
                        continue;
                    }
                    if ($this->payrollNameKey($entry) !== $targetKey) {
                        continue;
                    }
                    $absolute = $dir.DIRECTORY_SEPARATOR.$entry;
                    if (is_file($absolute) && is_readable($absolute)) {
                        $this->rememberPayrollPath($payroll, $absolute);

                        return $absolute;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function payrollPathCandidates(Payroll $payroll): array
    {
        $candidates = [];
        $raw = urldecode(str_replace('\\', '/', (string) $payroll->file_path));
        $raw = ltrim($raw, '/');
        foreach (['storage/app/private/', 'storage/app/public/', 'storage/app/', 'app/private/', 'app/public/', 'app/'] as $prefix) {
            if ($raw !== '' && str_starts_with($raw, $prefix)) {
                $raw = substr($raw, strlen($prefix));
            }
        }
        if ($payroll->file_path) {
            $candidates[] = (string) $payroll->file_path;
        }
        if ($raw !== '') {
            $candidates[] = $raw;
        }
        if ($payroll->employee_id && $payroll->file_name) {
            $safe = $this->sanitizePayrollStorageFileName($payroll->file_name);
            $base = basename(str_replace('\\', '/', $payroll->file_name));
            $candidates[] = 'payrolls/'.$payroll->employee_id.'/'.$safe;
            $candidates[] = 'payrolls/'.$payroll->employee_id.'/'.$base;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * @return list<string>
     */
    private function payrollStorageRoots(): array
    {
        return array_values(array_unique(array_filter([
            storage_path('app'),
            storage_path('app/private'),
            storage_path('app/public'),
            public_path('storage'),
        ], 'is_dir')));
    }

    private function absolutePathIfReadable(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);
        $attempts = [];
        if ($path !== '' && ($path[0] === '/' || preg_match('/^[A-Za-z]:\//', $path))) {
            $attempts[] = $path;
        }
        $relative = ltrim($path, '/');
        foreach ($this->payrollStorageRoots() as $root) {
            $attempts[] = $root.'/'.$relative;
        }

        foreach ($attempts as $attempt) {
            if (is_file($attempt) && is_readable($attempt)) {
                return $attempt;
            }
        }

        return null;
    }

    private function rememberPayrollPath(Payroll $payroll, string $absolutePath): void
    {
        $normalized = str_replace('\\', '/', $absolutePath);
        foreach ($this->payrollStorageRoots() as $root) {
            $prefix = rtrim(str_replace('\\', '/', $root), '/').'/';
            if (! str_starts_with($normalized, $prefix)) {
                continue;
            }
            $relative = substr($normalized, strlen($prefix));
            if ($relative !== '' && $relative !== $payroll->file_path) {
                $payroll->update(['file_path' => $relative]);
            }

            return;
        }
    }

    private function payrollNameKey(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = Str::ascii(mb_strtolower($name));
        $name = preg_replace('/\.pdf$/', '', $name) ?? $name;
        $name = preg_replace('/[^a-z0-9]+/', '', $name) ?? '';

        return $name;
    }

    private function resolvePayrollPdfContent(Payroll $payroll): string
    {
        $absolute = $this->locatePayrollPdfPath($payroll);
        if ($absolute !== null) {
            $content = file_get_contents($absolute);

            return is_string($content) ? $content : '';
        }

        $stored = Payroll::withTrashed()->whereKey($payroll->id)->value('base64_content');
        if (is_string($stored) && str_starts_with($stored, '%PDF')) {
            return $stored;
        }

        return $this->decodePayrollBase64(is_string($stored) ? $stored : null);
    }

    private function decodePayrollBase64(?string $base64): string
    {
        if ($base64 === null || trim($base64) === '') {
            return '';
        }

        $base64 = trim($base64);
        if (preg_match('/^data:.*?;base64,(.*)$/is', $base64, $matches)) {
            $base64 = $matches[1];
        }
        $base64 = preg_replace('/\s+/', '', $base64);

        $decoded = base64_decode($base64, true);
        if ($decoded !== false && $decoded !== '') {
            return $decoded;
        }

        $decoded = base64_decode($base64, false);

        return ($decoded !== false && $decoded !== '') ? $decoded : '';
    }

    private function ensurePayrollFileOnDisk(Payroll $payroll, string $pdfContent): void
    {
        if (! $payroll->employee_id || $pdfContent === '') {
            return;
        }

        $disk = Storage::disk('local');
        if ($payroll->file_path && $disk->exists($payroll->file_path)) {
            return;
        }

        $dir = 'payrolls/' . $payroll->employee_id;
        $path = $dir . '/' . $this->sanitizePayrollStorageFileName($payroll->file_name ?? 'nomina.pdf');
        $disk->makeDirectory($dir);
        $disk->put($path, $pdfContent);
        $payroll->update(['file_path' => $path]);
    }
}
