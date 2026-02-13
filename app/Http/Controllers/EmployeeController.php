<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:hr.employees.create')->only(['create', 'store']);
        $this->middleware('permission:hr.employees.edit')->only(['edit', 'update']);
        $this->middleware('permission:hr.employees.delete')->only(['destroy']);
        $this->middleware('permission:hr.employees.configure')->only(['uploadPayroll', 'uploadPayrollAuto']);
    }

    public function index()
    {
        $this->syncStoresFromBusinesses();

        $user = Auth::user();
        $canViewStore = $user->hasPermission('hr.employees.view_store');
        $canViewOwn = $user->hasPermission('hr.employees.view_own');
        if (!$canViewStore && !$canViewOwn) {
            abort(403, 'No tienes permiso para ver empleados.');
        }

        $query = Employee::with(['user', 'stores']);

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            // Sin restricción por tienda a nivel de query (ven toda la empresa)
        } elseif ($canViewStore) {
            // Ver todas las fichas de la tienda asignada
            $enforcedStoreId = $user->getEnforcedStoreId();
            if ($enforcedStoreId !== null) {
                $query->whereHas('stores', function ($q) use ($enforcedStoreId) {
                    $q->where('stores.id', $enforcedStoreId);
                });
            }
        } else {
            // Solo su ficha
            $query->where('user_id', $user->id);
        }

        $employees = $query->get();
        $totalStores = Store::count();
        return view('employees.index', compact('employees', 'totalStores'));
    }

    public function create()
    {
        $this->syncStoresFromBusinesses();

        $stores = $this->storesForCurrentUser();
        $users = User::with('role')->get();
        $roles = Role::all();
        return view('employees.create', compact('stores', 'users', 'roles'));
    }

    public function store(Request $request)
    {
        // Sincronizar stores antes de validar
        $this->syncStoresFromBusinesses();
        
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'dni' => 'required|string|max:255',
                'phone' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'street' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:10',
                'city' => 'nullable|string|max:255',
                'position' => 'required|string|max:255',
                'hours' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'social_security' => 'nullable|string|max:255',
                'iban' => 'nullable|string|max:255',
                'gross_salary' => 'nullable|numeric|min:0',
                'net_salary' => 'nullable|numeric|min:0',
                'shirt_size' => 'nullable|string|max:50',
                'blazer_size' => 'nullable|string|max:50',
                'pants_size' => 'nullable|string|max:50',
                'user_id' => 'nullable|exists:users,id',
                'store_ids' => 'required|array|min:1',
                'store_ids.*' => 'exists:stores,id',
            ], [
                'full_name.required' => 'El nombre completo es obligatorio.',
                'dni.required' => 'El DNI es obligatorio.',
                'position.required' => 'El puesto es obligatorio.',
                'hours.required' => 'Las horas contratadas son obligatorias.',
                'start_date.required' => 'La fecha de inicio es obligatoria.',
                'store_ids.required' => 'Debe seleccionar al menos una tienda.',
                'store_ids.min' => 'Debe seleccionar al menos una tienda.',
                'store_ids.*.exists' => 'Una o más tiendas seleccionadas no existen. Por favor, recarga la página e intenta de nuevo.',
            ]);

            // Convertir user_id vacío a null
            if (empty($validated['user_id'])) {
                $validated['user_id'] = null;
            }

            // Convertir end_date vacío a null para que quede vacío si no se rellena
            if (empty($validated['end_date'])) {
                $validated['end_date'] = null;
            }

            // MySQL no acepta '' en columnas decimal: convertir vacíos a null
            foreach (['gross_salary', 'net_salary'] as $key) {
                if (isset($validated[$key]) && $validated[$key] === '') {
                    $validated[$key] = null;
                }
            }
            if (isset($validated['hours']) && $validated['hours'] === '') {
                $validated['hours'] = 0;
            }

            $user = Auth::user();
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                $enforcedStoreId = $user->getEnforcedStoreId();
                if ($enforcedStoreId !== null) {
                    $validated['store_ids'] = array_values(array_unique(array_merge($validated['store_ids'], [$enforcedStoreId])));
                    $validated['store_ids'] = array_filter($validated['store_ids'], fn ($id) => (int) $id === $enforcedStoreId);
                }
            }

            // Asegurar company_id: sesión o, si no hay, de la primera tienda seleccionada (evita empleados huérfanos en producción)
            $companyId = session('company_id');
            if ($companyId === null && !empty($validated['store_ids'])) {
                $firstStore = Store::withoutGlobalScope(\App\Models\Scopes\BelongsToCompanyScope::class)
                    ->find($validated['store_ids'][0]);
                if ($firstStore && $firstStore->company_id !== null) {
                    $companyId = $firstStore->company_id;
                }
            }
            $validated['company_id'] = $companyId;

            $storeIds = $validated['store_ids'];
            unset($validated['store_ids']);

            $employee = Employee::create($validated);
            $employee->stores()->sync($storeIds);

            Log::info('Employee created', [
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'full_name' => $employee->full_name,
            ]);

            return redirect()->route('employees.index')->with('success', 'Empleado creado correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Employee create validation failed', [
                'errors' => $e->errors(),
                'input_store_ids' => $request->input('store_ids'),
            ]);
            // Devolver la vista en la misma respuesta (sin redirect) para que los datos y errores se muestren
            // aunque la sesión no persista en producción
            $this->syncStoresFromBusinesses();
            $stores = $this->storesForCurrentUser();
            $users = User::with('role')->get();
            $roles = Role::all();
            $oldInput = $request->except('password', '_token');
            return view('employees.create', compact('stores', 'users', 'roles', 'oldInput'))
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Employee create failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->syncStoresFromBusinesses();
            $stores = $this->storesForCurrentUser();
            $users = User::with('role')->get();
            $roles = Role::all();
            $oldInput = $request->except('password', '_token');
            return view('employees.create', compact('stores', 'users', 'roles', 'oldInput'))
                ->withErrors(['error' => 'Error al crear el empleado: ' . $e->getMessage()]);
        }
    }

    public function show(Employee $employee)
    {
        $user = Auth::user();
        $canViewStore = $user->hasPermission('hr.employees.view_store');
        $canViewOwn = $user->hasPermission('hr.employees.view_own');
        if (!$canViewStore && !$canViewOwn) {
            abort(403, 'No tienes permiso para ver fichas de empleado.');
        }

        $isOwn = (int) $employee->user_id === (int) $user->id;
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            // Acceso total
        } elseif ($isOwn && $canViewOwn) {
            // Ve solo su ficha y es la suya
        } elseif ($canViewStore) {
            $enforcedStoreId = $user->getEnforcedStoreId();
            if ($enforcedStoreId === null || !$employee->stores->contains('id', $enforcedStoreId)) {
                abort(403, 'No tienes acceso a los datos de este empleado.');
            }
        } else {
            abort(403, 'Solo puedes ver tu propia ficha de empleado.');
        }

        $employee->load(['user.role', 'stores', 'payrolls']);
        $totalStores = Store::count();
        return view('employees.show', compact('employee', 'totalStores'));
    }

    public function edit(Employee $employee)
    {
        $user = Auth::user();
        if (!$user->hasPermission('hr.employees.edit')) {
            abort(403, 'No tienes permiso para editar empleados.');
        }
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $enforcedStoreId = $user->getEnforcedStoreId();
            if ($enforcedStoreId !== null && !$employee->stores->contains('id', $enforcedStoreId)) {
                abort(403, 'No tienes acceso a los datos de este empleado.');
            }
        }

        $this->syncStoresFromBusinesses();
        $stores = $this->storesForCurrentUser();
        $users = User::with('role')->get();
        $roles = Role::all();
        $employee->load('stores');
        return view('employees.edit', compact('employee', 'stores', 'users', 'roles'));
    }

    public function update(Request $request, Employee $employee)
    {
        $user = Auth::user();
        if (!$user->hasPermission('hr.employees.edit')) {
            abort(403, 'No tienes permiso para editar empleados.');
        }
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $enforcedStoreId = $user->getEnforcedStoreId();
            if ($enforcedStoreId !== null && !$employee->stores->contains('id', $enforcedStoreId)) {
                abort(403, 'No tienes acceso a los datos de este empleado.');
            }
        }

        // Sincronizar stores antes de validar
        $this->syncStoresFromBusinesses();
        
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'dni' => 'required|string|max:255',
                'phone' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'street' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:10',
                'city' => 'nullable|string|max:255',
                'position' => 'required|string|max:255',
                'hours' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'social_security' => 'nullable|string|max:255',
                'iban' => 'nullable|string|max:255',
                'gross_salary' => 'nullable|numeric|min:0',
                'net_salary' => 'nullable|numeric|min:0',
                'shirt_size' => 'nullable|string|max:50',
                'blazer_size' => 'nullable|string|max:50',
                'pants_size' => 'nullable|string|max:50',
                'user_id' => 'nullable|exists:users,id',
                'store_ids' => 'required|array|min:1',
                'store_ids.*' => 'exists:stores,id',
            ], [
                'full_name.required' => 'El nombre completo es obligatorio.',
                'dni.required' => 'El DNI es obligatorio.',
                'position.required' => 'El puesto es obligatorio.',
                'hours.required' => 'Las horas contratadas son obligatorias.',
                'start_date.required' => 'La fecha de inicio es obligatoria.',
                'store_ids.required' => 'Debe seleccionar al menos una tienda.',
                'store_ids.min' => 'Debe seleccionar al menos una tienda.',
                'store_ids.*.exists' => 'Una o más tiendas seleccionadas no existen. Por favor, recarga la página e intenta de nuevo.',
            ]);

            // Convertir user_id vacío a null
            if (empty($validated['user_id'])) {
                $validated['user_id'] = null;
            }

            // Convertir end_date vacío a null para que quede vacío si no se rellena
            if (empty($validated['end_date'])) {
                $validated['end_date'] = null;
            }

            // MySQL no acepta '' en columnas decimal: convertir vacíos a null
            foreach (['gross_salary', 'net_salary'] as $key) {
                if (isset($validated[$key]) && $validated[$key] === '') {
                    $validated[$key] = null;
                }
            }
            if (isset($validated['hours']) && $validated['hours'] === '') {
                $validated['hours'] = 0;
            }

            $user = Auth::user();
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                $enforcedStoreId = $user->getEnforcedStoreId();
                if ($enforcedStoreId !== null) {
                    $validated['store_ids'] = array_values(array_filter($validated['store_ids'], fn ($id) => (int) $id === $enforcedStoreId));
                    if (empty($validated['store_ids'])) {
                        $validated['store_ids'] = [$enforcedStoreId];
                    }
                }
            }

            $employee->update($validated);
            $employee->stores()->sync($validated['store_ids']);

            return redirect()->route('employees.index')->with('success', 'Empleado actualizado correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar el empleado: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Crear un usuario nuevo desde el formulario de empleado (AJAX).
     * Devuelve JSON con id, name y role_name para añadirlo al select.
     */
    public function storeQuickUser(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        if (empty($validated['store_id'])) {
            $validated['store_id'] = null;
        }
        if (array_key_exists('email', $validated) && $validated['email'] === '') {
            $validated['email'] = null;
        }

        $user = User::create($validated);
        $user->load('role');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'role_name' => $user->role->name,
        ]);
    }

    public function destroy(Employee $employee)
    {
        $user = Auth::user();
        if ($user->isEmployee()) {
            abort(403, 'No tienes permiso para eliminar empleados.');
        }
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $enforcedStoreId = $user->getEnforcedStoreId();
            if ($enforcedStoreId !== null && !$employee->stores->contains('id', $enforcedStoreId)) {
                abort(403, 'No tienes acceso a los datos de este empleado.');
            }
        }
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Empleado eliminado correctamente.');
    }

    public function uploadPayroll(Request $request, Employee $employee)
    {
        $request->validate([
            'payrolls' => 'required|array',
            'payrolls.*' => 'file|mimes:pdf|max:10240',
        ]);

        foreach ($request->file('payrolls') as $file) {
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            
            // Procesar PDF para extraer información
            $pdfData = $this->processPayrollPDF($file);
            
            // Usar mes y año actuales si no se puede extraer del archivo
            $date = $pdfData['date'] ?? now();
            // Asegurar que la fecha sea el primer día del mes actual
            if ($date->format('Y-m') !== now()->format('Y-m')) {
                $date = now()->startOfMonth();
            }
            
            $fileName = $this->generatePayrollFileName($employee->full_name, $date, $file->getClientOriginalName());
            
            // Buscar coincidencias con el empleado
            $matchedBy = $this->matchEmployeeInPDF($pdfData['text'], $employee);
            
            $employee->payrolls()->create([
                'file_name' => $fileName,
                'date' => $date,
                'base64_content' => $base64,
                'matched_by' => $matchedBy,
            ]);
        }

        return back()->with('success', 'Nóminas subidas correctamente.');
    }

    public function uploadPayrollAuto(Request $request)
    {
        $request->validate([
            'payroll' => 'required|file|mimes:pdf|max:10240',
        ]);

        $file = $request->file('payroll');
        $base64 = base64_encode(file_get_contents($file->getRealPath()));
        
        // Procesar PDF para extraer información
        $pdfData = $this->processPayrollPDF($file);
        
        // Extraer texto del nombre del archivo para identificar al empleado
        $fileName = $file->getClientOriginalName();
        $fileNameLower = mb_strtolower($fileName);
        
        // Buscar empleado por nombre, DNI o número de seguridad social
        $employee = $this->findEmployeeByFile($fileName, $pdfData['text'] ?? '');
        
        if (!$employee) {
            return back()->withErrors(['payroll' => 'No se pudo identificar al empleado. Por favor, asegúrate de que el nombre del archivo contenga el nombre del empleado, DNI o número de seguridad social.']);
        }
        
        // Usar mes y año actuales
        $date = now()->startOfMonth();
        $fileName = $this->generatePayrollFileName($employee->full_name, $date, $fileName);
        
        // Buscar coincidencias con el empleado
        $matchedBy = $this->matchEmployeeInPDF($pdfData['text'] ?? '', $employee);
        
        $employee->payrolls()->create([
            'file_name' => $fileName,
            'date' => $date,
            'base64_content' => $base64,
            'matched_by' => $matchedBy,
        ]);

        return redirect()->route('employees.show', $employee)->with('success', 'Nómina subida correctamente para ' . $employee->full_name . '.');
    }

    private function findEmployeeByFile($fileName, $pdfText = '')
    {
        $fileNameLower = mb_strtolower($fileName);
        $allText = mb_strtolower($fileName . ' ' . $pdfText);
        
        $employees = Employee::all();
        
        foreach ($employees as $employee) {
            // Buscar por DNI
            if ($employee->dni) {
                $dniNormalized = preg_replace('/[-\s]/', '', mb_strtoupper($employee->dni));
                $dniLower = mb_strtolower($dniNormalized);
                if (mb_strpos($allText, $dniLower) !== false) {
                    return $employee;
                }
            }
            
            // Buscar por número de seguridad social
            if ($employee->social_security) {
                $ssNormalized = preg_replace('/[-\s]/', '', $employee->social_security);
                $ssLower = mb_strtolower($ssNormalized);
                if (mb_strpos($allText, $ssLower) !== false) {
                    return $employee;
                }
            }
            
            // Buscar por nombre (al menos 2 partes del nombre)
            if ($employee->full_name) {
                $nameParts = array_filter(explode(' ', mb_strtolower($employee->full_name)), function($part) {
                    return mb_strlen(trim($part)) > 2;
                });
                
                $matches = 0;
                foreach ($nameParts as $part) {
                    $part = trim($part);
                    if (mb_strlen($part) > 2 && mb_strpos($allText, $part) !== false) {
                        $matches++;
                    }
                }
                
                if ($matches >= 2) {
                    return $employee;
                }
            }
        }
        
        return null;
    }

    private function processPayrollPDF($file)
    {
        try {
            // Extraer fecha del nombre del archivo
            // En producción, se puede usar una librería como smalot/pdfparser para extraer texto
            $fileName = $file->getClientOriginalName();
            $date = $this->extractDateFromFileName($fileName);
            
            return [
                'text' => '', // Se puede implementar con smalot/pdfparser: composer require smalot/pdfparser
                'date' => $date,
            ];
        } catch (\Exception $e) {
            return [
                'text' => '',
                'date' => now(),
            ];
        }
    }

    private function extractDateFromFileName($fileName)
    {
        $months = [
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
        ];
        
        $fileNameLower = mb_strtolower($fileName);
        
        foreach ($months as $index => $month) {
            if (mb_strpos($fileNameLower, $month) !== false) {
                // Buscar año en el nombre
                if (preg_match('/\b(20\d{2})\b/', $fileName, $matches)) {
                    $year = $matches[1];
                    return \Carbon\Carbon::createFromDate($year, $index + 1, 1);
                }
            }
        }
        
        // Buscar formato de fecha en el nombre (YYYY-MM, YYYY/MM, etc.)
        if (preg_match('/(\d{4})[\/\-](\d{1,2})/', $fileName, $matches)) {
            return \Carbon\Carbon::createFromDate($matches[1], $matches[2], 1);
        }
        
        return now();
    }

    private function generatePayrollFileName($employeeName, $date, $originalName = '')
    {
        $months = [
            'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
            'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'
        ];
        
        $month = $months[$date->month - 1] ?? $months[now()->month - 1];
        $year = $date->year ?? now()->year;
        
        $normalizedName = mb_strtoupper($employeeName);
        // Normalizar nombre: quitar acentos y caracteres especiales
        if (class_exists('\Transliterator')) {
            $normalizedName = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')->transliterate($normalizedName);
        } else {
            // Fallback si Transliterator no está disponible
            $normalizedName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizedName);
        }
        $normalizedName = preg_replace('/[^A-Z0-9\s]/', '', $normalizedName);
        $normalizedName = preg_replace('/\s+/', '_', trim($normalizedName));
        
        return "{$normalizedName}_{$month}_{$year}.pdf";
    }

    private function matchEmployeeInPDF($pdfText, $employee)
    {
        if (empty($pdfText)) {
            return 'manual';
        }
        
        $normalizedText = mb_strtolower($pdfText);
        
        // Buscar por DNI
        if ($employee->dni) {
            $dniNormalized = preg_replace('/[-\s]/', '', mb_strtoupper($employee->dni));
            if (mb_strpos($normalizedText, mb_strtolower($dniNormalized)) !== false) {
                return 'dni';
            }
        }
        
        // Buscar por Seguridad Social
        if ($employee->social_security) {
            $ssNormalized = preg_replace('/[-\s]/', '', $employee->social_security);
            if (mb_strpos($normalizedText, mb_strtolower($ssNormalized)) !== false) {
                return 'social_security';
            }
        }
        
        // Buscar por nombre (al menos 2 partes del nombre)
        if ($employee->full_name) {
            $nameParts = array_filter(explode(' ', mb_strtolower($employee->full_name)), function($part) {
                return mb_strlen(trim($part)) > 2;
            });
            
            $matches = 0;
            foreach ($nameParts as $part) {
                $part = trim($part);
                if (mb_strlen($part) > 2 && mb_strpos($normalizedText, $part) !== false) {
                    $matches++;
                }
            }
            
            if ($matches >= 2) {
                return 'name';
            }
        }
        
        return 'manual';
    }
}
