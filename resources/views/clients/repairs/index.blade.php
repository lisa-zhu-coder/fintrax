@extends('layouts.app')

@section('title', 'Reparaciones')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <h1 class="text-lg font-semibold">Reparaciones</h1>
            <p class="text-sm text-slate-500">Selecciona una tienda para ver y gestionar las reparaciones.</p>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        @if($stores->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center text-slate-500">
                No hay tiendas disponibles
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left">Tienda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($stores as $s)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-3">
                                <a href="{{ route('clients.repairs.store', $s) }}" class="font-semibold text-slate-900 hover:text-brand-600 cursor-pointer underline-offset-2 hover:underline">{{ $s->name }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
