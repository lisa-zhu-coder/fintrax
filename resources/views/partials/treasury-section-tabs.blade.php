@php
    $user = auth()->user();
    $tabs = collect();

    if (($group ?? null) === 'cash') {
        if ($user->hasPermission('treasury.cash_control.view')) {
            $tabs->push([
                'href' => route('financial.cash-control'),
                'label' => 'Control de efectivo',
                'active' => request()->routeIs('financial.cash-control*', 'financial.cash-withdrawals.*'),
            ]);
        }
        if ($user->hasPermission('treasury.cash_wallets.view')) {
            $tabs->push([
                'href' => route('cash-wallets.index'),
                'label' => 'Carteras / monederos',
                'active' => request()->routeIs('cash-wallets.*'),
            ]);
        }
    } elseif (($group ?? null) === 'bank') {
        if ($user->hasPermission('treasury.bank_control.view')) {
            $tabs->push([
                'href' => route('financial.bank-control'),
                'label' => 'Control de banco',
                'active' => request()->routeIs('financial.bank-control'),
            ]);
        }
        if ($user->hasPermission('treasury.bank_conciliation.view')) {
            $tabs->push([
                'href' => route('financial.bank-conciliation'),
                'label' => 'Conciliación bancaria',
                'active' => request()->routeIs('financial.bank-conciliation', 'financial.bank-import*', 'financial.bank-movements.*'),
            ]);
        }
    }
@endphp

@if($tabs->count() > 1)
<nav class="mb-4 flex flex-wrap gap-1 border-b border-slate-200 dark:border-slate-600" aria-label="Subsecciones">
    @foreach($tabs as $tab)
    <a href="{{ $tab['href'] }}"
       class="inline-flex items-center border-b-2 px-3 py-2 text-sm font-medium transition-colors -mb-px {{ $tab['active'] ? 'border-brand-600 text-brand-700 dark:text-brand-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:border-slate-500 dark:hover:text-slate-200' }}">
        {{ $tab['label'] }}
    </a>
    @endforeach
</nav>
@endif
