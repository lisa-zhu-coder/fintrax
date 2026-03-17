<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.payroll_templates.manage');
    }

    public function index()
    {
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        $templates = EmailTemplate::where('company_id', $companyId)->orderBy('name')->get();
        return view('settings.email-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('settings.email-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'type' => 'required|in:payroll,document',
        ]);
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        $validated['company_id'] = $companyId;
        $template = EmailTemplate::create($validated);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'id' => $template->id, 'name' => $template->name, 'subject' => $template->subject, 'body' => $template->body]);
        }
        return redirect()->route('email-templates-settings.index')->with('success', 'Plantilla creada correctamente.');
    }

    public function edit(EmailTemplate $email_template)
    {
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        if ($email_template->company_id != $companyId) {
            abort(404);
        }
        return view('settings.email-templates.edit', ['template' => $email_template]);
    }

    public function update(Request $request, EmailTemplate $email_template)
    {
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        if ($email_template->company_id != $companyId) {
            abort(404);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'type' => 'required|in:payroll,document',
        ]);
        $email_template->update($validated);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'name' => $email_template->name, 'subject' => $email_template->subject, 'body' => $email_template->body]);
        }
        return redirect()->route('email-templates-settings.index')->with('success', 'Plantilla actualizada.');
    }

    public function destroy(EmailTemplate $email_template)
    {
        $companyId = session('company_id') ?? Auth::user()?->company_id;
        if ($email_template->company_id != $companyId) {
            abort(404);
        }
        $email_template->delete();
        return redirect()->route('email-templates-settings.index')->with('success', 'Plantilla eliminada.');
    }
}
