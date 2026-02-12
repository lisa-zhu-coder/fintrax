@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Proveedores</h1>
                <p class="text-sm text-slate-500">Gestiona los proveedores para los pedidos</p>
            </div>
            @if(auth()->user()->hasPermission('admin.suppliers.create') || auth()->user()->hasPermission('orders.main.create'))
            <a href="{{ route('suppliers.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Añadir proveedor
            </a>
            @endif
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Nombre</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">CIF</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2 text-right">Pedidos</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $supplier)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-semibold">{{ $supplier->name }}</td>
                        <td class="px-3 py-2">{{ $supplier->cif ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $supplier->email ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ $supplier->orders_count }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                @if(auth()->user()->hasPermission('orders.main.view'))
                                <a href="{{ route('orders.supplier', $supplier) }}" class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100">Ver pedidos</a>
                                @endif
                                @if(auth()->user()->hasPermission('admin.suppliers.view'))
                                <a href="{{ route('suppliers.show', $supplier) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Ver</a>
                                @endif
                                @if(auth()->user()->hasPermission('admin.suppliers.edit'))
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Editar</a>
                                @endif
                                @if(auth()->user()->hasPermission('admin.suppliers.delete') && $supplier->orders_count == 0)
                                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline" onsubmit="return confirm('¿Eliminar este proveedor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Eliminar</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-slate-500">No hay proveedores registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
