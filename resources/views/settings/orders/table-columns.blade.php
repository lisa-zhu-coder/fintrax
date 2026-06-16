@extends('layouts.app')

@section('title', 'Columnas de tablas de pedidos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        @include('partials.settings-section-tabs', ['group' => 'orders'])
        <div>
            <h1 class="text-lg font-semibold">Columnas de tablas de pedidos</h1>
            <p class="text-sm text-slate-500">Activa, desactiva y ordena las columnas de cada cuadro del módulo de pedidos para la empresa {{ $company->name }}.</p>
        </div>
    </header>

    <form method="POST" action="{{ route('order-table-settings.update') }}" class="space-y-6" id="order-table-settings-form">
        @csrf
        @method('PUT')

        @foreach($tableDefinitions as $tableKey => $table)
        <section class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100" data-table-section="{{ $tableKey }}">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">{{ $table['label'] }}</h2>
                    <p class="text-xs text-slate-500">Arrastra o usa las flechas para cambiar el orden de las columnas.</p>
                </div>
            </div>

            <ul class="space-y-2" data-column-list="{{ $tableKey }}">
                @foreach($tableConfig[$tableKey] ?? [] as $index => $column)
                    @php
                        $columnMeta = $table['columns'][$column['key']] ?? null;
                        $isLocked = (bool) ($columnMeta['locked'] ?? false);
                    @endphp
                    <li class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2.5" data-column-row draggable="{{ $isLocked ? 'false' : 'true' }}">
                        <input type="hidden" name="tables[{{ $tableKey }}][{{ $index }}][key]" value="{{ $column['key'] }}" data-column-key>
                        <input type="hidden" name="tables[{{ $tableKey }}][{{ $index }}][visible]" value="{{ ($column['visible'] ?? true) ? '1' : '0' }}" data-column-visible-input>

                        <span class="cursor-grab text-slate-400 {{ $isLocked ? 'opacity-30' : '' }}" title="Arrastrar" @if(!$isLocked) draggable="true" @endif>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                        </span>

                        <label class="flex flex-1 items-center gap-3 {{ $isLocked ? 'opacity-70' : 'cursor-pointer' }}">
                            <input type="checkbox"
                                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                   data-column-visible-toggle
                                   {{ ($column['visible'] ?? true) ? 'checked' : '' }}
                                   {{ $isLocked ? 'checked disabled' : '' }}>
                            <span class="text-sm font-medium text-slate-800">{{ $columnMeta['label'] ?? $column['key'] }}</span>
                            @if($isLocked)
                                <span class="text-xs text-slate-500">(siempre visible)</span>
                            @endif
                        </label>

                        <div class="flex items-center gap-1">
                            <button type="button" class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-100 disabled:opacity-40" data-move-up title="Subir" {{ $isLocked ? 'disabled' : '' }}>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 15l6-6 6 6"/></svg>
                            </button>
                            <button type="button" class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-100 disabled:opacity-40" data-move-down title="Bajar" {{ $isLocked ? 'disabled' : '' }}>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                Guardar configuración
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('order-table-settings-form');
    if (!form) return;

    function reindexList(list) {
        const tableKey = list.dataset.columnList;
        list.querySelectorAll('[data-column-row]').forEach(function (row, index) {
            row.querySelector('[data-column-key]').name = 'tables[' + tableKey + '][' + index + '][key]';
            row.querySelector('[data-column-visible-input]').name = 'tables[' + tableKey + '][' + index + '][visible]';
        });
    }

    form.querySelectorAll('[data-column-list]').forEach(function (list) {
        list.addEventListener('click', function (event) {
            const row = event.target.closest('[data-column-row]');
            if (!row) return;

            if (event.target.closest('[data-move-up]')) {
                const prev = row.previousElementSibling;
                if (prev) {
                    list.insertBefore(row, prev);
                    reindexList(list);
                }
            }

            if (event.target.closest('[data-move-down]')) {
                const next = row.nextElementSibling;
                if (next) {
                    list.insertBefore(next, row);
                    reindexList(list);
                }
            }
        });

        list.querySelectorAll('[data-column-visible-toggle]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                const row = checkbox.closest('[data-column-row]');
                const hidden = row.querySelector('[data-column-visible-input]');
                hidden.value = checkbox.checked ? '1' : '0';
            });
        });

        let draggedRow = null;

        list.addEventListener('dragstart', function (event) {
            const row = event.target.closest('[data-column-row]');
            if (!row || row.getAttribute('draggable') === 'false') {
                event.preventDefault();
                return;
            }
            draggedRow = row;
            row.classList.add('opacity-50');
            event.dataTransfer.effectAllowed = 'move';
        });

        list.addEventListener('dragend', function () {
            if (draggedRow) {
                draggedRow.classList.remove('opacity-50');
                draggedRow = null;
                reindexList(list);
            }
        });

        list.addEventListener('dragover', function (event) {
            event.preventDefault();
            const row = event.target.closest('[data-column-row]');
            if (!row || !draggedRow || row === draggedRow) return;
            const rect = row.getBoundingClientRect();
            const after = event.clientY > rect.top + rect.height / 2;
            if (after) {
                row.after(draggedRow);
            } else {
                row.before(draggedRow);
            }
        });
    });

    form.addEventListener('submit', function () {
        form.querySelectorAll('[data-column-list]').forEach(reindexList);
    });
});
</script>
@endsection
