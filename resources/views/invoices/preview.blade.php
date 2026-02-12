@extends('layouts.app')

@section('title', 'Previsualizar Factura')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Previsualizar Factura</h1>
                <p class="text-sm text-slate-500">{{ $invoice->supplier_name ?: 'Sin proveedor' }} - {{ $invoice->invoice_number ?: 'Sin número' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('invoices.show', $invoice->id) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    ← Volver
                </a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <div class="flex flex-col items-center justify-center min-h-[600px]">
            @if(str_starts_with($mimeType, 'image/'))
                {{-- Mostrar imagen --}}
                <img src="{{ route('invoices.serve', $invoice->id) }}" alt="Factura" class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-lg">
            @elseif($mimeType === 'application/pdf')
                {{-- Mostrar PDF --}}
                <iframe 
                    src="{{ route('invoices.serve', $invoice->id) }}" 
                    class="w-full h-[80vh] border-0 rounded-lg shadow-lg"
                    frameborder="0"
                    title="Vista previa de factura PDF">
                </iframe>
            @else
                {{-- Tipo de archivo no soportado para previsualización --}}
                <div class="text-center p-8">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-slate-900">Tipo de archivo no soportado para previsualización</h3>
                    <p class="mt-1 text-sm text-slate-500">Tipo MIME: {{ $mimeType }}</p>
                    <div class="mt-6">
                        <a href="{{ route('invoices.download', $invoice->id) }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Descargar archivo
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
