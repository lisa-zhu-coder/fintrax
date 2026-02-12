<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Fintrax Smart Backoffice</title>
    
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
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="fixed inset-0 flex items-center justify-center bg-slate-50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-soft ring-1 ring-slate-100">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-white shadow-soft ring-1 ring-slate-100">
                    <svg width="60" height="60" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
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
                <h1 class="text-2xl font-bold text-[#224D5F]">Fintrax</h1>
                <p class="text-sm font-medium text-[#25AD9F]">Smart Backoffice</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                
                @if($errors->any())
                    <div class="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 ring-1 ring-rose-100">
                        {{ $errors->first() }}
                    </div>
                @endif
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Usuario</span>
                    <input
                        type="text"
                        name="username"
                        required
                        autocomplete="username"
                        value="{{ old('username') }}"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="Nombre de usuario"
                    />
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Contraseña</span>
                    <input
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="Contraseña"
                    />
                </label>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-200"
                >
                    Iniciar sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>
