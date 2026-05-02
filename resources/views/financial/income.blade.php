@extends('layouts.app')

@section('title', 'Ingresos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Ingresos</h1>
                <p class="text-sm text-slate-500">Registros de ingresos</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative" data-export-menu>
                    <form method="GET" action="{{ route('financial.export') }}" class="flex items-center gap-2" data-export-form>
                        <input type="hidden" name="scope" value="income">
                        <input type="hidden" name="format" value="xlsx" data-export-format>
                        @foreach(request()->query() as $k => $v)
                            @if(!in_array($k, ['scope','format'], true))
                                @if(is_array($v))
                                    @foreach($v as $vv)
                                        <input type="hidden" name="{{ $k }}[]" value="{{ e($vv) }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $k }}" value="{{ e($v) }}">
                                @endif
                            @endif
                        @endforeach
                        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-export-trigger>
                            Exportar
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="text-slate-500">
                                <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </form>
                    <div class="absolute right-0 mt-2 hidden w-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg z-10" data-export-dropdown>
                        <button type="button" class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50" data-export-option="xlsx">Excel</button>
                        <button type="button" class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50" data-export-option="pdf">PDF</button>
                        <button type="button" class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50" data-export-option="csv">CSV</button>
                    </div>
                </div>
                @if(auth()->user()->hasPermission('financial.income.create'))
                <a href="{{ route('financial.create', ['type' => 'income']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir ingreso
                </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('financial.income') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                @include('partials.store-filter-select', ['name' => 'store', 'stores' => $stores, 'selected' => request('store'), 'label' => 'Tienda', 'showAllOption' => true])
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Período</span>
                    <select name="period" id="periodSelect" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="last_7" {{ $period === 'last_7' ? 'selected' : '' }}>Últimos 7 días</option>
                        <option value="last_30" {{ $period === 'last_30' ? 'selected' : '' }}>Últimos 30 días</option>
                        <option value="last_90" {{ $period === 'last_90' ? 'selected' : '' }}>Últimos 90 días</option>
                        <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Este mes</option>
                        <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Mes pasado</option>
                        <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>Este año</option>
                        <option value="custom" {{ request('date_from') && request('date_to') ? 'selected' : '' }}>Personalizado</option>
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Categoría</span>
                    <select name="category" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todas</option>
                        <option value="ventas" {{ request('category') === 'ventas' ? 'selected' : '' }}>Ventas</option>
                        <option value="servicios_financieros" {{ request('category') === 'servicios_financieros' ? 'selected' : '' }}>Servicios financieros</option>
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Modo de pago</span>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todos</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Banco</option>
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Procedencia</span>
                    <select name="source" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todas</option>
                        <option value="cierre_diario" {{ request('source') === 'cierre_diario' ? 'selected' : '' }}>Cierre diario</option>
                        <option value="ventas" {{ request('source') === 'ventas' ? 'selected' : '' }}>Ventas</option>
                        <option value="servicios_financieros" {{ request('source') === 'servicios_financieros' ? 'selected' : '' }}>Servicios financieros</option>
                        <option value="otros" {{ request('source') === 'otros' ? 'selected' : '' }}>Otros</option>
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Mes correspondiente</span>
                    <input type="month" name="month" value="{{ request('month') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    <p class="mt-1 text-xs text-slate-500">Filtrar por mes al que corresponde el registro (opcional)</p>
                </label>
            </div>
            
            <div id="customDateRange" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Desde</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Hasta</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>
            
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Filtrar
                </button>
                <a href="{{ route('financial.income') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Mes correspondiente</th>
                        <th class="px-3 py-2 cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.income', array_merge(request()->query(), ['sort_by' => 'date', 'sort_dir' => request('sort_by') === 'date' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
                                Fecha
                                @if(request('sort_by') === 'date')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2 cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.income', array_merge(request()->query(), ['sort_by' => 'income_concept', 'sort_dir' => request('sort_by') === 'income_concept' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
                                Concepto
                                @if(request('sort_by') === 'income_concept')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2">Categoría</th>
                        <th class="px-3 py-2">Modo de pago</th>
                        <th class="px-3 py-2 text-right cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.income', array_merge(request()->query(), ['sort_by' => 'amount', 'sort_dir' => request('sort_by') === 'amount' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center justify-end gap-1">
                                Importe
                                @if(request('sort_by') === 'amount')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 align-middle reporting-month-cell" data-entry-id="{{ $entry->id }}" data-current-value="{{ e($entry->getReportingMonth()) }}" data-current-label="{{ ucfirst(\Carbon\Carbon::createFromFormat('!Y-m', $entry->getReportingMonth())->locale('es')->isoFormat('MMMM YYYY')) }}" data-readonly="{{ ($entry->income_category ?? '') === 'cierre_diario' ? '1' : '0' }}">
                                @if(($entry->income_category ?? '') === 'cierre_diario')
                                    <span class="text-slate-600">{{ ucfirst(\Carbon\Carbon::createFromFormat('!Y-m', $entry->getReportingMonth())->locale('es')->isoFormat('MMMM YYYY')) }}</span>
                                @elseif(auth()->user()->hasPermission('financial.income.edit') || auth()->user()->hasPermission('financial.registros.edit'))
                                    <span class="reporting-month-view inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700 cursor-pointer hover:ring-2 hover:ring-brand-300 hover:ring-offset-1 min-w-[2rem]" title="Clic para cambiar mes">
                                        {{ ucfirst(\Carbon\Carbon::createFromFormat('!Y-m', $entry->getReportingMonth())->locale('es')->isoFormat('MMMM YYYY')) }}
                                    </span>
                                    <select class="reporting-month-edit hidden w-full max-w-[160px] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2" data-entry-id="{{ $entry->id }}">
                                        @php $monthBase = now()->startOfMonth(); @endphp
                                        @for($i = 12; $i >= 0; $i--)
                                            @php $m = $monthBase->copy()->addMonthsNoOverflow($i); $val = $m->format('Y-m'); $lab = ucfirst($m->locale('es')->isoFormat('MMMM YYYY')); @endphp
                                            <option value="{{ $val }}" {{ $entry->getReportingMonth() === $val ? 'selected' : '' }}>{{ $lab }}</option>
                                        @endfor
                                        @for($i = 1; $i <= 24; $i++)
                                            @php $m = $monthBase->copy()->subMonthsNoOverflow($i); $val = $m->format('Y-m'); $lab = ucfirst($m->locale('es')->isoFormat('MMMM YYYY')); @endphp
                                            <option value="{{ $val }}" {{ $entry->getReportingMonth() === $val ? 'selected' : '' }}>{{ $lab }}</option>
                                        @endfor
                                    </select>
                                @else
                                    <span class="text-slate-600">{{ ucfirst(\Carbon\Carbon::createFromFormat('!Y-m', $entry->getReportingMonth())->locale('es')->isoFormat('MMMM YYYY')) }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $entry->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">{{ $entry->store->name }}</td>
                            <td class="px-3 py-2">{{ $entry->income_concept ?? $entry->concept ?? '—' }}</td>
                            <td class="px-3 py-2">
                                @if($entry->income_category)
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ ucfirst(str_replace('_', ' ', $entry->income_category)) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @php
                                    $paymentMethod = '—';
                                    // Usar el campo expense_payment_method si existe, sino inferir del concepto
                                    if ($entry->expense_payment_method) {
                                        if ($entry->expense_payment_method === 'cash') {
                                            $paymentMethod = 'Efectivo';
                                        } elseif (in_array($entry->expense_payment_method, ['bank', 'card', 'datafono', 'tarjeta'])) {
                                            $paymentMethod = 'Banco';
                                        }
                                    } else {
                                        // Fallback: inferir del concepto si no hay método de pago guardado
                                        $concept = strtolower($entry->income_concept ?? $entry->concept ?? '');
                                        if (str_contains($concept, 'efectivo') || str_contains($concept, 'cash')) {
                                            $paymentMethod = 'Efectivo';
                                        } elseif (str_contains($concept, 'banco') || str_contains($concept, 'tarjeta') || str_contains($concept, 'bank') || str_contains($concept, 'tpv') || str_contains($concept, 'datáfono') || str_contains($concept, 'datafono')) {
                                            $paymentMethod = 'Banco';
                                        }
                                    }
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $paymentMethod === 'Efectivo' ? 'bg-emerald-100 text-emerald-700' : ($paymentMethod === 'Banco' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500') }}">
                                    {{ $paymentMethod }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-emerald-700 whitespace-nowrap">
                                {{ number_format($entry->amount, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('financial.show', [$entry->id, 'return_to' => request()->fullUrl()]) }}" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100" title="Ver">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    @if(auth()->user()->hasPermission('financial.income.edit'))
                                    <a href="{{ route('financial.edit', $entry->id) }}?return_to={{ urlencode(request()->fullUrl()) }}" class="rounded-lg p-1.5 text-brand-600 hover:bg-brand-50" title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('financial.income.delete'))
                                    <form method="POST" action="{{ route('financial.destroy', $entry->id) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50" title="Eliminar">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-slate-500">No hay registros</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            @if(method_exists($entries, 'links'))
                {{ $entries->links() }}
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Menú exportación (se despliega al clicar Exportar)
        document.querySelectorAll('[data-export-menu]').forEach(function(root) {
            const trigger = root.querySelector('[data-export-trigger]');
            const dropdown = root.querySelector('[data-export-dropdown]');
            const form = root.querySelector('[data-export-form]');
            const formatInput = root.querySelector('[data-export-format]');
            if (!trigger || !dropdown || !form || !formatInput) return;

            function close() {
                dropdown.classList.add('hidden');
            }
            function toggle() {
                dropdown.classList.toggle('hidden');
            }

            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                toggle();
            });

            dropdown.querySelectorAll('[data-export-option]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const fmt = btn.getAttribute('data-export-option');
                    formatInput.value = fmt || 'xlsx';
                    form.submit();
                });
            });

            document.addEventListener('click', function(e) {
                if (!root.contains(e.target)) close();
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') close();
            });
        });

        const periodSelect = document.getElementById('periodSelect');
        const customDateRange = document.getElementById('customDateRange');
        
        if (periodSelect && customDateRange) {
            function toggleCustomDates() {
                if (periodSelect.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                }
            }
            toggleCustomDates();
            periodSelect.addEventListener('change', toggleCustomDates);
        }

        // Edición inline de mes correspondiente
        const baseUrl = '{{ url("/financial") }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        document.querySelectorAll('.reporting-month-cell').forEach(function(cell) {
            if (cell.getAttribute('data-readonly') === '1') return;
            const viewSpan = cell.querySelector('.reporting-month-view');
            const selectEl = cell.querySelector('.reporting-month-edit');
            if (!viewSpan || !selectEl) return;

            viewSpan.addEventListener('click', function(e) {
                e.stopPropagation();
                viewSpan.classList.add('hidden');
                selectEl.classList.remove('hidden');
                selectEl.focus();
            });

            function closeEdit() {
                selectEl.classList.add('hidden');
                viewSpan.classList.remove('hidden');
            }

            selectEl.addEventListener('change', function() {
                const entryId = selectEl.getAttribute('data-entry-id');
                const value = selectEl.value;
                fetch(baseUrl + '/' + entryId + '/reporting-month', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ reporting_month: value || null })
                })
                .then(function(r) { if (!r.ok) throw new Error('Error'); return r.json(); })
                .then(function(data) {
                    viewSpan.textContent = data.label || '—';
                    cell.setAttribute('data-current-value', data.reporting_month || '');
                    cell.setAttribute('data-current-label', data.label || '—');
                    closeEdit();
                })
                .catch(function() { alert('No se pudo actualizar.'); closeEdit(); });
            });

            selectEl.addEventListener('blur', function() { setTimeout(closeEdit, 150); });
        });
    });
</script>
@endsection
