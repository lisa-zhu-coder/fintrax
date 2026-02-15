<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanySelectController extends Controller
{
    /**
     * Mostrar la pantalla de selección de empresa.
     * Super_admin: ve todas las empresas. Otros: solo si tienen acceso a más de una (company_user).
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            $companies = Company::orderBy('name')
                ->get()
                ->unique('id')
                ->values();
            $archivedCompanies = Company::withoutGlobalScopes()
                ->onlyTrashed()
                ->orderBy('name')
                ->get();
            return view('company.select', compact('companies', 'archivedCompanies'));
        }

        $companyIds = $user->getCompanyAccessCompanyIds();
        if (count($companyIds) <= 1) {
            return redirect()->route('dashboard');
        }

        $companies = Company::whereIn('id', $companyIds)->orderBy('name')->get();
        $archivedCompanies = collect();

        return view('company.select', compact('companies', 'archivedCompanies'));
    }

    /**
     * Cambiar a una empresa específica (guardar en sesión).
     * Super_admin: cualquier empresa. Otros: solo empresas a las que tienen acceso (company_user).
     */
    public function switch(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $companyId = (int) $request->company_id;

        if (!$user->isSuperAdmin()) {
            $allowedIds = $user->getCompanyAccessCompanyIds();
            if (count($allowedIds) === 0) {
                $allowedIds = $user->company_id ? [$user->company_id] : [];
            }
            if (!in_array($companyId, $allowedIds, true)) {
                return redirect()->route('dashboard')
                    ->with('error', 'No tienes acceso a esa empresa.');
            }
        }

        session(['company_id' => $companyId]);
        $company = Company::find($companyId);

        return redirect()->route('dashboard')
            ->with('success', 'Has entrado a la empresa: ' . $company->name);
    }

    /**
     * Crear una nueva empresa (solo super_admin).
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Solo super_admin puede crear empresas
        if (!$user->isSuperAdmin()) {
            return redirect()->route('dashboard')
                ->with('error', 'No tienes permiso para crear empresas.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cif' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $company = Company::create($validated);

        // Entrar automáticamente a la nueva empresa
        session(['company_id' => $company->id]);

        return redirect()->route('dashboard')
            ->with('success', 'Empresa "' . $company->name . '" creada correctamente.');
    }

    /**
     * Salir de la empresa actual y volver a la pantalla de selección (solo super_admin o quien tenga varias empresas).
     */
    public function exit()
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin()) {
            $companyIds = $user->getCompanyAccessCompanyIds();
            if (count($companyIds) <= 1) {
                return redirect()->route('dashboard')
                    ->with('error', 'No tienes permiso para cambiar de empresa.');
            }
        }

        session()->forget('company_id');

        return redirect()->route('company.select');
    }

    /**
     * Archivar empresa (soft delete). La empresa y sus datos permanecen en la base de datos.
     */
    public function archive(Company $company)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para archivar empresas.');
        }

        // Si la empresa archivada es la actual en sesión, salir
        if (session('company_id') == $company->id) {
            session()->forget('company_id');
        }

        $company->delete();

        return redirect()->route('company.select')
            ->with('success', 'Empresa "' . $company->name . '" archivada correctamente. Puedes recuperarla desde la sección de empresas archivadas.');
    }

    /**
     * Recuperar una empresa archivada.
     */
    public function restore(int $company)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para recuperar empresas.');
        }

        $company = Company::withoutGlobalScopes()->withTrashed()->findOrFail($company);
        $company->restore();

        return redirect()->route('company.select')
            ->with('success', 'Empresa "' . $company->name . '" recuperada correctamente.');
    }

    /**
     * Eliminar definitivamente una empresa archivada (y todos sus datos en cascada).
     */
    public function forceDelete(int $company)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para eliminar empresas.');
        }

        $company = Company::withoutGlobalScopes()->withTrashed()->findOrFail($company);
        $name = $company->name;

        // Si estaba en sesión, salir
        if (session('company_id') == $company->id) {
            session()->forget('company_id');
        }

        $company->forceDelete();

        return redirect()->route('company.select')
            ->with('success', 'Empresa "' . $name . '" eliminada definitivamente.');
    }
}
