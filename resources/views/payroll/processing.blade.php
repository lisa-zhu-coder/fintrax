@extends('layouts.app')

@section('title', 'Procesando nóminas')

@section('content')
<div class="mx-auto max-w-md space-y-6">
    <div class="rounded-2xl bg-white p-8 shadow-soft ring-1 ring-slate-100 text-center">
        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
            <svg class="h-6 w-6 animate-spin text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <h1 class="text-lg font-semibold text-slate-900">Procesando tu PDF</h1>
        <p class="mt-2 text-sm text-slate-500">Puede tardar 1–2 minutos en PDFs con muchas páginas. No cierres esta pestaña.</p>
        <p id="statusText" class="mt-2 text-sm text-slate-600">Esperando cola…</p>
        <p id="errorText" class="mt-3 hidden text-sm text-rose-600"></p>
        <a id="backLink" href="{{ route('employees.index') }}" class="mt-4 hidden inline-block rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver a empleados</a>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var token = @json($token);
    var statusUrl = '{{ url("payroll/process-status") }}/' + encodeURIComponent(token);
    var statusText = document.getElementById('statusText');
    var errorText = document.getElementById('errorText');
    var backLink = document.getElementById('backLink');

    function poll() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'done') {
                    statusText.textContent = 'Listo. Redirigiendo…';
                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    window.location.href = '{{ route("payroll.pending-send") }}?token=' + encodeURIComponent(token);
                    return;
                }
                if (data.status === 'error') {
                    statusText.classList.add('hidden');
                    errorText.textContent = data.message || 'Error al procesar el PDF.';
                    errorText.classList.remove('hidden');
                    backLink.classList.remove('hidden');
                    return;
                }
                statusText.textContent = 'Procesando páginas…';
                setTimeout(poll, 2000);
            })
            .catch(function() {
                statusText.textContent = 'Comprobando de nuevo…';
                setTimeout(poll, 2000);
            });
    }

    setTimeout(poll, 1500);
});
</script>
@endpush
@endsection
