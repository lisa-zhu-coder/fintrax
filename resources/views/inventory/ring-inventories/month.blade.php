@extends('layouts.app')

@section('title', 'Inventario de anillos — ' . $store->name . ' — ' . $monthName . ' ' . $year)

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('ring-inventories.index', ['year' => $year]) }}" class="hover:text-brand-600">Inventario de anillos</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('ring-inventories.store-months', ['store' => $store, 'year' => $year]) }}" class="hover:text-brand-600">{{ $store->name }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $monthName }} {{ $year }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $store->name }} — {{ $monthName }} {{ $year }}</h1>
                <p class="text-sm text-slate-500">Rellena los datos en la tabla. Usa «Editar» en cada fila para poder modificar.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ring-inventories.store-months', ['store' => $store, 'year' => $year]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Meses</a>
            </div>
        </div>
    </header>

    {{-- Resumen solo con registros de turno cierre --}}
    <div class="rounded-2xl border-2 border-amber-100 bg-amber-50/30 p-4 ring-1 ring-amber-100">
        <h2 class="text-sm font-semibold text-amber-900 mb-3">Resumen del mes (solo registros de cierre)</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <span class="text-xs text-slate-600">Anillos vendidos</span>
                <p class="text-xl font-semibold text-slate-900">{{ number_format($totalSold, 0, ',', '.') }}</p>
            </div>
            <div>
                <span class="text-xs text-slate-600">Taras</span>
                <p class="text-xl font-semibold text-slate-900">{{ number_format($totalTara ?? 0, 0, ',', '.') }}</p>
            </div>
            <div>
                <span class="text-xs text-slate-600">Discrepancia</span>
                <p class="text-xl font-semibold {{ $totalDiscrepancy != 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ number_format($totalDiscrepancy, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-auto max-h-[70vh]">
            <table class="min-w-full text-left text-sm ring-inventory-table">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 bg-slate-50 border-b border-slate-200">Fecha</th>
                        <th class="px-3 py-2 bg-slate-50 border-b border-slate-200">Turno</th>
                        <th class="px-3 py-2 text-right w-24 bg-slate-50 border-b border-slate-200">Inicial</th>
                        <th class="px-3 py-2 text-right w-24 bg-slate-50 border-b border-slate-200">Reposición</th>
                        <th class="px-3 py-2 text-right w-24 bg-slate-50 border-b border-slate-200">Taras</th>
                        <th class="px-3 py-2 text-right w-24 bg-slate-50 border-b border-slate-200">Vendidos</th>
                        <th class="px-3 py-2 text-right w-24 bg-slate-50 border-b border-slate-200">Final</th>
                        <th class="px-3 py-2 text-right w-24 bg-slate-50 border-b border-slate-200">Discrepancia</th>
                        <th class="px-3 py-2 w-32 bg-slate-50 border-b border-slate-200">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($rows as $row)
                        @php
                            $r = $row->record;
                            $formId = 'form-' . str_replace('-', '_', $row->date_str) . '_' . $row->shift;
                            $rowId = 'row-' . str_replace('-', '_', $row->date_str) . '_' . $row->shift;
                            $isCierre = $row->shift === 'cierre';
                        @endphp
                        <tr class="ring-row {{ $isCierre ? 'bg-slate-100 hover:bg-slate-200' : 'hover:bg-slate-50' }}" id="{{ $rowId }}" data-row-key="{{ $row->date_str }}-{{ $row->shift }}">
                            <td class="px-3 py-2 whitespace-nowrap">{{ $row->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $row->shift_label }}</td>
                            <td class="px-3 py-2 text-right">
                                @if($isCierre)
                                    {{-- Cierre: Inicial calculado = Inicial + Reposición del cambio de turno del mismo día (solo lectura) --}}
                                    <span class="view-val">{{ $row->display_initial !== null ? number_format($row->display_initial, 0, ',', '.') : '—' }}</span>
                                    <span class="edit-inp hidden text-right text-sm" data-initial-readonly>{{ $row->display_initial !== null ? number_format($row->display_initial, 0, ',', '.') : '—' }}</span>
                                    <input form="{{ $formId }}" name="initial_quantity" type="hidden" value="{{ $row->display_initial ?? '' }}">
                                @else
                                    <span class="view-val">{{ $r && $r->initial_quantity !== null ? number_format($r->initial_quantity, 0, ',', '.') : '—' }}</span>
                                    <input form="{{ $formId }}" name="initial_quantity" type="number" min="0" class="edit-inp hidden w-full rounded border border-slate-200 px-2 py-1 text-right text-sm" value="{{ $r?->initial_quantity ?? '' }}" placeholder="—">
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <span class="view-val">{{ $r && $r->replenishment_quantity !== null ? number_format($r->replenishment_quantity, 0, ',', '.') : '—' }}</span>
                                <input form="{{ $formId }}" name="replenishment_quantity" type="number" class="edit-inp hidden w-full rounded border border-slate-200 px-2 py-1 text-right text-sm" value="{{ $r?->replenishment_quantity ?? '' }}" placeholder="—">
                            </td>
                            <td class="px-3 py-2 text-right">
                                <span class="view-val">{{ $r && $r->tara_quantity !== null ? number_format($r->tara_quantity, 0, ',', '.') : '—' }}</span>
                                <input form="{{ $formId }}" name="tara_quantity" type="number" min="0" class="edit-inp hidden w-full rounded border border-slate-200 px-2 py-1 text-right text-sm" value="{{ $r?->tara_quantity ?? '' }}" placeholder="—">
                            </td>
                            <td class="px-3 py-2 text-right">
                                <span class="view-val">{{ $r && $r->sold_quantity !== null ? number_format($r->sold_quantity, 0, ',', '.') : '—' }}</span>
                                <input form="{{ $formId }}" name="sold_quantity" type="number" class="edit-inp hidden w-full rounded border border-slate-200 px-2 py-1 text-right text-sm" value="{{ $r?->sold_quantity ?? '' }}" placeholder="—">
                            </td>
                            <td class="px-3 py-2 text-right">
                                <span class="view-val">{{ $r && $r->final_quantity !== null ? number_format($r->final_quantity, 0, ',', '.') : '—' }}</span>
                                <input form="{{ $formId }}" name="final_quantity" type="number" min="0" class="edit-inp hidden w-full rounded border border-slate-200 px-2 py-1 text-right text-sm" value="{{ $r?->final_quantity ?? '' }}" placeholder="—">
                            </td>
                            <td class="px-3 py-2 text-right font-medium discrepancy-cell">
                                <span class="view-val {{ $row->display_discrepancy !== null && $row->display_discrepancy != 0 ? 'text-rose-600' : '' }}">{{ $row->display_discrepancy !== null ? number_format($row->display_discrepancy, 0, ',', '.') : '—' }}</span>
                                <span class="edit-inp discrepancy-calc hidden">—</span>
                            </td>
                            <td class="px-3 py-2">
                                <span class="view-val">
                                    @if(auth()->user()->hasPermission('inventory.rings.edit') || auth()->user()->hasPermission('inventory.rings.create'))
                                        <button type="button" class="btn-edit rounded-lg px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50" data-row-id="{{ $rowId }}">Editar</button>
                                    @else
                                        —
                                    @endif
                                </span>
                                @if(auth()->user()->hasPermission('inventory.rings.edit') || auth()->user()->hasPermission('inventory.rings.create'))
                                    <span class="edit-inp hidden flex items-center gap-1">
                                        @if($r)
                                            <form id="{{ $formId }}" method="POST" action="{{ route('ring-inventories.update', $r) }}" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="store_id" value="{{ $store->id }}">
                                                <input type="hidden" name="date" value="{{ $row->date_str }}">
                                                <input type="hidden" name="shift" value="{{ $row->shift }}">
                                                <button type="submit" class="rounded-lg px-2 py-1 text-xs font-medium text-white bg-brand-600 hover:bg-brand-700">Guardar</button>
                                            </form>
                                        @else
                                            <form id="{{ $formId }}" method="POST" action="{{ route('ring-inventories.store') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="store_id" value="{{ $store->id }}">
                                                <input type="hidden" name="date" value="{{ $row->date_str }}">
                                                <input type="hidden" name="shift" value="{{ $row->shift }}">
                                                <button type="submit" class="rounded-lg px-2 py-1 text-xs font-medium text-white bg-brand-600 hover:bg-brand-700">Guardar</button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn-cancel rounded-lg px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100" data-row-id="{{ $rowId }}">Cancelar</button>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const table = document.querySelector('.ring-inventory-table');
    if (!table) return;

    function parseNum(val) {
        if (val === '' || val === null || val === undefined) return 0;
        const n = parseInt(val, 10);
        return isNaN(n) ? 0 : n;
    }

    function updateDiscrepancy(tr) {
        const initial = parseNum(tr.querySelector('input[name="initial_quantity"]')?.value);
        const replenishment = parseNum(tr.querySelector('input[name="replenishment_quantity"]')?.value);
        const tara = parseNum(tr.querySelector('input[name="tara_quantity"]')?.value);
        const sold = parseNum(tr.querySelector('input[name="sold_quantity"]')?.value);
        const final = parseNum(tr.querySelector('input[name="final_quantity"]')?.value);
        const disc = initial + replenishment + tara + sold - final;
        const cell = tr.querySelector('.discrepancy-calc');
        if (cell) {
            cell.textContent = disc.toLocaleString('es-ES');
            cell.classList.toggle('text-rose-600', disc !== 0);
        }
    }

    function setRowEditing(rowId, editing) {
        const tr = document.getElementById(rowId);
        if (!tr) return;
        const viewCells = tr.querySelectorAll('.view-val');
        const editCells = tr.querySelectorAll('.edit-inp');
        if (editing) {
            viewCells.forEach(el => el.classList.add('hidden'));
            editCells.forEach(el => el.classList.remove('hidden'));
            tr.classList.add('bg-brand-50');
            updateDiscrepancy(tr);
        } else {
            viewCells.forEach(el => el.classList.remove('hidden'));
            editCells.forEach(el => el.classList.add('hidden'));
            tr.classList.remove('bg-brand-50');
        }
    }

    function closeAllEditing() {
        table.querySelectorAll('.ring-row').forEach(tr => {
            const rowId = tr.id;
            if (rowId) setRowEditing(rowId, false);
        });
    }

    table.addEventListener('click', function(e) {
        const btnEdit = e.target.closest('.btn-edit');
        const btnCancel = e.target.closest('.btn-cancel');
        if (btnEdit) {
            e.preventDefault();
            const rowId = btnEdit.getAttribute('data-row-id');
            closeAllEditing();
            setRowEditing(rowId, true);
        } else if (btnCancel) {
            e.preventDefault();
            const rowId = btnCancel.getAttribute('data-row-id');
            setRowEditing(rowId, false);
        }
    });

    table.addEventListener('input', function(e) {
        if (!e.target.matches('.edit-inp')) return;
        const tr = e.target.closest('.ring-row');
        if (tr) updateDiscrepancy(tr);
    });
})();
</script>
@endpush
@endsection
