<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fintrax Smart Backoffice')</title>
    <meta name="description" content="Fintrax Smart Backoffice: gestión financiera, ventas, gastos y reportes por tienda.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Script anti-flash: aplicar tema antes del primer paint --}}
    <script>
        (function() {
            const stored = localStorage.getItem('theme') || 'system';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = stored === 'dark' || (stored === 'system' && prefersDark);
            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: "#eef8f7",
                            100: "#d6f0ed",
                            200: "#b0e4de",
                            300: "#7dd4ca",
                            400: "#45beb2",
                            500: "#25AD9F",
                            600: "#25AD9F",
                            700: "#1e9a8e",
                            800: "#224D5F",
                            900: "#1a3d4a",
                        },
                    },
                    boxShadow: {
                        soft: "0 1px 2px rgba(16,24,40,.06), 0 6px 16px rgba(16,24,40,.08)",
                    },
                },
            },
        };
    </script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
        th.cursor-pointer a {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
        }
        th.cursor-pointer:hover {
            background-color: rgb(248 250 252);
        }
        .dark th.cursor-pointer:hover {
            background-color: rgb(30 41 59);
        }
        /* Animación del icono de tema seleccionado */
        .theme-icon-active {
            animation: themeBounce 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .theme-icon-inactive {
            opacity: 0.5;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .theme-btn:hover .theme-icon-inactive {
            opacity: 0.8;
        }
        .theme-btn.active {
            background-color: rgb(255 255 255);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .dark .theme-btn.active {
            background-color: rgb(71 85 105);
        }
        @keyframes themeBounce {
            0% { transform: scale(0.8); opacity: 0.6; }
            60% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        /* Animación sol - rayos rotando */
        .theme-sun-rays {
            transform-origin: center;
            animation: sunPulse 3s ease-in-out infinite;
        }
        .theme-btn.active .theme-sun-rays {
            animation: sunPulse 1.5s ease-in-out infinite;
        }
        @keyframes sunPulse {
            0%, 100% { opacity: 0.9; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }
        /* Animación luna - brillo sutil */
        .theme-moon-glow {
            animation: moonGlow 4s ease-in-out infinite;
        }
        .theme-btn.active .theme-moon-glow {
            animation: moonGlow 2s ease-in-out infinite;
        }
        @keyframes moonGlow {
            0%, 100% { opacity: 0.85; filter: drop-shadow(0 0 2px currentColor); }
            50% { opacity: 1; filter: drop-shadow(0 0 6px currentColor); }
        }
        /* Animación sistema - transición */
        .theme-system-monitor {
            transition: all 0.3s ease;
        }
        .theme-btn.active .theme-system-monitor {
            animation: systemPulse 2s ease-in-out infinite;
        }
        @keyframes systemPulse {
            0%, 100% { opacity: 0.9; }
            50% { opacity: 1; }
        }
        th.cursor-pointer svg {
            transition: transform 0.2s;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 transition-colors duration-300">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-200 dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 p-4 flex flex-col transition-colors duration-300">
            <div class="mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white dark:bg-slate-700 ring-1 ring-slate-200/80 dark:ring-slate-600/80">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            {{-- Semicírculo inferior (verde azulado oscuro #224D5F) --}}
                            <path d="M 6 20 A 14 14 0 0 1 34 20" stroke="#224D5F" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                            {{-- Semicírculo superior (cian #25AD9F) --}}
                            <path d="M 34 20 A 14 14 0 0 1 6 20" stroke="#25AD9F" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                            {{-- Barras del gráfico --}}
                            <rect x="12" y="22" width="3" height="8" rx="1" fill="#224D5F"/>
                            <rect x="17" y="18" width="3" height="12" rx="1" fill="#2D5F6B"/>
                            <rect x="22" y="14" width="3" height="16" rx="1" fill="#25AD9F"/>
                            <rect x="27" y="10" width="3" height="20" rx="1" fill="#25AD9F"/>
                            {{-- Flecha ascendente --}}
                            <path d="M10 26 Q20 14 30 8" stroke="#25AD9F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            <path d="M26 10 L30 8 L28 12 Z" fill="#25AD9F"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg font-semibold text-[#224D5F] dark:text-brand-300 truncate">Fintrax</h1>
                        <p class="text-xs font-medium text-[#25AD9F] dark:text-brand-400 truncate">Smart Backoffice</p>
                    </div>
                </div>
            </div>

            @if(!empty($companyName))
            <div class="mb-4 rounded-xl bg-white/90 dark:bg-slate-700/90 px-3 py-2.5 ring-1 ring-slate-200/80 dark:ring-slate-600/80 shadow-sm">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Empresa</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $companyName }}</p>
                @if(auth()->user()->isSuperAdmin() || count(auth()->user()->getCompanyAccessCompanyIds()) > 1)
                <form method="POST" action="{{ route('company.exit') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 hover:underline">
                        Cambiar empresa
                    </button>
                </form>
                @endif
            </div>
            @endif
            
            <nav class="flex-1 space-y-1 overflow-y-auto">
                @if(auth()->user()->hasPermission('dashboard.main.view'))
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 22V12h6v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Dashboard
                </a>
                @endif

                @php
                    $canFinance = auth()->user()->hasAnyPermission([
                        'financial.registros.view', 'financial.income.view', 'financial.expenses.view', 'financial.daily_closes.view',
                        'treasury.cash_control.view', 'treasury.cash_wallets.view', 'treasury.bank_control.view', 'treasury.bank_conciliation.view', 'treasury.transfers.view',
                        'objectives.main.view', 'declared_sales.main.view',
                    ]);
                @endphp
                @if($canFinance)
                {{-- Finanzas (desplegable) --}}
                <div>
                    <button type="button" id="financeMenuToggle" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 10h12M4 14h9M19 6a7.7 7.7 0 0 0-5.2-2A7.9 7.9 0 0 0 6 12c0 4.4 3.5 8 7.8 8 2 0 3.8-.8 5.2-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Finanzas</span>
                        </div>
                        <svg id="financeMenuIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform duration-200 text-slate-400">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div id="financeMenuItems" class="hidden mt-1 space-y-1 pl-9">
                        @if(auth()->user()->hasPermission('financial.registros.view'))
                        <a href="{{ route('financial.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('financial.index') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Registros</a>
                        @endif
                        @if(auth()->user()->hasPermission('financial.income.view'))
                        <a href="{{ route('financial.income') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('financial.income') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Ingresos</a>
                        @endif
                        @if(auth()->user()->hasPermission('financial.expenses.view'))
                        <a href="{{ route('financial.expenses') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('financial.expenses') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Gastos</a>
                        @endif
                        @if(auth()->user()->hasPermission('financial.daily_closes.view'))
                        <a href="{{ route('financial.daily-closes') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('financial.daily-closes') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Cierres Diarios</a>
                        @endif

                        {{-- Control de flujos (submenú) - solo si tiene algún permiso de tesorería --}}
                        @if(auth()->user()->hasAnyPermission(['treasury.cash_control.view', 'treasury.cash_wallets.view', 'treasury.bank_control.view', 'treasury.bank_conciliation.view', 'treasury.transfers.view']))
                        <div>
                            <button type="button" id="flowControlMenuToggle" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                                <span>Control de flujos</span>
                                <svg id="flowControlMenuIcon" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform duration-200 text-slate-400 shrink-0">
                                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <div id="flowControlMenuItems" class="hidden mt-1 space-y-1 pl-4">
                                @if(auth()->user()->hasAnyPermission(['treasury.cash_control.view', 'treasury.cash_wallets.view']))
                                <div>
                                    <button type="button" id="cashControlMenuToggle" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                                        <span>Control de efectivo</span>
                                        <svg id="cashControlMenuIcon" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform duration-200 text-slate-400 shrink-0">
                                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <div id="cashControlMenuItems" class="hidden mt-1 space-y-1 pl-4">
                                        @if(auth()->user()->hasPermission('treasury.cash_control.view'))
                                        <a href="{{ route('financial.cash-control') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('financial.cash-control') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Control de efectivo</a>
                                        @endif
                                        @if(auth()->user()->hasPermission('treasury.cash_wallets.view'))
                                        <a href="{{ route('cash-wallets.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('cash-wallets.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Carteras / monederos</a>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                @if(auth()->user()->hasAnyPermission(['treasury.bank_control.view', 'treasury.bank_conciliation.view']))
                                <div>
                                    <button type="button" id="bankControlMenuToggle" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                                        <span>Control de banco</span>
                                        <svg id="bankControlMenuIcon" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform duration-200 text-slate-400 shrink-0">
                                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <div id="bankControlMenuItems" class="hidden mt-1 space-y-1 pl-4">
                                        @if(auth()->user()->hasPermission('treasury.bank_control.view'))
                                        <a href="{{ route('financial.bank-control') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('financial.bank-control') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Control de banco</a>
                                        @endif
                                        @if(auth()->user()->hasPermission('treasury.bank_conciliation.view'))
                                        <a href="{{ route('financial.bank-conciliation') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('financial.bank-conciliation') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Conciliación bancaria</a>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                @if(auth()->user()->hasPermission('treasury.transfers.view'))
                                <a href="{{ route('transfers.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('transfers.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Traspasos</a>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if(auth()->user()->hasPermission('objectives.main.view'))
                        <a href="{{ route('objectives.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('objectives.*') && !request()->routeIs('objectives-settings.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Objetivos mensuales</a>
                        @endif
                        @if(auth()->user()->hasPermission('declared_sales.main.view'))
                        <a href="{{ route('declared-sales.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('declared-sales.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Ventas declaradas</a>
                        @endif
                    </div>
                </div>
                @endif

                @if(auth()->user()->hasPermission('invoices.main.view'))
                <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('invoices.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Facturas
                </a>
                @endif

                @if(auth()->user()->hasPermission('orders.main.view'))
                <a href="{{ route('orders.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('orders.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M9 2v4M15 2v4M9 18h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Pedidos
                </a>
                @endif

                @if(($clientsModuleEnabled ?? false) && (auth()->user()->hasPermission('clients.orders.view') || auth()->user()->hasPermission('clients.repairs.view')))
                {{-- Clientes (desplegable) --}}
                <div>
                    <button type="button" id="clientsMenuToggle" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Clientes</span>
                        </div>
                        <svg id="clientsMenuIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform duration-200 text-slate-400">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div id="clientsMenuItems" class="hidden mt-1 space-y-1 pl-9">
                        @if(auth()->user()->hasPermission('clients.orders.view'))
                        <a href="{{ route('clients.orders.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('clients.orders.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Pedidos clientes</a>
                        @endif
                        @if(auth()->user()->hasPermission('clients.repairs.view'))
                        <a href="{{ route('clients.repairs.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('clients.repairs.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Reparaciones</a>
                        @endif
                    </div>
                </div>
                @endif

                @php
                    $invCompany = \App\Models\Company::withoutGlobalScopes()->find(session('company_id'));
                    $invRingsEnabled = $invCompany?->rings_inventory_enabled ?? false;
                    $invShowRings = auth()->user()->hasPermission('inventory.rings.view') && $invRingsEnabled;
                    $invShowProducts = auth()->user()->hasPermission('inventory.products.view');
                    $invShowSalesProducts = auth()->user()->hasPermission('sales.products.view');
                    $invShowMenu = $invShowRings || $invShowProducts || $invShowSalesProducts;
                @endphp
                @if($invShowMenu)
                {{-- Inventario (desplegable) --}}
                <div>
                    <button type="button" id="inventoryMenuToggle" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 7l-8 4-8-4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Inventarios</span>
                        </div>
                        <svg id="inventoryMenuIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform duration-200 text-slate-400">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div id="inventoryMenuItems" class="hidden mt-1 space-y-1 pl-9">
                        @if($invShowProducts)
                        <a href="{{ route('inventory.categories.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('inventory.categories.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Inventarios por categoría</a>
                        @endif
                        @if($invShowSalesProducts)
                        <a href="{{ route('inventory.sales.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('inventory.sales.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Ventas de productos</a>
                        @endif
                        @if($invShowRings)
                        <a href="{{ route('ring-inventories.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('ring-inventories.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Inventario de anillos</a>
                        @endif
                    </div>
                </div>
                @endif

                @if(auth()->user()->hasAnyPermission(['hr.employees.view_own', 'hr.employees.view_store', 'hr.overtime.view']))
                {{-- RR.HH. (desplegable) --}}
                <div>
                    <button type="button" id="hrMenuToggle" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>RR.HH.</span>
                        </div>
                        <svg id="hrMenuIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform duration-200 text-slate-400">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div id="hrMenuItems" class="hidden mt-1 space-y-1 pl-9">
                        @if(auth()->user()->hasAnyPermission(['hr.employees.view_own', 'hr.employees.view_store']))
                        <a href="{{ route('employees.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('employees.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Empleados</a>
                        @endif
                        @if(auth()->user()->hasPermission('hr.overtime.view'))
                        <a href="{{ route('overtime.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('overtime.*') && !request()->routeIs('overtime-settings.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Horas extras</a>
                        @endif
                        @if(auth()->user()->hasPermission('hr.vacations.view'))
                        <a href="{{ route('vacations.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('vacations.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Vacaciones</a>
                        @endif
                    </div>
                </div>
                @endif

                @if(auth()->user()->hasPermission('trash.main.view'))
                <a href="{{ route('trash.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('trash.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Papelera
                </a>
                @endif

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasAnyPermission(['settings.cash_reduction.view', 'settings.objectives.view', 'settings.overtime.view', 'settings.products.view', 'settings.daily_close.view']))
                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" id="settingsMenuToggle" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1Z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>Ajustes</span>
                        </div>
                        <svg id="settingsMenuIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform duration-200">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <div id="settingsMenuItems" class="hidden mt-1 space-y-1 pl-9">
                        @if(auth()->user()->hasPermission('settings.cash_reduction.view'))
                        <a href="{{ route('store-cash-reductions.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('store-cash-reductions.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 10h12M4 14h9M19 6a7.7 7.7 0 0 0-5.2-2A7.9 7.9 0 0 0 6 12c0 4.4 3.5 8 7.8 8 2 0 3.8-.8 5.2-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Reducción de efectivo por tienda
                        </a>
                        @endif
                        @if(auth()->user()->hasPermission('settings.objectives.view'))
                        <a href="{{ route('objectives-settings.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('objectives-settings.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">
                            Objetivos de ventas
                        </a>
                        @endif
                        @if(auth()->user()->hasPermission('settings.overtime.view'))
                        <a href="{{ route('overtime-settings.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('overtime-settings.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">
                            Ajustes de horas extras
                        </a>
                        @endif
                        @if(auth()->user()->hasPermission('settings.products.view'))
                        <a href="{{ route('product-settings.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('product-settings.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }}">
                            Productos
                        </a>
                        @endif
                        @if(auth()->user()->hasPermission('settings.daily_close.view'))
                        <a href="{{ route('daily-close-settings.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('daily-close-settings.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">
                            Cierre de caja
                        </a>
                        @endif
                        @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('module-settings.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('module-settings.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">
                            Módulos
                        </a>
                        @endif
                    </div>
                </div>
                @endif
                
                @if(auth()->user()->hasAnyPermission(['admin.company.view', 'admin.users.view', 'admin.roles.view']))
                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" id="adminMenuToggle" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1Z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>Administración</span>
                        </div>
                        <svg id="adminMenuIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform duration-200">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    
                    <div id="adminMenuItems" class="hidden mt-1 space-y-1 pl-9">
                        @if(auth()->user()->hasPermission('admin.company.view'))
                        <a href="{{ route('company.show') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('company.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Empresa</a>
                        @endif
                        @if(auth()->user()->hasPermission('admin.suppliers.view'))
                        <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('suppliers.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Proveedores</a>
                        @endif
                        @if(auth()->user()->hasPermission('admin.users.view'))
                        <a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Usuarios</a>
                        @endif
                        @if(auth()->user()->hasPermission('admin.roles.view'))
                        <a href="{{ route('roles.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('roles.*') ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' }}">Roles</a>
                        @endif
                    </div>
                </div>
                @endif
            </nav>
            
            {{-- Theme Switcher (Light / Dark / System) --}}
            <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                <p class="px-3 mb-2 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Tema</p>
                <div id="themeSwitcher" class="flex gap-1 p-1 rounded-xl bg-slate-100 dark:bg-slate-700/50 ring-1 ring-slate-200/80 dark:ring-slate-600/80" role="group" aria-label="Seleccionar tema">
                    <button type="button" data-theme="light" class="theme-btn flex-1 flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-medium transition-all duration-300 hover:bg-white dark:hover:bg-slate-600/50" title="Modo claro">
                        <svg class="theme-icon w-5 h-5 text-amber-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle class="theme-sun-rays" cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/>
                            <path class="theme-sun-rays" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span class="hidden sm:inline">Claro</span>
                    </button>
                    <button type="button" data-theme="dark" class="theme-btn flex-1 flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-medium transition-all duration-300 hover:bg-white dark:hover:bg-slate-600/50" title="Modo oscuro">
                        <svg class="theme-icon theme-moon-glow w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="hidden sm:inline">Oscuro</span>
                    </button>
                    <button type="button" data-theme="system" class="theme-btn flex-1 flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-medium transition-all duration-300 hover:bg-white dark:hover:bg-slate-600/50" title="Seguir sistema">
                        <svg class="theme-icon theme-system-monitor w-5 h-5 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 21h8M12 17v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                        <span class="hidden sm:inline">Sistema</span>
                    </button>
                </div>
            </div>
            
            <div class="mt-4 pt-6 border-t border-slate-200 dark:border-slate-700">
                <div class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
                    <div class="font-semibold">{{ auth()->user()->name }}</div>
                    <div>{{ auth()->user()->role->name }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full text-left flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 p-6">
            @if(session('success'))
                <div class="mb-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 p-4 text-sm text-emerald-800 dark:text-emerald-200 ring-1 ring-emerald-100 dark:ring-emerald-800/50">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-4 rounded-xl bg-rose-50 dark:bg-rose-900/30 p-4 text-sm text-rose-800 dark:text-rose-200 ring-1 ring-rose-100 dark:ring-rose-800/50">
                    {{ session('error') }}
                </div>
            @endif
            
            @yield('content')
        </main>
    </div>
    
    <!-- Modal de previsualización de factura -->
    <div id="invoicePreviewModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" id="invoiceModalBackdrop"></div>
            
            <!-- Modal panel -->
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-800 shadow-xl transition-all w-full max-w-6xl max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100" id="invoiceModalTitle">Previsualizar Factura</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400" id="invoiceModalSubtitle"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a id="invoiceModalDownload" href="#" class="hidden rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block mr-1">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Descargar
                        </a>
                        <button type="button" id="invoiceModalClose" class="rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 p-2 text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-600 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="flex-1 overflow-auto p-6">
                    <div id="invoiceModalContent" class="flex items-center justify-center min-h-[400px]">
                        <div class="text-center">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
                            <p class="mt-2 text-sm text-slate-500">Cargando factura...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @auth
    <script>
        window.LaravelAuth = {
            permissions: @json(auth()->user()->role ? (auth()->user()->role->permissions ?? []) : []),
            user: { name: @json(auth()->user()->name ?? ''), username: @json(auth()->user()->username ?? '') }
        };
    </script>
    @endauth

    @stack('scripts')
    
    <script>
        // Theme Switcher (Light / Dark / System)
        (function initThemeSwitcher() {
            const STORAGE_KEY = 'theme';
            const html = document.documentElement;
            
            function getStoredTheme() { return localStorage.getItem(STORAGE_KEY) || 'system'; }
            function prefersDark() { return window.matchMedia('(prefers-color-scheme: dark)').matches; }
            function applyTheme(theme) {
                const isDark = theme === 'dark' || (theme === 'system' && prefersDark());
                html.classList.toggle('dark', isDark);
            }
            
            function setTheme(theme) {
                localStorage.setItem(STORAGE_KEY, theme);
                applyTheme(theme);
                updateActiveButton(theme);
            }
            
            function updateActiveButton(theme) {
                document.querySelectorAll('.theme-btn').forEach(btn => {
                    const isActive = btn.dataset.theme === theme;
                    btn.classList.toggle('active', isActive);
                    const icon = btn.querySelector('.theme-icon');
                    if (icon) {
                        icon.classList.toggle('theme-icon-active', isActive);
                        icon.classList.toggle('theme-icon-inactive', !isActive);
                    }
                });
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('themeSwitcher');
                if (!container) return;
                
                const stored = getStoredTheme();
                applyTheme(stored);
                updateActiveButton(stored);
                
                container.querySelectorAll('.theme-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const theme = btn.dataset.theme;
                        setTheme(theme);
                        // Pequeño feedback táctil
                        btn.style.transform = 'scale(0.95)';
                        setTimeout(() => { btn.style.transform = ''; }, 150);
                    });
                });
                
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                    if (getStoredTheme() === 'system') {
                        applyTheme('system');
                        updateActiveButton('system');
                    }
                });
            });
        })();
        
        // Toggle del menú de administración
        document.addEventListener('DOMContentLoaded', function() {
            const adminToggle = document.getElementById('adminMenuToggle');
            const adminMenu = document.getElementById('adminMenuItems');
            const adminIcon = document.getElementById('adminMenuIcon');
            
            if (adminToggle && adminMenu) {
                const isAdminPage = window.location.pathname.includes('/users') || window.location.pathname.includes('/roles') || window.location.pathname.includes('/company');
                if (isAdminPage) {
                    adminMenu.classList.remove('hidden');
                    if (adminIcon) adminIcon.style.transform = 'rotate(180deg)';
                }
                
                adminToggle.addEventListener('click', function() {
                    const isHidden = adminMenu.classList.contains('hidden');
                    if (isHidden) {
                        adminMenu.classList.remove('hidden');
                        if (adminIcon) adminIcon.style.transform = 'rotate(180deg)';
                    } else {
                        adminMenu.classList.add('hidden');
                        if (adminIcon) adminIcon.style.transform = 'rotate(0deg)';
                    }
                });
            }

            // Toggle del menú Finanzas
            const financeToggle = document.getElementById('financeMenuToggle');
            const financeMenu = document.getElementById('financeMenuItems');
            const financeIcon = document.getElementById('financeMenuIcon');
            if (financeToggle && financeMenu) {
                const path = window.location.pathname;
                const isFinancePage = path.includes('/financial') || path.includes('/cash-wallets') || path.includes('/transfers') || path.includes('/objectives') || path.includes('/declared-sales');
                if (isFinancePage) {
                    financeMenu.classList.remove('hidden');
                    if (financeIcon) financeIcon.style.transform = 'rotate(180deg)';
                }
                financeToggle.addEventListener('click', function() {
                    const isHidden = financeMenu.classList.contains('hidden');
                    if (isHidden) { financeMenu.classList.remove('hidden'); if (financeIcon) financeIcon.style.transform = 'rotate(180deg)'; }
                    else { financeMenu.classList.add('hidden'); if (financeIcon) financeIcon.style.transform = 'rotate(0deg)'; }
                });
            }

            // Toggle Control de flujos (dentro de Finanzas)
            const flowToggle = document.getElementById('flowControlMenuToggle');
            const flowMenu = document.getElementById('flowControlMenuItems');
            const flowIcon = document.getElementById('flowControlMenuIcon');
            if (flowToggle && flowMenu) {
                const path = window.location.pathname;
                const isFlowPage = path.includes('/financial/cash-control') || path.includes('/financial/bank') || path.includes('/cash-wallets') || path.includes('/transfers');
                if (isFlowPage) { flowMenu.classList.remove('hidden'); if (flowIcon) flowIcon.style.transform = 'rotate(180deg)'; }
                flowToggle.addEventListener('click', function() {
                    const isHidden = flowMenu.classList.contains('hidden');
                    if (isHidden) { flowMenu.classList.remove('hidden'); if (flowIcon) flowIcon.style.transform = 'rotate(180deg)'; }
                    else { flowMenu.classList.add('hidden'); if (flowIcon) flowIcon.style.transform = 'rotate(0deg)'; }
                });
            }

            // Toggle Control de efectivo
            const cashCtrlToggle = document.getElementById('cashControlMenuToggle');
            const cashCtrlMenu = document.getElementById('cashControlMenuItems');
            const cashCtrlIcon = document.getElementById('cashControlMenuIcon');
            if (cashCtrlToggle && cashCtrlMenu) {
                const path = window.location.pathname;
                if (path.includes('/financial/cash-control') || path.includes('/cash-wallets')) {
                    cashCtrlMenu.classList.remove('hidden');
                    if (cashCtrlIcon) cashCtrlIcon.style.transform = 'rotate(180deg)';
                }
                cashCtrlToggle.addEventListener('click', function() {
                    const isHidden = cashCtrlMenu.classList.contains('hidden');
                    if (isHidden) { cashCtrlMenu.classList.remove('hidden'); if (cashCtrlIcon) cashCtrlIcon.style.transform = 'rotate(180deg)'; }
                    else { cashCtrlMenu.classList.add('hidden'); if (cashCtrlIcon) cashCtrlIcon.style.transform = 'rotate(0deg)'; }
                });
            }

            // Toggle Control de banco
            const bankCtrlToggle = document.getElementById('bankControlMenuToggle');
            const bankCtrlMenu = document.getElementById('bankControlMenuItems');
            const bankCtrlIcon = document.getElementById('bankControlMenuIcon');
            if (bankCtrlToggle && bankCtrlMenu) {
                const path = window.location.pathname;
                if (path.includes('/financial/bank-control') || path.includes('/financial/conciliation')) {
                    bankCtrlMenu.classList.remove('hidden');
                    if (bankCtrlIcon) bankCtrlIcon.style.transform = 'rotate(180deg)';
                }
                bankCtrlToggle.addEventListener('click', function() {
                    const isHidden = bankCtrlMenu.classList.contains('hidden');
                    if (isHidden) { bankCtrlMenu.classList.remove('hidden'); if (bankCtrlIcon) bankCtrlIcon.style.transform = 'rotate(180deg)'; }
                    else { bankCtrlMenu.classList.add('hidden'); if (bankCtrlIcon) bankCtrlIcon.style.transform = 'rotate(0deg)'; }
                });
            }

            // Toggle RR.HH.
            const hrToggle = document.getElementById('hrMenuToggle');
            const hrMenu = document.getElementById('hrMenuItems');
            const hrIcon = document.getElementById('hrMenuIcon');
            if (hrToggle && hrMenu && hrIcon) {
                const path = window.location.pathname;
                const isHrPage = path.includes('/employees') || (path.includes('/overtime') && !path.includes('/overtime-settings'));
                if (isHrPage) { hrMenu.classList.remove('hidden'); hrIcon.style.transform = 'rotate(180deg)'; }
                hrToggle.addEventListener('click', function() {
                    const isHidden = hrMenu.classList.contains('hidden');
                    if (isHidden) { hrMenu.classList.remove('hidden'); hrIcon.style.transform = 'rotate(180deg)'; }
                    else { hrMenu.classList.add('hidden'); hrIcon.style.transform = 'rotate(0deg)'; }
                });
            }
            
            // Toggle del menú de ajustes
            const settingsToggle = document.getElementById('settingsMenuToggle');
            const settingsMenu = document.getElementById('settingsMenuItems');
            const settingsIcon = document.getElementById('settingsMenuIcon');
            
            if (settingsToggle && settingsMenu) {
                // Verificar si estamos en una página de ajustes para abrir el menú
                const isSettingsPage = window.location.pathname.includes('/settings/cash-reductions') || window.location.pathname.includes('/settings/objectives') || window.location.pathname.includes('/settings/overtime') || window.location.pathname.includes('/settings/daily-close') || window.location.pathname.includes('/settings/modules');
                if (isSettingsPage) {
                    settingsMenu.classList.remove('hidden');
                    settingsIcon.style.transform = 'rotate(180deg)';
                }
                
                settingsToggle.addEventListener('click', function() {
                    const isHidden = settingsMenu.classList.contains('hidden');
                    
                    if (isHidden) {
                        settingsMenu.classList.remove('hidden');
                        settingsIcon.style.transform = 'rotate(180deg)';
                    } else {
                        settingsMenu.classList.add('hidden');
                        settingsIcon.style.transform = 'rotate(0deg)';
                    }
                });
            }

            // Toggle del menú Inventario
            const inventoryToggle = document.getElementById('inventoryMenuToggle');
            const inventoryMenu = document.getElementById('inventoryMenuItems');
            const inventoryIcon = document.getElementById('inventoryMenuIcon');
            if (inventoryToggle && inventoryMenu && inventoryIcon) {
                const isInventoryPage = window.location.pathname.includes('/inventory/');
                if (isInventoryPage) {
                    inventoryMenu.classList.remove('hidden');
                    inventoryIcon.style.transform = 'rotate(180deg)';
                }
                inventoryToggle.addEventListener('click', function() {
                    const isHidden = inventoryMenu.classList.contains('hidden');
                    if (isHidden) {
                        inventoryMenu.classList.remove('hidden');
                        inventoryIcon.style.transform = 'rotate(180deg)';
                    } else {
                        inventoryMenu.classList.add('hidden');
                        inventoryIcon.style.transform = 'rotate(0deg)';
                    }
                });
            }

            // Toggle del menú Clientes
            const clientsToggle = document.getElementById('clientsMenuToggle');
            const clientsMenu = document.getElementById('clientsMenuItems');
            const clientsIcon = document.getElementById('clientsMenuIcon');
            if (clientsToggle && clientsMenu && clientsIcon) {
                const isClientsPage = window.location.pathname.startsWith('/clients');
                if (isClientsPage) {
                    clientsMenu.classList.remove('hidden');
                    clientsIcon.style.transform = 'rotate(180deg)';
                }
                clientsToggle.addEventListener('click', function() {
                    const isHidden = clientsMenu.classList.contains('hidden');
                    if (isHidden) {
                        clientsMenu.classList.remove('hidden');
                        clientsIcon.style.transform = 'rotate(180deg)';
                    } else {
                        clientsMenu.classList.add('hidden');
                        clientsIcon.style.transform = 'rotate(0deg)';
                    }
                });
            }
            
            // Modal de previsualización de factura
            const invoiceModal = document.getElementById('invoicePreviewModal');
            const invoiceModalBackdrop = document.getElementById('invoiceModalBackdrop');
            const invoiceModalClose = document.getElementById('invoiceModalClose');
            const invoiceModalContent = document.getElementById('invoiceModalContent');
            const invoiceModalTitle = document.getElementById('invoiceModalTitle');
            const invoiceModalSubtitle = document.getElementById('invoiceModalSubtitle');
            const invoiceModalDownload = document.getElementById('invoiceModalDownload');
            
            let currentInvoiceId = null;
            let currentInvoiceDownloadUrl = null;
            
            // Función para abrir el modal
            window.openInvoicePreview = function(invoiceId, invoiceTitle, invoiceSubtitle, serveUrl, downloadUrl, mimeType) {
                currentInvoiceId = invoiceId;
                currentInvoiceDownloadUrl = downloadUrl;
                
                // Actualizar título y subtítulo
                invoiceModalTitle.textContent = invoiceTitle || 'Previsualizar Factura';
                invoiceModalSubtitle.textContent = invoiceSubtitle || '';
                
                // Mostrar botón de descarga si hay URL
                if (downloadUrl) {
                    invoiceModalDownload.href = downloadUrl;
                    invoiceModalDownload.classList.remove('hidden');
                } else {
                    invoiceModalDownload.classList.add('hidden');
                }
                
                // Determinar tipo de archivo basado en el MIME type
                const isImage = mimeType && mimeType.startsWith('image/');
                const isPdf = mimeType === 'application/pdf';
                
                // Crear contenido según el tipo
                let content = '';
                if (isImage) {
                    content = `<img src="${serveUrl}" alt="Factura" class="max-w-full max-h-[70vh] mx-auto object-contain rounded-lg shadow-lg" onerror="this.parentElement.innerHTML='<div class=\\'text-center p-8\\'><svg class=\\'mx-auto h-12 w-12 text-rose-400\\' fill=\\'none\\' viewBox=\\'0 0 24 24\\' stroke=\\'currentColor\\'><path stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\' stroke-width=\\'2\\' d=\\'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z\\' /></svg><h3 class=\\'mt-2 text-sm font-semibold text-slate-900\\'>Error al cargar la imagen</h3></div>'">`;
                } else if (isPdf) {
                    content = `<iframe src="${serveUrl}" class="w-full h-[70vh] border-0 rounded-lg shadow-lg" frameborder="0" title="Vista previa de factura PDF" onerror="this.parentElement.innerHTML='<div class=\\'text-center p-8\\'><svg class=\\'mx-auto h-12 w-12 text-rose-400\\' fill=\\'none\\' viewBox=\\'0 0 24 24\\' stroke=\\'currentColor\\'><path stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\' stroke-width=\\'2\\' d=\\'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z\\' /></svg><h3 class=\\'mt-2 text-sm font-semibold text-slate-900\\'>Error al cargar el PDF</h3></div>'"></iframe>`;
                } else {
                    content = `
                        <div class="text-center p-8">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-slate-900">Tipo de archivo no soportado para previsualización</h3>
                            <p class="mt-1 text-sm text-slate-500">Tipo MIME: ${mimeType || 'desconocido'}</p>
                        </div>
                    `;
                }
                
                invoiceModalContent.innerHTML = `<div class="flex items-center justify-center w-full">${content}</div>`;
                
                // Mostrar modal
                invoiceModal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            };
            
            // Función para cerrar el modal
            function closeInvoiceModal() {
                invoiceModal.classList.add('hidden');
                document.body.style.overflow = '';
                invoiceModalContent.innerHTML = '';
                currentInvoiceId = null;
                currentInvoiceDownloadUrl = null;
            }
            
            // Event listeners para cerrar
            if (invoiceModalClose) {
                invoiceModalClose.addEventListener('click', closeInvoiceModal);
            }
            
            if (invoiceModalBackdrop) {
                invoiceModalBackdrop.addEventListener('click', closeInvoiceModal);
            }
            
            // Cerrar con ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !invoiceModal.classList.contains('hidden')) {
                    closeInvoiceModal();
                }
            });
            
            // Interceptar clics en enlaces de previsualización
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[data-invoice-preview]');
                if (link) {
                    e.preventDefault();
                    const invoiceId = link.getAttribute('data-invoice-id');
                    const invoiceTitle = link.getAttribute('data-invoice-title') || 'Previsualizar Factura';
                    const invoiceSubtitle = link.getAttribute('data-invoice-subtitle') || '';
                    const serveUrl = link.getAttribute('data-invoice-serve') || link.getAttribute('href').replace('/preview', '/serve');
                    const downloadUrl = link.getAttribute('data-invoice-download') || link.getAttribute('href').replace('/preview', '/download');
                    const mimeType = link.getAttribute('data-invoice-mime') || '';
                    
                    window.openInvoicePreview(invoiceId, invoiceTitle, invoiceSubtitle, serveUrl, downloadUrl, mimeType);
                }
            });
        });
    </script>
</body>
</html>
