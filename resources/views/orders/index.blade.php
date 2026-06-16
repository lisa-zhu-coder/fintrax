@extends('layouts.app')

@section('title', 'Pedidos')

@section('content')
@php
    use App\Support\OrderTableSettings;

    $ordersSortUrl = function (string $column, string $defaultDir = 'asc') {
        return route('orders.index', array_merge(request()->query(), [
            'sort_by' => $column,
            'sort_dir' => request('sort_by') === $column
                ? (request('sort_dir') === 'desc' ? 'asc' : 'desc')
                : $defaultDir,
        ]));
    };
    $suppliersListColumns = OrderTableSettings::visibleColumns('suppliers_list');
@endphp
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Pedidos</h1>
                <p class="text-sm text-slate-500">Proveedores con sus estadísticas de pedidos. Haz clic en un proveedor para ver sus pedidos.</p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->hasPermission('admin.suppliers.create') || auth()->user()->hasPermission('orders.main.create'))
                <a href="{{ route('suppliers.create') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir proveedor
                </a>
                @endif
                @if(auth()->user()->hasPermission('orders.main.create'))
                <a href="{{ route('orders.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir pedido
                </a>
                @endif
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100 mt-10">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        @foreach($suppliersListColumns as $column)
                            @include('partials.orders.table-header', [
                                'column' => $column,
                                'sortUrlCallback' => $ordersSortUrl,
                                'defaultDir' => in_array($column['sort_key'] ?? '', ['total_orders', 'total_amount', 'total_paid', 'total_pending'], true) ? 'desc' : 'asc',
                            ])
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliersWithStats as $row)
                        <tr class="hover:bg-slate-50">
                            @foreach($suppliersListColumns as $column)
                                @include('partials.orders.suppliers-list-cell', ['column' => $column, 'row' => $row])
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($suppliersListColumns) }}" class="px-3 py-8 text-center text-slate-500">
                                No hay proveedores con pedidos. Crea un proveedor en Administración → Proveedores y añade pedidos desde aquí.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
