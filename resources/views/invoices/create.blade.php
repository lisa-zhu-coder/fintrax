@extends('layouts.app')

@section('title', 'Añadir Factura')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Añadir Factura</h1>
                <p class="text-sm text-slate-500">Registra una nueva factura de proveedor</p>
            </div>
            <a href="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                ← Volver
            </a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                @foreach($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif
        
        @if(session('from_upload') && isset($uploadedFileName))
            <div class="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-3 text-sm text-brand-700">
                <p class="font-semibold">Archivo subido: {{ $uploadedFileName }}</p>
                <p class="mt-1 text-xs">Los datos extraídos se han prellenado automáticamente. Revisa y completa los campos antes de guardar.</p>
            </div>
        @endif
        
        <form method="POST" action="{{ route('invoices.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                    <input type="date" name="date" value="{{ old('date', $extractedData['date'] ?? now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('date') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Número de factura</span>
                    <input type="text" name="invoice_number" value="{{ old('invoice_number', $extractedData['invoice_number'] ?? '') }}" class="mt-1 w-full rounded-xl border {{ $errors->has('invoice_number') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('invoice_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Proveedor *</span>
                    <input type="text" name="supplier_name" value="{{ old('supplier_name', $extractedData['supplier_name'] ?? '') }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('supplier_name') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('supplier_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Importe total (€) *</span>
                    <input type="number" name="total_amount" step="0.01" min="0" value="{{ old('total_amount', $extractedData['total_amount'] ?? '') }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('total_amount') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('total_amount')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Método de pago</span>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border {{ $errors->has('payment_method') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona un método</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Tarjeta</option>
                    </select>
                    @error('payment_method')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Estado *</span>
                    <select name="status" required class="mt-1 w-full rounded-xl border {{ $errors->has('status') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="pendiente" {{ old('status', 'pendiente') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="pagada" {{ old('status') === 'pagada' ? 'selected' : '' }}>Pagada</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Detalles</span>
                <textarea name="details" rows="4" class="mt-1 w-full rounded-xl border {{ $errors->has('details') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">{{ old('details', $extractedData['details'] ?? '') }}</textarea>
                @error('details')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Archivo</span>
                @if(session('from_upload') && isset($uploadedFileName))
                    <div class="mt-1 rounded-xl border border-brand-200 bg-brand-50 p-3 text-sm text-brand-700">
                        <p class="font-semibold">Archivo ya subido: {{ $uploadedFileName }}</p>
                        <p class="mt-1 text-xs text-slate-600">Si deseas cambiar el archivo, puedes subir uno nuevo a continuación.</p>
                    </div>
                @endif
                <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded-xl border {{ $errors->has('file') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                <p class="mt-1 text-xs text-slate-500">Formatos permitidos: PDF, JPG, PNG (máx. 10MB){{ session('from_upload') ? '. Si no subes un archivo nuevo, se usará el archivo ya subido.' : '' }}</p>
                @error('file')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </label>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="crear_gasto" value="1" id="crear_gasto" class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" {{ old('crear_gasto') ? 'checked' : '' }}/>
                    <span class="text-sm font-semibold text-slate-700">Crear gasto asociado</span>
                </label>
                <p class="mt-1 ml-7 text-xs text-slate-500">Si está marcado, se creará automáticamente un registro de gasto con el importe de la factura</p>
                
                <div id="store_selector_container" class="mt-4 hidden">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Tienda *</span>
                        <select name="store_id" id="store_id" class="mt-1 w-full rounded-xl border {{ $errors->has('store_id') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="">Selecciona una tienda</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                            @endforeach
                        </select>
                        @error('store_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                @if(session('from_upload'))
                    <a href="{{ route('invoices.clear-upload-session') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar y eliminar archivo
                    </a>
                @else
                    <a href="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </a>
                @endif
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Crear factura
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const crearGastoCheckbox = document.getElementById('crear_gasto');
    const storeSelectorContainer = document.getElementById('store_selector_container');
    const storeIdSelect = document.getElementById('store_id');
    
    function toggleStoreSelector() {
        if (crearGastoCheckbox.checked) {
            storeSelectorContainer.classList.remove('hidden');
            storeIdSelect.setAttribute('required', 'required');
        } else {
            storeSelectorContainer.classList.add('hidden');
            storeIdSelect.removeAttribute('required');
            storeIdSelect.value = '';
        }
    }
    
    // Verificar estado inicial
    toggleStoreSelector();
    
    // Escuchar cambios en el checkbox
    crearGastoCheckbox.addEventListener('change', toggleStoreSelector);
});
</script>
@endsection
