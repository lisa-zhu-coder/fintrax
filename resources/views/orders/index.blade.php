@extends('layouts.app')

@section('title', 'Pedidos')

@section('content')
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
                        <th class="px-3 py-3">Proveedor</th>
                        <th class="px-3 py-3">Tipo</th>
                        <th class="px-3 py-3 text-right">Pedidos</th>
                        <th class="px-3 py-3 text-right">Importe total</th>
                        <th class="px-3 py-3 text-right">Importe pagado</th>
                        <th class="px-3 py-3 text-right">Importe pendiente</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliersWithStats as $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-3">
                                <a href="{{ route('orders.supplier', $row['supplier']) }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">
                                    {{ $row['supplier']->name }}
                                </a>
                            </td>
                            <td class="px-3 py-3">
                                @if($row['supplier']->type)
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ \App\Models\Supplier::TYPES[$row['supplier']->type] ?? ucfirst(str_replace('_', ' ', $row['supplier']->type)) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right">{{ $row['total_orders'] }}</td>
                            <td class="px-3 py-3 text-right font-semibold whitespace-nowrap">{{ number_format($row['total_amount'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-3 text-right text-emerald-700 whitespace-nowrap">{{ number_format($row['total_paid'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-3 text-right font-semibold {{ $row['total_pending'] > 0 ? 'text-amber-700' : 'text-emerald-700' }} whitespace-nowrap">
                                {{ number_format($row['total_pending'], 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-3">
                                <a href="{{ route('orders.supplier', $row['supplier']) }}" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Ver pedidos
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-slate-500">
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
