@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-100">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-100">
            {{ session('error') }}
        </div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Usuarios</h1>
                <p class="text-sm text-slate-500">Gestiona las cuentas de usuario del sistema</p>
            </div>
            <button onclick="openUserModal()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Añadir usuario
            </button>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Usuario</th>
                        <th class="px-3 py-2">Nombre</th>
                        <th class="px-3 py-2">Rol</th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ $user->username }}</td>
                            <td class="px-3 py-2">{{ $user->name }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800">
                                    {{ $user->role->name }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-slate-600">{{ $user->store ? $user->store->name : 'Todas' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <button onclick="editUser({{ $user->id }})" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('¿Estás seguro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-slate-500">No hay usuarios registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de usuario -->
<div id="userModal" class="hidden fixed inset-0 flex items-center justify-center bg-slate-900/40 p-4 z-50">
    <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-soft">
        <form id="userForm" method="POST" action="{{ route('users.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <div class="text-base font-semibold" id="modalTitle">Añadir usuario</div>
                    <div class="text-sm text-slate-500">Gestiona la información del usuario</div>
                </div>
                <button type="button" onclick="closeUserModal()" class="rounded-xl p-2 text-slate-500 hover:bg-slate-50">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            @if($errors->any())
                <div class="mb-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800 ring-1 ring-rose-100">
                    <p class="font-semibold mb-2">Errores:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Nombre de usuario</span>
                <input type="text" name="username" id="username" value="{{ old('username') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Nombre completo</span>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Correo electrónico</span>
                <input type="email" name="email" id="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700" id="passwordLabel">Contraseña</span>
                <input type="password" name="password" id="password" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                <div class="mt-1 text-xs text-slate-500 hidden" id="passwordHint">Dejar vacío para no cambiar</div>
            </label>

            @if(auth()->user()->isSuperAdmin() && isset($companies) && $companies->isNotEmpty())
            <input type="hidden" name="role_id" id="role_id" value="{{ $roles->where('key', '!=', 'super_admin')->first()?->id ?? '' }}"/>
            <input type="hidden" name="store_id" id="store_id" value=""/>
            <div id="companyAccessSection" class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-700">Acceso a empresas</span>
                    <button type="button" id="addCompanyAccessBtn" class="text-xs text-brand-600 hover:underline">+ Añadir empresa</button>
                </div>
                <p class="text-xs text-slate-500">Asigna una o varias empresas y el rol en cada una. Solo el Super Admin puede gestionar esto.</p>
                <div id="companyAccessContainer" class="space-y-2 max-h-48 overflow-y-auto"></div>
            </div>
            <template id="companyAccessRowTemplate">
                <div class="company-access-row flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-slate-50/50 p-2">
                    <select name="company_access[__INDEX__][company_id]" class="company-access-company flex-1 min-w-[120px] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm">
                        @foreach($companies as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <select name="company_access[__INDEX__][role_id]" class="company-access-role rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm">
                        @foreach($roles->where('key', '!=', 'super_admin') as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <select name="company_access[__INDEX__][store_id]" class="company-access-store rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm">
                        <option value="">Todas</option>
                        @foreach($stores as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="remove-company-access rounded p-1.5 text-rose-600 hover:bg-rose-50" aria-label="Quitar">×</button>
                </div>
            </template>
            @else
            <label class="block" id="labelRole">
                <span class="text-xs font-semibold text-slate-700">Rol</span>
                <select name="role_id" id="role_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block" id="labelStore">
                <span class="text-xs font-semibold text-slate-700">Tienda asignada</span>
                <select name="store_id" id="store_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Todas las tiendas</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            @endif

            <div class="flex items-center justify-end gap-2 mt-4">
                <button type="button" onclick="closeUserModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" onclick="this.disabled=true; this.form.submit();">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let currentUserId = null;
let companyAccessIndex = 0;

function reindexCompanyAccess() {
    const container = document.getElementById('companyAccessContainer');
    if (!container) return;
    container.querySelectorAll('.company-access-row').forEach((row, i) => {
        row.querySelectorAll('[name^="company_access"]').forEach(input => {
            input.name = input.name.replace(/company_access\[\d+\]/, 'company_access[' + i + ']');
        });
    });
    const first = container.querySelector('.company-access-row');
    const roleEl = document.getElementById('role_id');
    const storeEl = document.getElementById('store_id');
    if (roleEl && first) {
        const roleSelect = first.querySelector('.company-access-role');
        const storeSelect = first.querySelector('.company-access-store');
        if (roleSelect) roleEl.value = roleSelect.value;
        if (storeEl && storeSelect) storeEl.value = storeSelect.value || '';
    }
}

function addCompanyAccessRow(data = {}) {
    const container = document.getElementById('companyAccessContainer');
    const template = document.getElementById('companyAccessRowTemplate');
    if (!container || !template) return;
    const idx = container.querySelectorAll('.company-access-row').length;
    const html = template.innerHTML.replace(/__INDEX__/g, idx);
    const wrap = document.createElement('div');
    wrap.innerHTML = html;
    const row = wrap.firstElementChild;
    if (data.company_id) row.querySelector('.company-access-company').value = data.company_id;
    if (data.role_id) row.querySelector('.company-access-role').value = data.role_id;
    if (data.store_id) row.querySelector('.company-access-store').value = data.store_id;
    row.querySelector('.remove-company-access').addEventListener('click', function() {
        row.remove();
        reindexCompanyAccess();
    });
    container.appendChild(row);
    reindexCompanyAccess();
}

function initCompanyAccessSection() {
    const section = document.getElementById('companyAccessSection');
    const btn = document.getElementById('addCompanyAccessBtn');
    if (!section) return;
    if (btn) btn.onclick = () => addCompanyAccessRow();
}

document.addEventListener('DOMContentLoaded', function() {
    initCompanyAccessSection();
    const form = document.getElementById('userForm');
    if (form) {
        form.addEventListener('submit', function() {
            reindexCompanyAccess();
            const container = document.getElementById('companyAccessContainer');
            if (container) {
                const first = container.querySelector('.company-access-row');
                if (first) {
                    const r = document.getElementById('role_id');
                    const s = document.getElementById('store_id');
                    if (r) r.value = first.querySelector('.company-access-role')?.value || r.value;
                    if (s) s.value = first.querySelector('.company-access-store')?.value || '';
                }
            }
        });
    }
});

function openUserModal(userId = null) {
    currentUserId = userId;
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const title = document.getElementById('modalTitle');
    const passwordLabel = document.getElementById('passwordLabel');
    const passwordInput = document.getElementById('password');
    const passwordHint = document.getElementById('passwordHint');
    const formMethod = document.getElementById('formMethod');
    
    if (userId) {
        title.textContent = 'Editar usuario';
        passwordLabel.textContent = 'Nueva contraseña';
        passwordInput.removeAttribute('required');
        passwordHint.classList.remove('hidden');
        form.action = `/users/${userId}`;
        formMethod.value = 'PUT';
        
        fetch(`/users/${userId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('username').value = data.username || '';
                document.getElementById('name').value = data.name || '';
                document.getElementById('email').value = data.email || '';
                const roleEl = document.getElementById('role_id');
                const storeEl = document.getElementById('store_id');
                if (roleEl) roleEl.value = data.role_id || '';
                if (storeEl) storeEl.value = data.store_id || '';
                const container = document.getElementById('companyAccessContainer');
                if (container && data.company_access && data.company_access.length) {
                    container.innerHTML = '';
                    data.company_access.forEach(function(row) {
                        addCompanyAccessRow({
                            company_id: String(row.company_id),
                            role_id: String(row.role_id),
                            store_id: row.store_id ? String(row.store_id) : ''
                        });
                    });
                }
            })
            .catch(error => console.error('Error cargando usuario:', error));
    } else {
        title.textContent = 'Añadir usuario';
        passwordLabel.textContent = 'Contraseña';
        passwordInput.setAttribute('required', 'required');
        passwordHint.classList.add('hidden');
        form.action = '{{ route("users.store") }}';
        formMethod.value = 'POST';
        form.reset();
        const container = document.getElementById('companyAccessContainer');
        if (container) {
            container.innerHTML = '';
            addCompanyAccessRow();
        }
    }
    
    modal.classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
    currentUserId = null;
    document.getElementById('userForm').reset();
}

function editUser(userId) {
    openUserModal(userId);
}
</script>
@endpush
@endsection
