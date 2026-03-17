<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser as PdfParser;

class EmployeeController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:hr.employees.create')->only(['create', 'store', 'storeQuickUser']);
        $this->middleware('permission:hr.employees.edit')->only(['edit', 'update']);
        $this->middleware('permission:hr.employees.delete')->only(['destroy']);
        $this->middleware('permission:hr.employees.configure')->only(['uploadPayroll', 'uploadPayrollAuto']);
        $this->middleware('permission:rrhh.documents.create')->only(['storeDocument']);
        $this->middleware('permission:rrhh.documents.delete')->only(['destroyDocument']);
        $this->middleware('permission:rrhh.documents.view')->only(['downloadDocument']);
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

    /**
     * Roles que el usuario actual puede asignar al crear una cuenta (solo roles de su mismo nivel o inferior).
     */
    private function rolesAllowedForNewUser()
    {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return Role::orderBy('level')->get();
        }
        $effectiveRole = $user->getEffectiveRole();
        if (!$effectiveRole) {
            return Role::orderBy('level')->get();
        }
        return Role::where('level', '>=', $effectiveRole->level)->orderBy('level')->get();
    }

    public function create()
    {
        $this->syncStoresFromBusinesses();

        $stores = $this->storesForCurrentUser();
        $users = User::with('role')->get();
        $roles = $this->rolesAllowedForNewUser();
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

            // Solo permitir salarios si el usuario tiene permiso (en creación solo view_salary_store)
            if (!$user->hasPermission('hr.employees.view_salary_store')) {
                unset($validated['gross_salary'], $validated['net_salary']);
            }

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
            $roles = $this->rolesAllowedForNewUser();
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
            $roles = $this->rolesAllowedForNewUser();
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

        $employee->load(['user.role', 'stores', 'payrolls', 'documents']);
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
        $roles = $this->rolesAllowedForNewUser();
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

            // Solo permitir actualizar salarios: view_salary_store (todos) o view_salary_own (solo su ficha)
            $user = Auth::user();
            $canSetSalary = $user->hasPermission('hr.employees.view_salary_store')
                || ($user->hasPermission('hr.employees.view_salary_own') && $employee->user_id === $user->id);
            if (!$canSetSalary) {
                unset($validated['gross_salary'], $validated['net_salary']);
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
        try {
            $companyId = session('company_id');
            $validated = $request->validate([
                'username' => 'required|string|max:255|unique:users,username',
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'password' => 'required|string|min:6',
                'role_id' => 'required|exists:roles,id',
                'store_id' => 'nullable|exists:stores,id',
            ]);

            $effectiveRole = Auth::user()->getEffectiveRole();
            if (!Auth::user()->isSuperAdmin() && $effectiveRole) {
                $selectedRole = Role::find($validated['role_id']);
                if ($selectedRole && $selectedRole->level < $effectiveRole->level) {
                    return response()->json([
                        'message' => 'No puedes asignar un rol de mayor nivel que el tuyo.',
                    ], 422);
                }
            }

            if (!empty($validated['store_id']) && $companyId) {
                $store = Store::withoutGlobalScopes()->find($validated['store_id']);
                if (!$store || (int) $store->company_id !== (int) $companyId) {
                    return response()->json(['message' => 'La tienda seleccionada no pertenece a la empresa actual.'], 422);
                }
            }

            $validated['password'] = Hash::make($validated['password']);
            if (empty($validated['store_id'])) {
                $validated['store_id'] = null;
            }
            if (array_key_exists('email', $validated) && $validated['email'] === '') {
                $validated['email'] = null;
            }

            // Asignar company_id de la sesión para que el usuario pertenezca a la empresa actual
            $validated['company_id'] = $companyId;

            $user = User::create($validated);
            $user->load('role');

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'role_name' => $user->role->name,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en storeQuickUser: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['password']),
            ]);
            return response()->json([
                'message' => 'Error al crear el usuario: ' . $e->getMessage(),
            ], 500);
        }
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

    public function storeDocument(Request $request, Employee $employee)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $enforcedStoreId = $user->getEnforcedStoreId();
            if ($enforcedStoreId !== null && !$employee->stores->contains('id', $enforcedStoreId)) {
                abort(403, 'No tienes acceso a este empleado.');
            }
        }
        $validated = $request->validate([
            'type' => 'required|in:contrato,anexo,DNI,certificado,otros',
            'title' => 'required|string|max:255',
            'document_date' => 'required|date',
            'file' => 'required|file|mimes:pdf|max:20480',
        ]);
        $file = $request->file('file');
        $dir = 'employee_documents/' . $employee->id;
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $path = $file->storeAs($dir, $filename, 'local');
        $employee->documents()->create([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'document_date' => $validated['document_date'],
            'file_path' => $path,
            'uploaded_by' => $user->id,
        ]);
        return redirect()->route('employees.show', $employee)->with('success', 'Documento subido correctamente.');
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document)
    {
        if ($document->employee_id !== $employee->id) {
            abort(404);
        }
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $enforcedStoreId = $user->getEnforcedStoreId();
            if ($enforcedStoreId !== null && !$employee->stores->contains('id', $enforcedStoreId)) {
                abort(403, 'No tienes acceso a este empleado.');
            }
        }
        Storage::disk('local')->delete($document->file_path);
        $document->delete();
        return redirect()->route('employees.show', $employee)->with('success', 'Documento eliminado.');
    }

    public function downloadDocument(Employee $employee, EmployeeDocument $document)
    {
        if ($document->employee_id !== $employee->id) {
            abort(404);
        }
        $user = Auth::user();
        if (!$user->hasPermission('rrhh.documents.view')) {
            abort(403);
        }
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $enforcedStoreId = $user->getEnforcedStoreId();
            if ($enforcedStoreId !== null && !$employee->stores->contains('id', $enforcedStoreId)) {
                abort(403, 'No tienes acceso a este empleado.');
            }
        }
        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }
        return Storage::disk('local')->download(
            $document->file_path,
            $document->title . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function uploadPayroll(Request $request, Employee $employee)
    {
        $request->validate([
            'payrolls' => 'required|array',
            'payrolls.*' => 'file|mimes:pdf|max:10240',
        ]);

        $createdIds = [];
        $errors = [];
        foreach ($request->file('payrolls') as $file) {
            $pdfData = $this->processPayrollPDF($file);
            $date = $pdfData['date'] ?? now();
            if ($date->format('Y-m') !== now()->format('Y-m')) {
                $date = now()->copy()->startOfMonth();
            }
            $month = (int) $date->month;
            $year = (int) $date->year;

            if ($employee->payrolls()->where('month', $month)->where('year', $year)->exists()) {
                $errors[] = "Ya existe una nómina para {$this->getSpanishMonthName($month)} {$year}. No se sube el archivo duplicado.";
                continue;
            }

            $fileName = $this->generatePayrollFileName($employee->full_name, $date);
            $dir = 'payrolls/' . $employee->id;
            $path = $file->storeAs($dir, $fileName, 'local');

            $matchedBy = $this->matchEmployeeInPDF($pdfData['text'] ?? '', $employee);
            $payroll = $employee->payrolls()->create([
                'file_name' => $fileName,
                'date' => $date,
                'month' => $month,
                'year' => $year,
                'file_path' => $path,
                'base64_content' => null,
                'matched_by' => $matchedBy,
            ]);
            $createdIds[] = $payroll->id;
        }

        if (!empty($errors)) {
            $request->session()->flash('warning', implode(' ', $errors));
        }
        if (empty($createdIds)) {
            return back()->with('error', empty($errors) ? 'No se pudo procesar ningún archivo.' : 'No se subió ninguna nómina nueva.');
        }
        $request->session()->flash('success', count($createdIds) . ' nómina(s) subida(s) correctamente.');
        return redirect()->route('payroll.pending-send')->with('pending_payroll_ids', $createdIds);
    }

    public function uploadPayrollAuto(Request $request)
    {
        $request->validate([
            'payroll' => 'required|file|mimes:pdf|max:51200', // 50 MB para PDFs con muchas nóminas
        ]);

        try {
            $file = $request->file('payroll');
            $path = $file->getRealPath();
            $originalFileName = $file->getClientOriginalName();
            $dateFromFileName = $this->extractDateFromFileName($originalFileName);

            // Obtener texto por página (Smalot) para identificar empleado en cada hoja
            $pagesText = $this->getPayrollPdfTextPerPage($path);
            if (empty($pagesText)) {
                return back()->withErrors(['payroll' => 'No se pudo leer el PDF. Comprueba que el archivo no esté corrupto o protegido.']);
            }

            // Obtener número de páginas (FPDI) para extraer cada página como PDF
            $pageCount = $this->getPdfPageCount($path);
            if ($pageCount <= 0) {
                return back()->withErrors(['payroll' => 'No se pudo procesar el PDF.']);
            }

            $saved = 0;
            $createdIds = [];
            $failedPages = [];
            $lastEmployee = null;

            for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                $pageText = $pagesText[$pageNum - 1] ?? '';
                $employee = $this->findEmployeeByFile($originalFileName, $pageText);
                if (!$employee) {
                    $failedPages[] = $pageNum;
                    continue;
                }

                $singlePageBase64 = $this->extractSinglePageAsBase64($path, $pageNum);
                if ($singlePageBase64 === null) {
                    $failedPages[] = $pageNum;
                    continue;
                }

                $date = $this->extractDateFromPageText($pageText) ?? $dateFromFileName;
                if (!($date instanceof \Carbon\Carbon)) {
                    $date = $dateFromFileName;
                }
                $month = (int) $date->month;
                $year = (int) $date->year;
                $fileName = $this->generatePayrollFileName($employee->full_name, $date, $originalFileName . '_p' . $pageNum);
                $matchedBy = $this->matchEmployeeInPDF($pageText, $employee);

                $payroll = $employee->payrolls()->create([
                    'file_name' => $fileName,
                    'date' => $date,
                    'month' => $month,
                    'year' => $year,
                    'base64_content' => $singlePageBase64,
                    'matched_by' => $matchedBy,
                ]);
                $createdIds[] = $payroll->id;
                $saved++;
                $lastEmployee = $employee;
            }

            if ($saved === 0) {
                $message = 'No se pudo identificar a ningún empleado en el PDF. ';
                if (!empty($failedPages)) {
                    $message .= 'Asegúrate de que cada nómina contenga el nombre, DNI o número de la seguridad social del empleado.';
                }
                return back()->withErrors(['payroll' => $message]);
            }

            $message = $saved === 1
                ? '1 nómina guardada en la ficha de ' . $lastEmployee->full_name . '.'
                : $saved . ' nóminas guardadas en las fichas de los empleados correspondientes.';
            if (!empty($failedPages)) {
                $message .= ' No se pudo asignar la(s) página(s) ' . implode(', ', $failedPages) . ' (empleado no identificado).';
            }

            return redirect()->route('payroll.pending-send')->with('success', $message)->with('pending_payroll_ids', $createdIds);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error subiendo nómina(s)', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()->withErrors(['payroll' => 'Error al procesar el PDF. Comprueba que el archivo sea válido y no esté protegido. Si el error continúa, contacta con soporte.']);
        }
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

    /**
     * Devuelve el texto de cada página del PDF (índice 0 = página 1).
     */
    private function getPayrollPdfTextPerPage(string $path): array
    {
        $texts = [];
        try {
            if (!class_exists(PdfParser::class)) {
                return $texts;
            }
            $parser = new PdfParser();
            $document = $parser->parseFile($path);
            $pages = $document->getPages();
            foreach ($pages as $page) {
                $texts[] = $page->getText() ?? '';
            }
        } catch (\Exception $e) {
            Log::warning('Error extrayendo texto del PDF de nómina: ' . $e->getMessage());
        }
        return $texts;
    }

    /**
     * Número de páginas del PDF usando FPDI.
     */
    private function getPdfPageCount(string $path): int
    {
        try {
            $fpdi = new Fpdi();
            return $fpdi->setSourceFile($path);
        } catch (\Exception $e) {
            Log::warning('Error leyendo número de páginas del PDF: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Extrae una sola página del PDF como contenido base64.
     */
    private function extractSinglePageAsBase64(string $path, int $pageNumber): ?string
    {
        try {
            $fpdi = new Fpdi();
            $fpdi->setSourceFile($path);
            $tplId = $fpdi->importPage($pageNumber);
            $fpdi->AddPage();
            $fpdi->useTemplate($tplId, 0, 0, null, null, true);
            $pdfString = $fpdi->Output('S', '');
            return base64_encode($pdfString);
        } catch (\Exception $e) {
            Log::warning('Error extrayendo página ' . $pageNumber . ' del PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Intenta extraer la fecha de nómina del texto de una página (periodo, nómina, etc.).
     */
    private function extractDateFromPageText(string $text): ?\Carbon\Carbon
    {
        if (trim($text) === '') {
            return null;
        }
        $patterns = [
            '/periodo[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/iu',
            '/n[oó]mina[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/iu',
            '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/',
            '/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                if (strlen($m[1] ?? '') === 4) {
                    $year = (int) $m[1];
                    $month = (int) ($m[2] ?? 1);
                    $day = (int) ($m[3] ?? 1);
                } else {
                    $day = (int) ($m[1] ?? 1);
                    $month = (int) ($m[2] ?? 1);
                    $year = (int) ($m[3] ?? now()->year);
                }
                if ($month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100) {
                    return \Carbon\Carbon::createFromDate($year, $month, min($day, 28));
                }
            }
        }
        return null;
    }

    private function processPayrollPDF($file)
    {
        try {
            $fileName = $file->getClientOriginalName();
            $date = $this->extractDateFromFileName($fileName);
            $text = '';
            if (class_exists(PdfParser::class)) {
                try {
                    $parser = new PdfParser();
                    $document = $parser->parseFile($file->getRealPath());
                    $text = $document->getText() ?? '';
                } catch (\Exception $e) {
                    // Ignorar
                }
            }
            return [
                'text' => $text,
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

    /** Mes en español con primera letra en mayúscula (ej. Marzo) */
    private function getSpanishMonthName(int $month): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        return $months[$month] ?? 'Enero';
    }

    /** Nombre automático: Nomina {Nombre completo} {Mes texto} {Año}.pdf */
    private function generatePayrollFileName($employeeName, $date, $originalName = '')
    {
        $monthName = $this->getSpanishMonthName($date->month);
        $year = $date->year;
        $name = trim(preg_replace('/\s+/', ' ', $employeeName));
        return "Nomina {$name} {$monthName} {$year}.pdf";
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
