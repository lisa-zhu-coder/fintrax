@extends('layouts.app')

@section('title', 'Préstamos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Préstamos</h1>
                <p class="text-sm text-slate-500">Control de préstamos financieros y comerciales</p>
            </div>
            @if(auth()->user()->hasPermission('loans.main.create'))
            <a href="{{ route('loans.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Nuevo préstamo
            </a>
            @endif
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        @if($loans->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-8 text-center text-slate-500">
                No hay préstamos. Crea uno para empezar.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-tl">Nombre</th>
                            <th class="px-3 py-2">Tipo</th>
                            <th class="px-3 py-2">Dirección</th>
                            <th class="px-3 py-2 text-right">Capital inicial</th>
                            <th class="px-3 py-2 text-right">Saldo</th>
                            <th class="px-3 py-2 rounded-tr text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($loans as $loan)
                            @php $balance = $loan->getBalance(); @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 font-medium text-slate-900">{{ $loan->name }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $loan->loanType->name ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @if($loan->direction === 'company_owes')
                                        <span class="text-amber-700">La empresa debe</span>
                                    @else
                                        <span class="text-emerald-700">La empresa presta</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">{{ number_format($loan->principal_amount, 2, ',', '.') }} €</td>
                                <td class="px-3 py-2 text-right font-semibold {{ $balance < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                    {{ number_format($balance, 2, ',', '.') }} €
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('loans.show', $loan) }}" class="rounded-lg px-2 py-1 text-brand-600 hover:bg-brand-50 font-medium">Ver</a>
                                    @if(auth()->user()->hasPermission('loans.main.edit'))
                                    <a href="{{ route('loans.edit', $loan) }}" class="rounded-lg px-2 py-1 text-slate-600 hover:bg-slate-100">Editar</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('loans.main.delete'))
                                    <form method="POST" action="{{ route('loans.destroy', $loan) }}" class="inline" onsubmit="return confirm('¿Eliminar este préstamo y todos sus pagos y cuotas? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg px-2 py-1 text-rose-600 hover:bg-rose-50 font-medium">Eliminar</button>
                                    </form>
                                    @endif
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
