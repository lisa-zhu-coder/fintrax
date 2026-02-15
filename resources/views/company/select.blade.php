<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Empresa - Fintrax Smart Backoffice</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
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
    
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    <div class="flex flex-col items-center justify-center min-h-screen p-6">
        <!-- Logo y título -->
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-white shadow-soft ring-1 ring-slate-100">
                <svg width="50" height="50" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M 6 20 A 14 14 0 0 1 34 20" stroke="#224D5F" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                    <path d="M 34 20 A 14 14 0 0 1 6 20" stroke="#25AD9F" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                    <rect x="12" y="22" width="3" height="8" rx="1" fill="#224D5F"/>
                    <rect x="17" y="18" width="3" height="12" rx="1" fill="#2D5F6B"/>
                    <rect x="22" y="14" width="3" height="16" rx="1" fill="#25AD9F"/>
                    <rect x="27" y="10" width="3" height="20" rx="1" fill="#25AD9F"/>
                    <path d="M10 26 Q20 14 30 8" stroke="#25AD9F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <path d="M26 10 L30 8 L28 12 Z" fill="#25AD9F"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Fintrax Smart Backoffice</h1>
            <p class="text-slate-500 mt-1">Selecciona una empresa para continuar</p>
        </div>

        <!-- Mensajes flash -->
        @if(session('success'))
            <div class="mb-6 w-full max-w-2xl rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-100">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="mb-6 w-full max-w-2xl rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-100">
                {{ session('error') }}
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 w-full max-w-2xl rounded-xl bg-blue-50 p-4 text-sm text-blue-800 ring-1 ring-blue-100">
                {{ session('info') }}
            </div>
        @endif

        <!-- Lista de empresas -->
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold">Empresas disponibles</h2>
                @if(auth()->user()->isSuperAdmin())
                <button type="button" onclick="document.getElementById('createModal').classList.remove('hidden')" 
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir empresa
                </button>
                @endif
            </div>

            @if($companies->isEmpty())
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-slate-900">No hay empresas</h3>
                    <p class="mt-1 text-sm text-slate-500">Crea tu primera empresa para comenzar.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($companies as $company)
                        <div class="flex items-stretch gap-2 rounded-xl border border-slate-200 overflow-hidden">
                            <form method="POST" action="{{ route('company.switch') }}" class="flex-1 min-w-0">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $company->id }}">
                                <button type="submit" class="w-full text-left p-4 hover:bg-slate-50 hover:border-brand-200 transition-colors h-full">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="font-semibold text-slate-800">{{ $company->name }}</h3>
                                            @if($company->cif)
                                                <p class="text-sm text-slate-500">CIF: {{ $company->cif }}</p>
                                            @endif
                                            @if($company->email)
                                                <p class="text-sm text-slate-500">{{ $company->email }}</p>
                                            @endif
                                        </div>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-slate-400 shrink-0">
                                            <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                </button>
                            </form>
                            @if(auth()->user()->isSuperAdmin())
                            <form method="POST" action="{{ route('company.archive', $company) }}" class="flex items-center pr-2" onsubmit="return confirm('¿Archivar la empresa {{ addslashes($company->name) }}? Los datos se conservarán y podrás recuperarla después.');">
                                @csrf
                                <button type="submit" class="rounded-lg p-2 text-amber-600 hover:bg-amber-50" title="Archivar empresa">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if(isset($archivedCompanies) && $archivedCompanies->isNotEmpty())
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 mt-6">
            <details class="group">
                <summary class="flex cursor-pointer items-center justify-between text-lg font-semibold text-slate-600 hover:text-slate-900">
                    <span>Empresas archivadas ({{ $archivedCompanies->count() }})</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="transition-transform group-open:rotate-180">
                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </summary>
                <p class="mt-2 text-sm text-slate-500">Las empresas archivadas conservan todos sus datos. Puedes recuperarlas o eliminarlas definitivamente.</p>
                <div class="mt-4 space-y-3">
                    @foreach($archivedCompanies as $company)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                            <div>
                                <h3 class="font-semibold text-slate-700">{{ $company->name }}</h3>
                                @if($company->cif)
                                    <p class="text-sm text-slate-500">CIF: {{ $company->cif }}</p>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('company.restore', $company->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Recuperar</button>
                                </form>
                                <form method="POST" action="{{ route('company.force-delete', $company->id) }}" class="inline" onsubmit="return confirm('¿Eliminar DEFINITIVAMENTE la empresa {{ addslashes($company->name) }}? Se borrarán todos sus datos (usuarios, empleados, registros, etc.) y esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-rose-300 bg-white px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Eliminar definitivamente</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </details>
        </div>
        @endif

        <!-- Info usuario y logout -->
        <div class="mt-6 text-center">
            <p class="text-sm text-slate-500">
                Conectado como <strong>{{ auth()->user()->name }}</strong>@if(auth()->user()->isSuperAdmin()) (Super Admin)@endif
            </p>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 underline">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>

    <!-- Modal para crear empresa -->
    <div id="createModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
            
            <!-- Modal panel -->
            <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all w-full max-w-lg">
                <form method="POST" action="{{ route('company.store') }}">
                    @csrf
                    
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">Crear nueva empresa</h3>
                    </div>
                    
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre de la empresa *</label>
                            <input type="text" name="name" id="name" required
                                class="w-full rounded-xl border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        
                        <div>
                            <label for="cif" class="block text-sm font-medium text-slate-700 mb-1">CIF</label>
                            <input type="text" name="cif" id="cif"
                                class="w-full rounded-xl border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        
                        <div>
                            <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                            <input type="text" name="address" id="address"
                                class="w-full rounded-xl border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" id="email"
                                class="w-full rounded-xl border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                            <input type="text" name="phone" id="phone"
                                class="w-full rounded-xl border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                            Crear empresa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
