{{--
  Selector de tienda para filtros: si el usuario solo tiene una tienda asignada, se muestra solo el nombre (sin opción de cambiar).
  Uso: @include('partials.store-filter-select', ['name' => 'store', 'stores' => $stores, 'selected' => request('store'), 'label' => 'Tienda', 'showAllOption' => true])
  name: 'store' o 'store_id'
--}}
@php
    $enforcedStoreId = auth()->user()->getEnforcedStoreId();
    $inputName = $name ?? 'store';
    $labelText = $label ?? 'Tienda';
@endphp
@if($enforcedStoreId !== null)
    @php
        $enforcedStore = $stores->firstWhere('id', $enforcedStoreId) ?? $stores->first();
    @endphp
    <label class="block">
        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $labelText }}</span>
        <div class="mt-1 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 px-3 py-2 text-sm text-slate-700 dark:text-slate-300">
            {{ $enforcedStore->name ?? '—' }}
        </div>
        <input type="hidden" name="{{ $inputName }}" value="{{ $enforcedStoreId }}">
    </label>
@else
    @php
        $allValue = ($inputName === 'store_id') ? '' : 'all';
        $isAllSelected = in_array($selected ?? '', ['all', ''], true) || ($selected ?? null) === null;
    @endphp
    <label class="block">
        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $labelText }}</span>
        <select name="{{ $inputName }}" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none ring-brand-200 focus:ring-4">
            @if($showAllOption ?? true)
                <option value="{{ $allValue }}" {{ $isAllSelected ? 'selected' : '' }}>Todas las tiendas</option>
            @endif
            @foreach($stores as $store)
                <option value="{{ $store->id }}" {{ ($selected ?? '') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
            @endforeach
        </select>
    </label>
@endif
