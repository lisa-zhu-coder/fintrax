@extends('layouts.app')

@section('title', 'Añadir Empleado')

@section('content')
@php $oldInput = $oldInput ?? []; @endphp
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Añadir Empleado</h1>
                <p class="text-sm text-slate-500">Completa todos los datos del nuevo empleado</p>
            </div>
            <a href="{{ route('employees.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                ← Volver
            </a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4">
                <div class="flex items-start gap-3">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-rose-600 mt-0.5">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-rose-900 mb-2">Errores de validación:</h3>
                        <ul class="list-disc list-inside text-sm text-rose-800 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        
        <form method="POST" action="{{ route('employees.store') }}" class="space-y-6" id="employeeForm">
            @csrf
            
            <!-- Información Personal -->
            <div class="rounded-xl border-2 border-blue-100 bg-blue-50/30 p-4 ring-1 ring-blue-100">
                <div class="mb-4 flex items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-blue-700">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span class="text-sm font-semibold text-blue-900">Información Personal</span>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Nombre completo *</span>
                        <input type="text" name="full_name" value="{{ $oldInput['full_name'] ?? old('full_name') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">DNI *</span>
                        <input type="text" name="dni" value="{{ $oldInput['dni'] ?? old('dni') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Número de teléfono</span>
                        <input type="tel" name="phone" value="{{ $oldInput['phone'] ?? old('phone') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Correo electrónico</span>
                        <input type="email" name="email" value="{{ $oldInput['email'] ?? old('email') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-xs font-semibold text-slate-700">Calle</span>
                        <input type="text" name="street" value="{{ $oldInput['street'] ?? old('street') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Código postal</span>
                        <input type="text" name="postal_code" value="{{ $oldInput['postal_code'] ?? old('postal_code') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Ciudad</span>
                        <input type="text" name="city" value="{{ $oldInput['city'] ?? old('city') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                </div>
            </div>

            <!-- Asociación de Usuario -->
            <div class="rounded-xl border-2 border-brand-100 bg-brand-50/30 p-4 ring-1 ring-brand-100">
                <div class="mb-4 flex items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-brand-700">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span class="text-sm font-semibold text-brand-900">Cuenta de Usuario</span>
                </div>
                <div class="flex gap-2 items-end">
                    <label class="block flex-1">
                        <span class="text-xs font-semibold text-slate-700">Usuario asociado</span>
                        <select name="user_id" id="employeeUserId" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="">Sin usuario asignado</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ ($oldInput['user_id'] ?? old('user_id')) == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->role->name }})</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="button" id="openNewUserModal" class="rounded-xl border border-brand-600 bg-white px-4 py-2 text-sm font-semibold text-brand-600 hover:bg-brand-50 whitespace-nowrap">
                        + Crear usuario nuevo
                    </button>
                </div>
                <div class="mt-1 text-xs text-slate-500">Asocia este empleado con una cuenta de usuario del sistema o crea una nueva.</div>
            </div>

            <!-- Información Laboral -->
            <div class="rounded-xl border-2 border-emerald-100 bg-emerald-50/30 p-4 ring-1 ring-emerald-100">
                <div class="mb-4 flex items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-emerald-700">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span class="text-sm font-semibold text-emerald-900">Información Laboral</span>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Puesto *</span>
                        <input type="text" name="position" value="{{ $oldInput['position'] ?? old('position') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Horas contratadas *</span>
                        <input type="number" name="hours" step="0.5" min="0" value="{{ $oldInput['hours'] ?? old('hours') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Fecha de inicio *</span>
                        <input type="date" name="start_date" value="{{ $oldInput['start_date'] ?? old('start_date') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Fecha de finalización</span>
                        <input type="date" name="end_date" value="{{ $oldInput['end_date'] ?? old('end_date') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-xs font-semibold text-slate-700">Tiendas a las que pertenece *</span>
                        @if(count($stores) === 0)
                            <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 p-3">
                                <p class="text-sm text-amber-800">
                                    <strong>No hay tiendas disponibles.</strong> Por favor, crea al menos una tienda en la sección "Empresa" antes de crear un empleado.
                                </p>
                                <a href="{{ route('company.show') }}" class="mt-2 inline-block text-sm font-semibold text-amber-900 underline">
                                    Ir a Empresa →
                                </a>
                            </div>
                        @else
                            <div class="mt-2 space-y-2 rounded-xl border border-slate-200 bg-white p-3">
                                @foreach($stores as $store)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        @php $checked = in_array($store->id, (array)($oldInput['store_ids'] ?? old('store_ids', []))); @endphp
                                        <input type="checkbox" name="store_ids[]" value="{{ $store->id }}" class="employeeStoreCheckbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-500" {{ $checked ? 'checked' : '' }}/>
                                        <span class="text-sm text-slate-700">{{ $store->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if($errors->has('store_ids') || $errors->has('store_ids.*'))
                                <p class="mt-1 text-sm text-rose-600">{{ $errors->first('store_ids') ?: $errors->first('store_ids.*') }}</p>
                            @endif
                            <div id="employeeStoresError" class="mt-1 hidden text-xs text-rose-600">Debe seleccionar al menos una tienda</div>
                        @endif
                    </label>
                </div>
            </div>

            <!-- Información Financiera -->
            <div class="rounded-xl border-2 border-amber-100 bg-amber-50/30 p-4 ring-1 ring-amber-100">
                <div class="mb-4 flex items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-amber-700">
                        <path d="M4 10h12M4 14h9M19 6a7.7 7.7 0 0 0-5.2-2A7.9 7.9 0 0 0 6 12c0 4.4 3.5 8 7.8 8 2 0 3.8-.8 5.2-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-sm font-semibold text-amber-900">Información Financiera</span>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Nº Seguridad Social</span>
                        <input type="text" name="social_security" value="{{ $oldInput['social_security'] ?? old('social_security') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Número IBAN</span>
                        <input type="text" name="iban" value="{{ $oldInput['iban'] ?? old('iban') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Salario bruto mensual (€)</span>
                        <input type="number" name="gross_salary" step="0.01" min="0" value="{{ $oldInput['gross_salary'] ?? old('gross_salary') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Salario neto aproximado mensual (€)</span>
                        <input type="number" name="net_salary" step="0.01" min="0" value="{{ $oldInput['net_salary'] ?? old('net_salary') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                </div>
            </div>

            <!-- Información de Uniforme -->
            <div class="rounded-xl border-2 border-purple-100 bg-purple-50/30 p-4 ring-1 ring-purple-100">
                <div class="mb-4 flex items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-purple-700">
                        <path d="M20 7h-3M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-sm font-semibold text-purple-900">Información de Uniforme</span>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Talla de camiseta</span>
                        <input type="text" name="shirt_size" value="{{ $oldInput['shirt_size'] ?? old('shirt_size') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Talla de blazer</span>
                        <input type="text" name="blazer_size" value="{{ $oldInput['blazer_size'] ?? old('blazer_size') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Talla de pantalones</span>
                        <input type="text" name="pants_size" value="{{ $oldInput['pants_size'] ?? old('pants_size') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <a href="{{ route('employees.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" {{ count($stores) === 0 ? 'disabled' : '' }}>
                    Crear empleado
                </button>
            </div>
        </form>

        <!-- Modal Crear usuario nuevo (fuera del form para evitar formularios anidados) -->
        <div id="newUserModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
            <div class="fixed inset-0 bg-slate-900/50" id="newUserModalBackdrop"></div>
            <div class="fixed left-1/2 top-1/2 z-50 w-full max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Crear usuario nuevo</h3>
                <form id="newUserForm" class="space-y-4">
                    @csrf
                    <p class="text-xs text-slate-600">El nombre del usuario será el nombre completo del empleado que has indicado arriba.</p>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Usuario (login) *</span>
                        <input type="text" name="username" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Correo electrónico</span>
                        <input type="email" name="email" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Contraseña *</span>
                        <input type="password" name="password" required minlength="6" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Rol *</span>
                        <select name="role_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Tienda (opcional)</span>
                        <select name="store_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="">Sin tienda</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div id="newUserFormError" class="hidden rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800"></div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="closeNewUserModal" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="submit" id="submitNewUser" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Crear usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('employeeForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const checkedStores = document.querySelectorAll('.employeeStoreCheckbox:checked');
        const errorDiv = document.getElementById('employeeStoresError');
        if (checkedStores.length === 0) {
            e.preventDefault();
            e.stopPropagation();
            if (errorDiv) errorDiv.classList.remove('hidden');
            alert('Debe seleccionar al menos una tienda.');
            return false;
        }
        if (errorDiv) errorDiv.classList.add('hidden');
        return true;
    });

    document.querySelectorAll('.employeeStoreCheckbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const errorDiv = document.getElementById('employeeStoresError');
            if (document.querySelectorAll('.employeeStoreCheckbox:checked').length > 0 && errorDiv) {
                errorDiv.classList.add('hidden');
            }
        });
    });

    // Modal crear usuario nuevo
    const modal = document.getElementById('newUserModal');
    const openBtn = document.getElementById('openNewUserModal');
    const closeBtn = document.getElementById('closeNewUserModal');
    const backdrop = document.getElementById('newUserModalBackdrop');
    const newUserForm = document.getElementById('newUserForm');
    const newUserFormError = document.getElementById('newUserFormError');
    const employeeUserId = document.getElementById('employeeUserId');
    const submitBtn = document.getElementById('submitNewUser');

    function openModal() {
        if (modal) modal.classList.remove('hidden');
        newUserFormError.classList.add('hidden');
        newUserFormError.textContent = '';
    }
    function closeModal() {
        if (modal) modal.classList.add('hidden');
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    if (newUserForm) {
        newUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            newUserFormError.classList.add('hidden');
            newUserFormError.textContent = '';
            submitBtn.disabled = true;

            const formData = new FormData(newUserForm);
            const employeeName = document.querySelector('#employeeForm input[name="full_name"]') ? document.querySelector('#employeeForm input[name="full_name"]').value.trim() : '';
            if (!employeeName) {
                newUserFormError.textContent = 'Indica primero el nombre completo del empleado arriba.';
                newUserFormError.classList.remove('hidden');
                submitBtn.disabled = false;
                return;
            }
            const data = {
                username: formData.get('username'),
                name: employeeName,
                email: formData.get('email') || '',
                password: formData.get('password'),
                role_id: formData.get('role_id'),
                store_id: formData.get('store_id') || '',
                _token: formData.get('_token'),
            };

            fetch('{{ route("employees.quick-user") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': data._token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            })
            .then(function(res) { return res.json().then(function(body) { return { ok: res.ok, status: res.status, body: body }; }); })
            .then(function(result) {
                if (result.ok) {
                    const opt = document.createElement('option');
                    opt.value = result.body.id;
                    opt.textContent = result.body.name + ' (' + result.body.role_name + ')';
                    opt.selected = true;
                    employeeUserId.appendChild(opt);
                    closeModal();
                    newUserForm.reset();
                } else {
                    const msg = result.body.message || (result.body.errors ? Object.values(result.body.errors).flat().join(' ') : 'Error al crear el usuario.');
                    newUserFormError.textContent = msg;
                    newUserFormError.classList.remove('hidden');
                }
            })
            .catch(function() {
                newUserFormError.textContent = 'Error de conexión. Inténtalo de nuevo.';
                newUserFormError.classList.remove('hidden');
            })
            .finally(function() {
                submitBtn.disabled = false;
            });
        });
    }
});
</script>
@endpush
@endsection
