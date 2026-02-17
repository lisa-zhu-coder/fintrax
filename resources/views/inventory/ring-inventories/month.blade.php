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
        <div class="overflow-auto max-h-[70vh] table-records-mobile">
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
                        <th class="px-3 py-2 w-28 bg-slate-50 border-b border-slate-200">Acciones</th>
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
                                    {{-- Cierre: Inicial = Inicial + Reposición del cambio de turno del mismo día (solo lectura) --}}
                                    <span class="view-val">{{ $row->display_initial !== null ? number_format($row->display_initial, 0, ',', '.') : '—' }}</span>
                                    <span class="edit-inp hidden text-right text-sm" data-initial-readonly>{{ $row->display_initial !== null ? number_format($row->display_initial, 0, ',', '.') : '—' }}</span>
                                    <input form="{{ $formId }}" name="initial_quantity" type="hidden" value="{{ $row->display_initial ?? '' }}">
                                @else
                                    {{-- Cambio de turno: Inicial = Final del cierre del día anterior (solo lectura) --}}
                                    <span class="view-val">{{ $row->display_initial !== null ? number_format($row->display_initial, 0, ',', '.') : '—' }}</span>
                                    <span class="edit-inp hidden text-right text-sm" data-initial-readonly>{{ $row->display_initial !== null ? number_format($row->display_initial, 0, ',', '.') : '—' }}</span>
                                    <input form="{{ $formId }}" name="initial_quantity" type="hidden" value="{{ $row->display_initial ?? '' }}">
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
                                @if(auth()->user()->hasPermission('inventory.rings.edit') || auth()->user()->hasPermission('inventory.rings.create'))
                                    <div class="relative inline-flex items-center gap-1">
                                        <span class="view-val flex items-center gap-1">
                                            <button type="button" class="btn-edit rounded-lg p-1.5 text-brand-600 hover:bg-brand-50" data-row-id="{{ $rowId }}" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/></svg>
                                            </button>
                                            <button type="button" class="btn-comment rounded-lg p-1.5 {{ $r && $r->comment ? 'text-amber-600 hover:bg-amber-50' : 'text-slate-500 hover:bg-slate-100' }}" data-row-id="{{ $rowId }}" data-has-comment="{{ $r && $r->comment ? '1' : '0' }}" title="{{ $r && $r->comment ? 'Ver comentario' : 'Añadir comentario' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                            </button>
                                        </span>
                                        <div class="comment-popover hidden absolute right-0 top-full z-20 mt-1 min-w-[200px] max-w-[280px] rounded-lg border border-slate-200 bg-white p-3 shadow-lg" data-row-id="{{ $rowId }}">
                                            <div class="comment-popover-view text-sm text-slate-700 whitespace-pre-wrap">{{ $r && $r->comment ? e($r->comment) : 'Sin comentario' }}</div>
                                            <textarea form="{{ $formId }}" name="comment" class="comment-popover-edit hidden mt-2 w-full rounded border border-slate-200 px-2 py-1 text-sm" rows="2" maxlength="2000" placeholder="Comentario...">{{ $r?->comment ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    <span class="edit-inp hidden flex items-center gap-1">
                                        <button type="button" class="btn-comment rounded-lg p-1.5 {{ $r && $r->comment ? 'text-amber-600 hover:bg-amber-50' : 'text-slate-500 hover:bg-slate-100' }}" data-row-id="{{ $rowId }}" data-edit-mode="1" title="Comentario">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        </button>
                                        @if($r)
                                            <form id="{{ $formId }}" method="POST" action="{{ route('ring-inventories.update', $r) }}" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="store_id" value="{{ $store->id }}">
                                                <input type="hidden" name="date" value="{{ $row->date_str }}">
                                                <input type="hidden" name="shift" value="{{ $row->shift }}">
                                                <button type="submit" class="rounded-lg p-1.5 text-white bg-brand-600 hover:bg-brand-700" title="Guardar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </form>
                                        @else
                                            <form id="{{ $formId }}" method="POST" action="{{ route('ring-inventories.store') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="store_id" value="{{ $store->id }}">
                                                <input type="hidden" name="date" value="{{ $row->date_str }}">
                                                <input type="hidden" name="shift" value="{{ $row->shift }}">
                                                <button type="submit" class="rounded-lg p-1.5 text-white bg-brand-600 hover:bg-brand-700" title="Guardar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn-cancel rounded-lg p-1.5 text-slate-600 hover:bg-slate-100" data-row-id="{{ $rowId }}" title="Cancelar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
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

    function closeAllCommentPopovers() {
        table.querySelectorAll('.comment-popover').forEach(p => p.classList.add('hidden'));
    }

    table.addEventListener('click', function(e) {
        const btnEdit = e.target.closest('.btn-edit');
        const btnCancel = e.target.closest('.btn-cancel');
        const btnComment = e.target.closest('.btn-comment');
        if (btnEdit) {
            e.preventDefault();
            const rowId = btnEdit.getAttribute('data-row-id');
            closeAllCommentPopovers();
            closeAllEditing();
            setRowEditing(rowId, true);
        } else if (btnCancel) {
            e.preventDefault();
            const rowId = btnCancel.getAttribute('data-row-id');
            setRowEditing(rowId, false);
            closeAllCommentPopovers();
        } else if (btnComment) {
            e.preventDefault();
            const rowId = btnComment.getAttribute('data-row-id');
            const tr = document.getElementById(rowId);
            const popover = table.querySelector('.comment-popover[data-row-id="' + rowId + '"]');
            if (!popover) return;
            const viewDiv = popover.querySelector('.comment-popover-view');
            const editTa = popover.querySelector('.comment-popover-edit');
            const isOpen = !popover.classList.contains('hidden');
            closeAllCommentPopovers();
            if (!isOpen) {
                const isEditMode = tr && tr.classList.contains('bg-brand-50');
                if (isEditMode && editTa) {
                    viewDiv.classList.add('hidden');
                    editTa.classList.remove('hidden');
                } else {
                    viewDiv.classList.remove('hidden');
                    if (editTa) editTa.classList.add('hidden');
                }
                popover.classList.remove('hidden');
            }
        }
    });

    document.addEventListener('click', function(e) {
        if (!table.contains(e.target) && !e.target.closest('.comment-popover')) {
            closeAllCommentPopovers();
        }
    });

    table.addEventListener('input', function(e) {
        if (!e.target.matches('.edit-inp')) return;
        const tr = e.target.closest('.ring-row');
        if (tr) updateDiscrepancy(tr);
    });

    // Enter en un campo: ir al siguiente campo vacío de la fila (no enviar el formulario)
    table.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter') return;
        const active = document.activeElement;
        const tr = active.closest('.ring-row');
        if (!tr) return;
        const form = tr.querySelector('form');
        if (!form) return;
        const formId = form.id;
        const fields = tr.querySelectorAll('input[form="' + formId + '"]:not([type="hidden"]), textarea[form="' + formId + '"]');
        const list = Array.from(fields);
        const idx = list.indexOf(active);
        if (idx === -1) return;
        e.preventDefault();
        for (let i = idx + 1; i < list.length; i++) {
            if (list[i].value.trim() === '') {
                list[i].focus();
                return;
            }
        }
        for (let i = 0; i < idx; i++) {
            if (list[i].value.trim() === '') {
                list[i].focus();
                return;
            }
        }
        if (idx < list.length - 1) {
            list[idx + 1].focus();
        }
    });
})();
</script>
@endpush
@endsection
