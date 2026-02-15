@php
    $canSeeQuickActions = auth()->user()->hasPermission('dashboard.quick_actions.view');
    $canDailyClose = auth()->user()->hasPermission('financial.daily_closes.create');
    $canCashWithdrawal = auth()->user()->hasPermission('treasury.cash_control.view');
    $canCashDeposit = auth()->user()->hasPermission('treasury.cash_wallets.create');
    $canCreateOrder = auth()->user()->hasPermission('orders.main.create');
    $defaultStoreId = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() ? '' : (auth()->user()->getEnforcedStoreId() ?? '');
@endphp
@if($canSeeQuickActions)
<div class="widget-content">
    <div class="flex flex-wrap gap-3">
        @if($canDailyClose)
        <a href="{{ route('financial.create', array_filter(['type' => 'daily_close', 'date' => now()->format('Y-m-d'), 'store_id' => $defaultStoreId])) }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Crear cierre diario
        </a>
        @endif
        @if($canCashWithdrawal)
        <button type="button" onclick="document.getElementById('modalRetirarEfectivo').classList.remove('hidden')" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2h-2m-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Retirar efectivo
        </button>
        @endif
        @if($canCashDeposit)
        <button type="button" onclick="document.getElementById('modalIngresarDinero').classList.remove('hidden')" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Ingresar dinero
        </button>
        @endif
        @if($canCreateOrder)
        <a href="{{ route('orders.create') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Registrar pedido
        </a>
        @endif
    </div>
</div>
@endif
