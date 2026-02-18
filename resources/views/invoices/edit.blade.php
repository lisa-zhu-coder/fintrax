@extends('layouts.app')

@section('title', 'Editar Factura')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Editar Factura</h1>
                <p class="text-sm text-slate-500">Modifica los datos de la factura</p>
            </div>
            <a href="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                ← Volver
            </a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                @foreach($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('invoices.update', $invoice->id) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                    <input type="date" name="date" value="{{ old('date', $invoice->date->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('date') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Número de factura</span>
                    <input type="text" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" class="mt-1 w-full rounded-xl border {{ $errors->has('invoice_number') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('invoice_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Proveedor *</span>
                    <input type="text" name="supplier_name" value="{{ old('supplier_name', $invoice->supplier_name) }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('supplier_name') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('supplier_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Importe total (€) *</span>
                    <input type="number" name="total_amount" step="0.01" min="0" value="{{ old('total_amount', $invoice->total_amount) }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('total_amount') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('total_amount')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Método de pago</span>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border {{ $errors->has('payment_method') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona un método</option>
                        <option value="cash" {{ old('payment_method', $invoice->payment_method) === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="card" {{ old('payment_method', $invoice->payment_method) === 'card' ? 'selected' : '' }}>Tarjeta</option>
                    </select>
                    @error('payment_method')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Estado *</span>
                    <select name="status" required class="mt-1 w-full rounded-xl border {{ $errors->has('status') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="pendiente" {{ old('status', $invoice->status) === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="pagada" {{ old('status', $invoice->status) === 'pagada' ? 'selected' : '' }}>Pagada</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Detalles</span>
                <textarea name="details" rows="4" class="mt-1 w-full rounded-xl border {{ $errors->has('details') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">{{ old('details', $invoice->details) }}</textarea>
                @error('details')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Archivo</span>
                @if($invoice->file_path)
                    <div class="mb-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="text-sm text-slate-700">Archivo actual: {{ basename($invoice->file_path) }}</span>
                                </div>
                                @php
                                    $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->exists($invoice->file_path) 
                                        ? \Illuminate\Support\Facades\Storage::disk('local')->mimeType($invoice->file_path) 
                                        : 'application/octet-stream';
                                @endphp
                                <a href="#" 
                                   data-invoice-preview 
                                   data-invoice-id="{{ $invoice->id }}"
                                   data-invoice-title="Previsualizar Factura"
                                   data-invoice-subtitle="{{ $invoice->supplier_name ?: 'Sin proveedor' }} - {{ $invoice->invoice_number ?: 'Sin número' }}"
                                   data-invoice-serve="{{ route('invoices.serve', $invoice->id) }}"
                                   data-invoice-download="{{ route('invoices.download', $invoice->id) }}"
                                   data-invoice-mime="{{ $mimeType }}"
                                   class="text-xs text-brand-600 hover:text-brand-700">Ver</a>
                        </div>
                    </div>
                @endif
                <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded-xl border {{ $errors->has('file') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                <p class="mt-1 text-xs text-slate-500">Formatos permitidos: PDF, JPG, PNG (máx. 10MB). Dejar vacío para mantener el archivo actual.</p>
                @error('file')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </label>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Enlazar con gasto existente</span>
                    @if($currentExpense)
                        <div class="mt-2 mb-2 rounded-xl border border-brand-200 bg-brand-50 p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4 10h12M4 14h9M19 6a7.7 7.7 0 0 0-5.2-2A7.9 7.9 0 0 0 6 12c0 4.4 3.5 8 7.8 8 2 0 3.8-.8 5.2-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-brand-700">Gasto actual asociado</span>
                                </div>
                                <a href="{{ route('financial.show', [$currentExpense->id, 'return_to' => url()->current()]) }}" target="_blank" class="text-xs text-brand-600 hover:text-brand-700">Ver gasto</a>
                            </div>
                            <p class="mt-1 ml-6 text-xs text-slate-600">
                                {{ $currentExpense->concept ?? $currentExpense->expense_concept ?? 'Sin concepto' }} - 
                                {{ number_format($currentExpense->total_amount ?? $currentExpense->amount ?? 0, 2, ',', '.') }} € - 
                                {{ $currentExpense->date->format('d/m/Y') }}
                            </p>
                        </div>
                    @endif
                    <select name="expense_id" class="mt-1 w-full rounded-xl border {{ $errors->has('expense_id') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Ninguno (sin enlazar)</option>
                        @foreach($availableExpenses as $expense)
                            <option value="{{ $expense->id }}" {{ old('expense_id', $currentExpense?->id) == $expense->id ? 'selected' : '' }}>
                                {{ $expense->date->format('d/m/Y') }} - 
                                {{ $expense->store->name ?? 'Sin tienda' }} - 
                                {{ $expense->concept ?? $expense->expense_concept ?? 'Sin concepto' }} - 
                                {{ number_format($expense->total_amount ?? $expense->amount ?? 0, 2, ',', '.') }} €
                                @if($expense->invoice_id == $invoice->id) (Ya asociado) @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Selecciona un gasto existente para asociarlo a esta factura. Solo se muestran gastos sin factura asignada.</p>
                    @error('expense_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <a href="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
