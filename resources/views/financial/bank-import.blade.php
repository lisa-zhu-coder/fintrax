@extends('layouts.app')

@section('title', 'Importar Movimientos Bancarios')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Importar Movimientos Bancarios</h1>
                <p class="text-sm text-slate-500">Importa movimientos desde archivos CSV o Excel</p>
            </div>
            <a href="{{ route('financial.bank-conciliation') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Volver a conciliación
            </a>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-100">
            {{ session('error') }}
        </div>
    @endif

    @if(isset($errors) && (is_array($errors) ? !empty($errors) : $errors->any()))
        <div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-100">
            <ul class="list-disc list-inside space-y-1">
                @if(is_array($errors))
                    @foreach($errors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                @else
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                @endif
            </ul>
        </div>
    @endif

    @if(isset($importErrors) && !empty($importErrors))
        <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-100">
            <p class="font-semibold mb-2">Errores durante la importación:</p>
            <ul class="list-disc list-inside space-y-1 max-h-60 overflow-y-auto">
                @foreach($importErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario de importación -->
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('financial.bank-import.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div class="space-y-4">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Cuenta bancaria *</span>
                    <select name="bank_account_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona una cuenta...</option>
                        @if(isset($bankAccounts))
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->bank_name }} - {{ $account->iban }} ({{ $account->store->name ?? '—' }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('bank_account_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Archivo *</span>
                    <div class="mt-1 flex items-center gap-3">
                        <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                    </div>
                    <p class="mt-2 text-xs text-slate-500">
                        Formatos soportados: CSV, TXT, XLSX, XLS (máximo 10MB)
                    </p>
                    @error('file')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>

            <!-- Información sobre el formato -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-slate-900">Formato del archivo</h3>
                    <a href="{{ route('financial.bank-import.template') }}" class="inline-flex items-center gap-2 rounded-xl border border-brand-200 bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Descargar plantilla CSV
                    </a>
                </div>
                <p class="text-xs text-slate-600 mb-3">
                    El archivo debe contener las siguientes columnas (primera fila como encabezados):
                </p>
                <ul class="text-xs text-slate-600 space-y-1 list-disc list-inside">
                    <li><strong>Fecha</strong> (o "date"): Fecha del movimiento</li>
                    <li><strong>Descripción</strong> (o "description"): Concepto del movimiento</li>
                    <li><strong>Importe</strong> (o "amount"): Cantidad (puede incluir símbolos €, $ o comas)</li>
                    <li><strong>Tipo</strong> (o "type", opcional): "credit" o "debit" (si no se especifica, se infiere del signo del importe)</li>
                </ul>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('financial.bank-conciliation') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block mr-2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Importar movimientos
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
