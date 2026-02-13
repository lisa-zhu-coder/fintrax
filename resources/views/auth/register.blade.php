<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Fintrax Smart Backoffice</title>

    <script>
        (function(){const s=localStorage.getItem('theme')||'system';const d=window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.classList.toggle('dark',s==='dark'||(s==='system'&&d));})();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: {
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
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors">
    <div class="fixed top-4 right-4 z-10 flex gap-1 rounded-xl bg-white/90 dark:bg-slate-800/90 p-1.5 shadow-lg ring-1 ring-slate-200/80 dark:ring-slate-700/80 backdrop-blur-sm" id="authThemeSwitcher" role="group" aria-label="Tema">
        <button type="button" data-theme="light" class="auth-theme-btn rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700" title="Claro">
            <svg class="w-5 h-5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        </button>
        <button type="button" data-theme="dark" class="auth-theme-btn rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700" title="Oscuro">
            <svg class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <button type="button" data-theme="system" class="auth-theme-btn rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700" title="Sistema">
            <svg class="w-5 h-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/><circle cx="12" cy="10" r="2.5"/></svg>
        </button>
    </div>
    <div class="fixed inset-0 flex items-center justify-center bg-slate-50 dark:bg-slate-900 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 p-8 shadow-soft ring-1 ring-slate-100 dark:ring-slate-700 transition-colors">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-white dark:bg-slate-700 shadow-soft ring-1 ring-slate-100 dark:ring-slate-600">
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
                <h1 class="text-2xl font-bold text-[#224D5F] dark:text-brand-300">Crear cuenta</h1>
                <p class="text-sm font-medium text-[#25AD9F] dark:text-brand-400">Fintrax Smart Backoffice</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                @if($errors->any())
                    <div class="rounded-xl bg-rose-50 dark:bg-rose-900/30 p-3 text-sm text-rose-800 dark:text-rose-200 ring-1 ring-rose-100 dark:ring-rose-800/50">
                        <ul class="list-none space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Nombre de usuario</span>
                    <input
                        type="text"
                        name="username"
                        required
                        autocomplete="username"
                        value="{{ old('username') }}"
                        class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none ring-brand-200 focus:ring-4"
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
                        class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none ring-brand-200 focus:ring-4"
                        placeholder="Nombre y apellidos"
                    />
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Correo electrónico <span class="text-slate-400 dark:text-slate-500">(opcional)</span></span>
                    <input
                        type="email"
                        name="email"
                        autocomplete="email"
                        value="{{ old('email') }}"
                        class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none ring-brand-200 focus:ring-4"
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
                        class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none ring-brand-200 focus:ring-4"
                        placeholder="Mínimo 6 caracteres"
                    />
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Confirmar contraseña</span>
                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none ring-brand-200 focus:ring-4"
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

            <p class="mt-4 text-center text-sm text-slate-600 dark:text-slate-400">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300">Iniciar sesión</a>
            </p>
        </div>
    </div>
    <style>.auth-theme-btn.active{background:rgb(226 232 240);}.dark .auth-theme-btn.active{background:rgb(71 85 105);}</style>
    <script>
        (function(){const k='theme',h=document.documentElement;function g(){return localStorage.getItem(k)||'system';}function d(){return matchMedia('(prefers-color-scheme: dark)').matches;}function a(t){h.classList.toggle('dark',t==='dark'||(t==='system'&&d()));}function u(t){document.querySelectorAll('.auth-theme-btn').forEach(b=>{b.classList.toggle('active',b.dataset.theme===t);});}document.getElementById('authThemeSwitcher')?.querySelectorAll('button').forEach(b=>{b.addEventListener('click',()=>{const t=b.dataset.theme;localStorage.setItem(k,t);a(t);u(t);});});a(g());u(g());matchMedia('(prefers-color-scheme: dark)').addEventListener('change',()=>{if(g()==='system'){a('system');u('system');}});})();
    </script>
</body>
</html>
