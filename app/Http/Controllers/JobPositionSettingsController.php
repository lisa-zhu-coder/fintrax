<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobPositionSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.job_positions.manage');
    }

    public function index()
    {
        $jobPositions = JobPosition::orderBy('sort_order')->orderBy('name')->get();

        return view('settings.job-positions.index', compact('jobPositions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('job_positions', 'name')->where(fn ($q) => $q->where('company_id', session('company_id'))),
            ],
        ], [
            'name.required' => 'Indica el nombre del puesto.',
            'name.unique' => 'Ya existe un puesto con ese nombre.',
        ]);

        $maxOrder = JobPosition::max('sort_order') ?? 0;
        JobPosition::create([
            'name' => trim($validated['name']),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('job-positions-settings.index')
            ->with('success', 'Puesto creado correctamente.');
    }

    public function update(Request $request, JobPosition $jobPosition)
    {
        $this->ensureCompany($jobPosition);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('job_positions', 'name')
                    ->where(fn ($q) => $q->where('company_id', session('company_id')))
                    ->ignore($jobPosition->id),
            ],
        ], ['name.unique' => 'Ya existe otro puesto con ese nombre.']);

        $name = trim($validated['name']);
        $jobPosition->update(['name' => $name]);
        Employee::where('job_position_id', $jobPosition->id)->update(['position' => $name]);

        return redirect()->route('job-positions-settings.index')
            ->with('success', 'Puesto actualizado. Las fichas vinculadas se han actualizado.');
    }

    public function destroy(JobPosition $jobPosition)
    {
        $this->ensureCompany($jobPosition);

        if (Employee::where('job_position_id', $jobPosition->id)->exists()) {
            return redirect()->route('job-positions-settings.index')
                ->with('error', 'No se puede eliminar: hay empleados con este puesto. Cambia primero el puesto en sus fichas.');
        }

        $jobPosition->delete();

        return redirect()->route('job-positions-settings.index')
            ->with('success', 'Puesto eliminado.');
    }

    private function ensureCompany(JobPosition $jobPosition): void
    {
        $companyId = session('company_id');
        if ($companyId === null || (int) $jobPosition->company_id !== (int) $companyId) {
            abort(403, 'No puedes modificar puestos de otra empresa.');
        }
    }
}
