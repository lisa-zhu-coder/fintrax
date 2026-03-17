<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payroll.view')->only(['view', 'pendingSend']);
        $this->middleware('permission:payroll.send')->only(['sendBulk']);
        $this->middleware('permission:payroll.create')->only(['assignEmployee']);
        $this->middleware('permission:payroll.delete')->only(['destroy', 'cancelPending']);
    }

    public function cancelPending(Request $request)
    {
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
        $ids = $request->session()->get('pending_payroll_ids', []);
        if (empty($ids)) {
            return redirect()->route('employees.index')->with('info', 'No hay nóminas pendientes de envío.');
        }
        $payrolls = Payroll::with('employee')->whereIn('id', $ids)->get();
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        $payrolls = $payrolls->filter(fn ($p) => $p->employee && $p->employee->company_id == $companyId);
        $templates = EmailTemplate::where('company_id', $companyId)->where('type', 'payroll')->orderBy('name')->get();
        $company = Company::find($companyId);
        $employees = Employee::where('company_id', $companyId)->orderBy('full_name')->get(['id', 'full_name', 'email']);
        return view('payroll.pending-send', compact('payrolls', 'templates', 'company', 'employees'));
    }

    public function sendBulk(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'exists:payrolls,id',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
        ]);
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->route('payroll.pending-send')->with('info', 'No se seleccionó ninguna nómina para enviar.');
        }
        $subject = $request->input('subject');
        $body = $request->input('body');
        $payrolls = Payroll::with('employee')->whereIn('id', $ids)->whereNull('sent_at')->get();
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        $payrolls = $payrolls->filter(fn ($p) => $p->employee && $p->employee->company_id == $companyId);
        $sent = 0;
        foreach ($payrolls as $payroll) {
            $email = $request->input('email_' . $payroll->id) ?: $payroll->employee->email;
            if (!$email) {
                continue;
            }
            $customFileName = $request->input('file_name_' . $payroll->id);
            if (is_string($customFileName) && trim($customFileName) !== '') {
                $payroll->update(['file_name' => trim($customFileName)]);
            }
            $this->sendPayrollEmail($payroll, $email, $subject, $body);
            $payroll->update(['sent_at' => now(), 'sent_by' => Auth::id()]);
            $sent++;
        }
        $request->session()->forget('pending_payroll_ids');
        return redirect()->route('employees.index')->with('success', $sent . ' nómina(s) enviada(s) correctamente.');
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
        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($to, $subject, $body, $path, $payroll, $fromAddress, $fromName) {
            $message->from($fromAddress, $fromName)->to($to)->subject($subject)->setBody($body, 'text/plain');
            if ($path) {
                $message->attach($path, ['as' => $payroll->file_name ?? 'nomina.pdf', 'mime' => 'application/pdf']);
            }
        });
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
