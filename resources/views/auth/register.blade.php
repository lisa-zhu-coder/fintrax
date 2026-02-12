<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Fintrax Smart Backoffice</title>

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
                <h1 class="text-2xl font-bold text-[#224D5F]">Crear cuenta</h1>
                <p class="text-sm font-medium text-[#25AD9F]">Fintrax Smart Backoffice</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                @if($errors->any())
                    <div class="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 ring-1 ring-rose-100">
                        <ul class="list-none space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nombre de usuario</span>
                    <input
                        type="text"
                        name="username"
                        required
                        autocomplete="username"
                        value="{{ old('username') }}"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="Usuario"
                    />
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nombre completo</span>
                    <input
                        type="text"
                        name="name"
                        required
                        autocomplete="name"
                        value="{{ old('name') }}"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="Nombre y apellidos"
                    />
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Correo electrónico <span class="text-slate-400">(opcional)</span></span>
                    <input
                        type="email"
                        name="email"
                        autocomplete="email"
                        value="{{ old('email') }}"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="correo@ejemplo.com"
                    />
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Contraseña</span>
                    <input
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="Mínimo 6 caracteres"
                    />
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Confirmar contraseña</span>
                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="Repite la contraseña"
                    />
                </label>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-200"
                >
                    Registrarse
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-600">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Iniciar sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
