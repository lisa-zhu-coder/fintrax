@extends('layouts.app')

@section('title', 'Datos de la Empresa')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Datos de la empresa</h1>
                <p class="text-sm text-slate-500">Información fiscal y datos de los negocios</p>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Información fiscal</h2>
            @if(auth()->user()->hasPermission('admin.company.edit'))
            <button type="button" id="editFiscalBtn" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Editar
            </button>
            @endif
        </div>

        {{-- Vista de solo lectura --}}
        <div id="fiscalView" class="space-y-3">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-xl bg-slate-50 px-3 py-2">
                    <span class="text-xs font-semibold text-slate-500">Nombre de la empresa</span>
                    <p class="text-sm font-medium text-slate-800">{{ $company->name ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2">
                    <span class="text-xs font-semibold text-slate-500">CIF</span>
                    <p class="text-sm font-medium text-slate-800">{{ $company?->cif ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2 md:col-span-2">
                    <span class="text-xs font-semibold text-slate-500">Calle</span>
                    <p class="text-sm font-medium text-slate-800">{{ $company->fiscal_street ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2">
                    <span class="text-xs font-semibold text-slate-500">Código postal</span>
                    <p class="text-sm font-medium text-slate-800">{{ $company?->fiscal_postal_code ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2">
                    <span class="text-xs font-semibold text-slate-500">Ciudad</span>
                    <p class="text-sm font-medium text-slate-800">{{ $company->fiscal_city ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2 md:col-span-2">
                    <span class="text-xs font-semibold text-slate-500">Correo electrónico</span>
                    <p class="text-sm font-medium text-slate-800">{{ $company?->fiscal_email ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Formulario de edición (oculto por defecto) --}}
        @if(auth()->user()->hasPermission('admin.company.edit'))
        <form id="fiscalForm" method="POST" action="{{ route('company.update') }}" class="hidden space-y-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nombre de la empresa</span>
                    <input type="text" name="name" value="{{ $company->name ?? '' }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">CIF</span>
                    <input type="text" name="cif" value="{{ $company?->cif ?? '' }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold text-slate-700">Calle</span>
                    <input type="text" name="fiscal_street" value="{{ $company->fiscal_street ?? '' }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Código postal</span>
                    <input type="text" name="fiscal_postal_code" value="{{ $company?->fiscal_postal_code ?? '' }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Ciudad</span>
                    <input type="text" name="fiscal_city" value="{{ $company->fiscal_city ?? '' }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold text-slate-700">Correo electrónico</span>
                    <input type="email" name="fiscal_email" value="{{ $company?->fiscal_email ?? '' }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="button" id="cancelFiscalBtn" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar cambios</button>
            </div>
        </form>
        @endif
    </div>

    <!-- Negocios/Tiendas -->
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Negocios/Tiendas</h2>
            @if(auth()->user()->hasPermission('admin.company.edit'))
            <button onclick="document.getElementById('newBusinessForm').classList.toggle('hidden'); document.getElementById('newBusinessName').focus();" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Añadir negocio
            </button>
            @endif
        </div>

        <!-- Formulario para nuevo negocio -->
        @if(auth()->user()->hasPermission('admin.company.edit'))
        <form id="newBusinessForm" method="POST" action="{{ route('company.businesses.store') }}" class="mb-6 {{ old('_token') && $errors->any() ? '' : 'hidden' }} rounded-xl border border-slate-200 bg-slate-50 p-4">
            @csrf
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
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nombre</span>
                    <input type="text" id="newBusinessName" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                
                <!-- Slug oculto, se genera automáticamente -->
                <input type="hidden" id="newBusinessSlug" name="slug" value="{{ old('slug') }}"/>
                
                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold text-slate-700">Calle</span>
                    <input type="text" name="street" value="{{ old('street') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Código postal</span>
                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Ciudad</span>
                    <input type="text" name="city" value="{{ old('city') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                
                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold text-slate-700">Correo electrónico</span>
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>
            
            <div class="mt-4 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('newBusinessForm').classList.add('hidden')" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" onclick="this.disabled=true; this.form.submit();">
                    Guardar
                </button>
            </div>
        </form>
        @endif

        <!-- Lista de negocios -->
        <div class="space-y-4">
            @forelse($businesses as $business)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                {{-- Vista de solo lectura --}}
                <div id="business-view-{{ $business->id }}" class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-base font-semibold">{{ $business->name }}</div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('stores.edit', $business) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6 4.03-6 9-6 9 4.8 9 6Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" fill="currentColor"/>
                                </svg>
                                Cuentas bancarias
                            </a>
                            @if(auth()->user()->hasPermission('admin.company.edit'))
                            <button type="button" class="business-edit-btn inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-business-id="{{ $business->id }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Editar
                            </button>
                            @endif
                        </div>
                    </div>
                    @if($business->street || $business->postal_code || $business->city)
                    <div class="text-sm text-slate-600">
                        {{ $business->street }}{{ $business->postal_code ? ', ' . $business->postal_code : '' }}{{ $business->city ? ', ' . $business->city : '' }}
                    </div>
                    @endif
                    @if($business->email)
                    <div class="text-sm text-slate-600">{{ $business->email }}</div>
                    @endif
                </div>

                {{-- Formulario de edición (oculto por defecto) --}}
                @if(auth()->user()->hasPermission('admin.company.edit'))
                <form id="business-form-{{ $business->id }}" method="POST" action="{{ route('company.businesses.update', $business) }}" class="hidden space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Nombre</span>
                            <input type="text" name="name" value="{{ $business->name }}" required class="business-name-input mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" data-slug-target="business-slug-{{ $business->id }}"/>
                        </label>
                        <input type="hidden" id="business-slug-{{ $business->id }}" name="slug" value="{{ $business->slug }}"/>
                        <label class="block md:col-span-2">
                            <span class="text-xs font-semibold text-slate-700">Calle</span>
                            <input type="text" name="street" value="{{ $business->street }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Código postal</span>
                            <input type="text" name="postal_code" value="{{ $business->postal_code }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Ciudad</span>
                            <input type="text" name="city" value="{{ $business->city }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-xs font-semibold text-slate-700">Correo electrónico</span>
                            <input type="email" name="email" value="{{ $business->email }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('stores.edit', $business) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cuentas bancarias</a>
                        <button type="button" class="business-cancel-btn rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-business-id="{{ $business->id }}">Cancelar</button>
                        <button type="button" onclick="if(confirm('¿Estás seguro de eliminar este negocio?')) { document.getElementById('delete-form-{{ $business->id }}').submit(); }" class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Eliminar</button>
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" onclick="this.disabled=true; this.form.submit();">Guardar cambios</button>
                    </div>
                </form>
                <form id="delete-form-{{ $business->id }}" method="POST" action="{{ route('company.businesses.destroy', $business) }}" style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>
                @endif
            </div>
            @empty
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center text-slate-500">
                No hay negocios registrados
            </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Función para convertir texto a slug
    function toSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '_')
            .replace(/^-+|-+$/g, '');
    }

    // Información fiscal: Editar / Cancelar
    const editFiscalBtn = document.getElementById('editFiscalBtn');
    const cancelFiscalBtn = document.getElementById('cancelFiscalBtn');
    const fiscalView = document.getElementById('fiscalView');
    const fiscalForm = document.getElementById('fiscalForm');

    if (editFiscalBtn && fiscalView && fiscalForm) {
        editFiscalBtn.addEventListener('click', function() {
            fiscalView.classList.add('hidden');
            fiscalForm.classList.remove('hidden');
            editFiscalBtn.classList.add('hidden');
        });
    }
    if (cancelFiscalBtn && fiscalView && fiscalForm) {
        cancelFiscalBtn.addEventListener('click', function() {
            fiscalView.classList.remove('hidden');
            fiscalForm.classList.add('hidden');
            if (editFiscalBtn) editFiscalBtn.classList.remove('hidden');
        });
    }

    // Negocios: Editar / Cancelar
    document.querySelectorAll('.business-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.businessId;
            document.getElementById('business-view-' + id).classList.add('hidden');
            document.getElementById('business-form-' + id).classList.remove('hidden');
        });
    });
    document.querySelectorAll('.business-cancel-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.businessId;
            document.getElementById('business-view-' + id).classList.remove('hidden');
            document.getElementById('business-form-' + id).classList.add('hidden');
        });
    });

    // Auto-generar slug para nuevo negocio
    const newBusinessNameInput = document.getElementById('newBusinessName');
    const newBusinessSlugInput = document.getElementById('newBusinessSlug');
    if (newBusinessNameInput && newBusinessSlugInput) {
        newBusinessNameInput.addEventListener('input', function() {
            newBusinessSlugInput.value = toSlug(this.value);
        });
        if (newBusinessNameInput.value) {
            newBusinessSlugInput.value = toSlug(newBusinessNameInput.value);
        }
    }

    // Auto-generar slug para negocios existentes
    document.querySelectorAll('.business-name-input').forEach(function(nameInput) {
        const slugTargetId = nameInput.dataset.slugTarget;
        const slugInput = document.getElementById(slugTargetId);
        if (slugInput) {
            nameInput.addEventListener('input', function() {
                slugInput.value = toSlug(this.value);
            });
        }
    });
</script>
@endpush
@endsection
