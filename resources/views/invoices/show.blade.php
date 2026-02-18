@extends('layouts.app')

@section('title', 'Detalle de Factura')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Detalle de Factura</h1>
                <p class="text-sm text-slate-500">Información completa de la factura</p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->hasPermission('invoices.main.edit'))
                <a href="{{ route('invoices.edit', $invoice->id) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Editar
                </a>
                @endif
                @if(auth()->user()->hasPermission('invoices.main.delete'))
                <form method="POST" action="{{ route('invoices.destroy', $invoice->id) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta factura? Los gastos asociados se desvincularán pero no se eliminarán.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                        Eliminar
                    </button>
                </form>
                @endif
                <a href="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    ← Volver
                </a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <span class="text-xs font-semibold text-slate-500">Fecha</span>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $invoice->date->format('d/m/Y') }}</div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Número de factura</span>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $invoice->invoice_number ?? '—' }}</div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Proveedor</span>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $invoice->supplier_name }}</div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Importe total</span>
                <div class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($invoice->total_amount, 2, ',', '.') }} €</div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Método de pago</span>
                <div class="mt-1">
                    @if($invoice->payment_method)
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium 
                            {{ $invoice->payment_method === 'cash' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}
                        ">
                            {{ $invoice->payment_method === 'cash' ? 'Efectivo' : 'Tarjeta' }}
                        </span>
                    @else
                        <span class="text-sm text-slate-400">—</span>
                    @endif
                </div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Estado</span>
                <div class="mt-1">
                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium 
                        {{ $invoice->status === 'pagada' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}
                    ">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
            </div>

            @if($invoice->details)
            <div class="md:col-span-2">
                <span class="text-xs font-semibold text-slate-500">Detalles</span>
                <div class="mt-1 text-sm text-slate-900 whitespace-pre-wrap">{{ $invoice->details }}</div>
            </div>
            @endif

            @if($invoice->file_path)
            @php
                $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->exists($invoice->file_path) 
                    ? \Illuminate\Support\Facades\Storage::disk('local')->mimeType($invoice->file_path) 
                    : 'application/octet-stream';
            @endphp
            <div class="md:col-span-2">
                <span class="text-xs font-semibold text-slate-500">Archivo</span>
                <div class="mt-1">
                    <a href="#" 
                       data-invoice-preview 
                       data-invoice-id="{{ $invoice->id }}"
                       data-invoice-title="Previsualizar Factura"
                       data-invoice-subtitle="{{ $invoice->supplier_name ?: 'Sin proveedor' }} - {{ $invoice->invoice_number ?: 'Sin número' }}"
                       data-invoice-serve="{{ route('invoices.serve', $invoice->id) }}"
                       data-invoice-download="{{ route('invoices.download', $invoice->id) }}"
                       data-invoice-mime="{{ $mimeType }}"
                       class="inline-flex items-center gap-2 text-sm text-brand-600 hover:text-brand-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Ver archivo
                    </a>
                </div>
            </div>
            @endif

            <div>
                <span class="text-xs font-semibold text-slate-500">Creado por</span>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $invoice->creator->name ?? '—' }}</div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Fecha de creación</span>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $invoice->created_at->format('d/m/Y H:i') }}</div>
            </div>

            @if($invoice->updated_at != $invoice->created_at)
            <div>
                <span class="text-xs font-semibold text-slate-500">Última actualización</span>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $invoice->updated_at->format('d/m/Y H:i') }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($invoice->financialEntries->count() > 0)
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Gastos asociados</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2">Concepto</th>
                        <th class="px-3 py-2">Categoría</th>
                        <th class="px-3 py-2 text-right">Importe</th>
                        <th class="px-3 py-2 text-right">Pagado</th>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($invoice->financialEntries as $expense)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">{{ $expense->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">{{ $expense->store->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $expense->concept ?? $expense->expense_concept ?? '—' }}</td>
                            <td class="px-3 py-2">
                                @if($expense->expense_category)
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ ucfirst(str_replace('_', ' ', $expense->expense_category)) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-rose-700">
                                {{ number_format($expense->total_amount ?? $expense->amount ?? 0, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-slate-600">
                                {{ number_format($expense->total_paid ?? 0, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2">
                                @php
                                    $status = $expense->status ?? ($expense->total_paid >= ($expense->total_amount ?? $expense->amount) ? 'pagado' : 'pendiente');
                                    $statusLabel = $status === 'pagado' ? 'Pagado' : 'Pendiente';
                                    $statusColor = $status === 'pagado' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('financial.show', [$expense->id, 'return_to' => route('invoices.show', $invoice)]) }}" class="inline-flex items-center gap-1 rounded-lg p-1.5 text-brand-600 hover:bg-brand-50" title="Ver gasto">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
