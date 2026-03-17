@extends('layouts.app')

@section('title', 'Empleados')

@section('content')
<div class="space-y-6">
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-lg font-semibold">Empleados</h1>
                <p class="text-sm text-slate-500">Gestiona la información de todos los empleados</p>
                <div class="mt-2 flex gap-1">
                    <a href="{{ route('employees.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ !($showArchived ?? false) ? 'bg-brand-100 text-brand-800' : 'text-slate-600 hover:bg-slate-100' }}">Activos</a>
                    <a href="{{ route('employees.index', ['archived' => 1]) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $showArchived ?? false ? 'bg-slate-200 text-slate-800' : 'text-slate-600 hover:bg-slate-100' }}">Archivados</a>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->hasPermission('hr.employees.configure'))
                <form method="POST" action="{{ route('employees.payrolls.upload') }}" enctype="multipart/form-data" class="inline" id="formPayrollUploadAuto" data-pending-send-url="{{ route('payroll.pending-send') }}">
                    @csrf
                    <input type="file" name="payroll" id="payrollFileInputAuto" accept=".pdf" class="hidden"/>
                    <button type="button" id="btnPayrollUploadAuto" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50" title="Puedes subir un PDF con una o varias nóminas; cada página se asignará al empleado por nombre, DNI o número de la seguridad social.">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Subir nómina
                    </button>
                </form>
                <div id="payrollUploadOverlay" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/60">
                    <div id="payrollUploadOverlayContent" class="rounded-2xl bg-white p-8 shadow-xl text-center max-w-sm">
                        <div class="inline-block h-10 w-10 animate-spin rounded-full border-2 border-brand-600 border-t-transparent mb-4" aria-hidden="true"></div>
                        <p class="text-sm font-semibold text-slate-900">Procesando PDF…</p>
                        <p class="text-xs text-slate-500 mt-1">Puede tardar un momento con varios documentos.</p>
                        <p id="payrollUploadError" class="mt-3 text-sm text-rose-600 hidden"></p>
                        <button type="button" id="payrollUploadCloseBtn" class="mt-4 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 hidden">Cerrar</button>
                    </div>
                </div>
                @endif
                @if(auth()->user()->hasPermission('hr.employees.create'))
                <a href="{{ route('employees.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir empleado
                </a>
                @endif
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Nombre</th>
                        <th class="px-3 py-2">DNI</th>
                        <th class="px-3 py-2">Puesto</th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2">Usuario</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ $employee->full_name }}</td>
                            <td class="px-3 py-2">{{ $employee->dni ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $employee->position }}</td>
                            <td class="px-3 py-2">
                                @if($employee->stores->count() > 0)
                                    @if($employee->stores->count() == $totalStores && $totalStores > 0)
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800">
                                            Todas las tiendas
                                        </span>
                                    @else
                                        {{ $employee->stores->pluck('name')->join(', ') }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if($employee->user)
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800">
                                        {{ $employee->user->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Sin usuario</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('employees.show', $employee) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Ver
                                    </a>
                                    @if(auth()->user()->hasPermission('hr.employees.edit'))
                                    <a href="{{ route('employees.edit', $employee) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                        Editar
                                    </a>
                                    @endif
                                    @if(($showArchived ?? false))
                                        @if(auth()->user()->hasPermission('hr.employees.edit') || auth()->user()->hasPermission('hr.employees.delete'))
                                        <form method="POST" action="{{ route('employees.restore', $employee->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                                                Restaurar
                                            </button>
                                        </form>
                                        @endif
                                    @else
                                        @if(auth()->user()->hasPermission('hr.employees.delete'))
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" data-confirm-title="Archivar empleado" data-confirm-message="¿Archivar a este empleado? No se borrarán sus datos y podrás restaurarlo desde la lista de archivados." data-confirm-ok="Archivar">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-50">
                                                Archivar
                                            </button>
                                        </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-slate-500">{{ $showArchived ?? false ? 'No hay empleados archivados' : 'No hay empleados registrados' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('payrollFileInputAuto');
    var form = document.getElementById('formPayrollUploadAuto');
    var overlay = document.getElementById('payrollUploadOverlay');
    var overlayContent = document.getElementById('payrollUploadOverlayContent');
    var errEl = document.getElementById('payrollUploadError');
    var closeBtn = document.getElementById('payrollUploadCloseBtn');
    var btn = document.getElementById('btnPayrollUploadAuto');

    function showOverlay(loading) {
        overlay.classList.remove('hidden');
        if (overlayContent) {
            overlayContent.querySelector('.animate-spin').classList.toggle('hidden', !loading);
            overlayContent.querySelector('.text-slate-900').textContent = loading ? 'Procesando PDF…' : '';
            overlayContent.querySelector('.text-slate-500').textContent = loading ? 'Puede tardar un momento con varios documentos.' : '';
        }
        if (errEl) { errEl.classList.add('hidden'); errEl.textContent = ''; }
        if (closeBtn) closeBtn.classList.add('hidden');
    }
    function showError(msg) {
        if (overlayContent) {
            overlayContent.querySelector('.animate-spin').classList.add('hidden');
            overlayContent.querySelector('.text-slate-900').textContent = 'Error';
            overlayContent.querySelector('.text-slate-500').textContent = '';
        }
        if (errEl) { errEl.textContent = msg || 'Error al procesar.'; errEl.classList.remove('hidden'); }
        if (closeBtn) closeBtn.classList.remove('hidden');
    }
    function hideOverlay() {
        overlay.classList.add('hidden');
        if (input) input.value = '';
    }

    if (btn && input) btn.addEventListener('click', function() { input.click(); });
    if (input && form && overlay) {
        input.addEventListener('change', function() {
            if (!this.files || this.files.length === 0) return;
            showOverlay(true);
            var formData = new FormData(form);
            var url = form.getAttribute('action');
            var token = form.querySelector('input[name="_token"]');
            if (token) formData.append('_token', token.value);
            var controller = new AbortController();
            var timeoutId = setTimeout(function() { controller.abort(); }, 120000);
            var pendingSendUrl = form.getAttribute('data-pending-send-url') || '';
            fetch(url, {
                method: 'POST',
                body: formData,
                signal: controller.signal,
                redirect: 'manual',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function(r) {
                clearTimeout(timeoutId);
                var status = r.status;
                if (status >= 200 && status < 300) {
                    if (pendingSendUrl) {
                        window.location.href = pendingSendUrl;
                        return;
                    }
                    var ct = (r.headers.get('Content-Type') || '');
                    if (ct.indexOf('application/json') !== -1) {
                        return r.json().then(function(data) {
                            if (data.redirect) { window.location.href = data.redirect; return; }
                            if (pendingSendUrl) { window.location.href = pendingSendUrl; return; }
                            showError(data.message || 'No se pudo procesar.');
                        });
                    }
                    if (pendingSendUrl) window.location.href = pendingSendUrl;
                    return;
                }
                if (status >= 300 && status < 400) {
                    var loc = r.headers.get('Location');
                    window.location.href = loc || pendingSendUrl || url;
                    return;
                }
                return r.text().then(function(text) {
                    var msg = 'No se pudo procesar el PDF.';
                    try {
                        var data = JSON.parse(text);
                        msg = data.message || (data.errors && Object.values(data.errors).flat().join(' ')) || msg;
                    } catch (_) {}
                    showError(msg);
                });
            }).catch(function(e) {
                clearTimeout(timeoutId);
                if (e.name === 'AbortError') showError('Tiempo agotado. Prueba con menos páginas o inténtalo más tarde.');
                else showError('Error de conexión. Comprueba la red e inténtalo de nuevo.');
            });
        });
    }
    if (closeBtn) closeBtn.addEventListener('click', hideOverlay);
});
</script>
@endpush
@endsection
