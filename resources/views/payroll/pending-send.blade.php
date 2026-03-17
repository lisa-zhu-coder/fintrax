@extends('layouts.app')

@section('title', 'Envío de nóminas')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h1 class="text-lg font-semibold text-slate-900">Envío de nóminas</h1>
        <p class="text-sm text-slate-500">Todas las nóminas se guardarán en la ficha de cada empleado. Marca la casilla «Enviar» solo en las que quieras enviar por correo (si el empleado no tiene email, déjala desmarcada).</p>
    </header>

    <form method="POST" action="{{ route('payroll.send-bulk') }}" id="formSendBulk" class="space-y-6">
        @csrf
        @if(!empty($token ?? null))
        <input type="hidden" name="payroll_token" value="{{ $token }}">
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Configuración del envío</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Plantilla</span>
                    <select id="templateSelect" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        <option value="">— Sin plantilla —</option>
                        @foreach($templates as $t)
                        <option value="{{ $t->id }}" data-subject="{{ e($t->subject) }}" data-body="{{ e($t->body) }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </label>
                @if(auth()->user()->hasPermission('settings.payroll_templates.manage'))
                <label class="block flex items-end gap-2">
                    <span class="sr-only">Guardar como plantilla</span>
                    <input type="text" id="newTemplateName" placeholder="Nombre de la plantilla" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" maxlength="255"/>
                    <button type="button" id="btnSaveAsTemplate" class="shrink-0 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Guardar como plantilla</button>
                </label>
                @endif
                @php
                    $defaultSubject = 'Nómina {{mes}} - {{empresa}}';
                    $defaultBody = "Hola {{nombre}},\n\nAdjuntamos tu nómina correspondiente al mes de {{mes}}.\n\nUn saludo,\n{{empresa}}";
                @endphp
                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold text-slate-700">Asunto</span>
                    <input type="text" name="subject" id="subjectInput" value="{{ old('subject', $defaultSubject) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="{{ $defaultSubject }}">
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold text-slate-700">Texto del correo</span>
                    <textarea name="body" id="bodyInput" rows="4" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">{{ old('body', $defaultBody) }}</textarea>
                </label>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 overflow-x-auto">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Nóminas</h2>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs text-slate-500">
                        <th class="w-12 py-2">
                            <label class="flex cursor-pointer items-center gap-1">
                                <input type="checkbox" id="cbSelectAll" class="rounded border-slate-300 text-brand-600" title="Seleccionar todas" checked>
                                <span>Enviar por correo</span>
                            </label>
                        </th>
                        <th class="py-2">Archivo</th>
                        <th class="py-2">Empleado</th>
                        <th class="py-2">Email</th>
                        <th class="py-2">Estado</th>
                        <th class="w-24 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingRows as $row)
                    <tr class="border-b border-slate-100" data-pending-index="{{ $row->index }}">
                        <td class="py-3">
                            <input type="checkbox" name="ids[]" value="{{ $row->index }}" class="cb-send rounded border-slate-300 text-brand-600" checked>
                        </td>
                        <td class="py-3">
                            <input type="text" name="file_name_{{ $row->index }}" value="{{ $row->file_name }}" class="w-full min-w-[180px] max-w-md rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm font-medium" placeholder="Nombre del archivo">
                        </td>
                        <td class="py-3">
                            <select name="employee_id_{{ $row->index }}" class="payroll-employee-select w-full max-w-[200px] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm" data-pending-index="{{ $row->index }}">
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $row->employee && $row->employee->id == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="py-3">
                            <input type="text" name="email_{{ $row->index }}" value="{{ $row->email ?? '' }}" class="payroll-email-input w-full max-w-[220px] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm" placeholder="email@ejemplo.com">
                        </td>
                        <td class="py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700">Pendiente</span>
                        </td>
                        <td class="py-3">
                            <button type="button" class="btn-delete-payroll text-rose-600 hover:text-rose-700 text-xs font-medium" data-pending-index="{{ $row->index }}">Borrar</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('payroll.cancel-pending') }}" class="inline" onsubmit="return confirm('¿Cancelar? Se eliminarán las nóminas subidas y no se guardará nada.');">
                @csrf
                <button type="submit" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
            </form>
            <button type="submit" form="formSendBulk" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar y enviar</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var cbSelectAll = document.getElementById('cbSelectAll');
    var formSendBulk = document.getElementById('formSendBulk');
    if (cbSelectAll && formSendBulk) {
        cbSelectAll.addEventListener('change', function() {
            formSendBulk.querySelectorAll('.cb-send').forEach(function(cb) {
                cb.checked = cbSelectAll.checked;
            });
        });
        formSendBulk.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('cb-send')) {
                var all = formSendBulk.querySelectorAll('.cb-send');
                var checked = formSendBulk.querySelectorAll('.cb-send:checked');
                cbSelectAll.checked = all.length > 0 && checked.length === all.length;
            }
        });
    }
    var templateSelect = document.getElementById('templateSelect');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (opt && opt.value) {
                document.getElementById('subjectInput').value = opt.dataset.subject || '';
                document.getElementById('bodyInput').value = (opt.dataset.body || '').replace(/&lt;br\s*\/?&gt;/gi, '\n');
            }
        });
    }
    document.querySelectorAll('.payroll-employee-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var index = this.dataset.pendingIndex;
            var employeeId = this.value;
            fetch('{{ route("payroll.pending-assign") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ index: parseInt(index, 10), employee_id: parseInt(employeeId, 10) })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success && data.email !== undefined) {
                    var row = document.querySelector('tr[data-pending-index="' + index + '"]');
                    if (row) {
                        var emailInput = row.querySelector('.payroll-email-input');
                        if (emailInput) emailInput.value = data.email;
                        var fileInput = row.querySelector('input[name^="file_name_"]');
                        if (fileInput && data.file_name) fileInput.value = data.file_name;
                    }
                }
            });
        });
    });
    document.querySelectorAll('.btn-delete-payroll').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('¿Eliminar esta nómina de la lista?')) return;
            var index = this.dataset.pendingIndex;
            fetch('{{ route("payroll.pending-remove") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ index: parseInt(index, 10) })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    var row = document.querySelector('tr[data-pending-index="' + index + '"]');
                    if (row) row.remove();
                    if (data.redirect) { window.location.href = data.redirect; return; }
                }
            });
        });
    });
    // Sin validación: se guardan todas las nóminas en las fichas; solo se envían por correo las filas marcadas
    var btnSave = document.getElementById('btnSaveAsTemplate');
    if (btnSave) {
        btnSave.addEventListener('click', function() {
            var name = document.getElementById('newTemplateName') && document.getElementById('newTemplateName').value.trim();
            if (!name) { alert('Escribe un nombre para la plantilla.'); return; }
            var subject = document.getElementById('subjectInput').value;
            var body = document.getElementById('bodyInput').value;
            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('name', name);
            formData.append('subject', subject);
            formData.append('body', body);
            formData.append('type', 'payroll');
            fetch('{{ route("email-templates-settings.store") }}', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r) { return r.json().catch(function() { return {}; }); })
                .then(function(data) {
                    if (data.success && data.id) {
                        var sel = document.getElementById('templateSelect');
                        var opt = document.createElement('option');
                        opt.value = data.id;
                        opt.setAttribute('data-subject', data.subject || '');
                        opt.setAttribute('data-body', (data.body || '').replace(/<br\s*\/?>/gi, '\n'));
                        opt.textContent = data.name;
                        sel.appendChild(opt);
                        sel.value = data.id;
                        document.getElementById('subjectInput').value = data.subject || '';
                        document.getElementById('bodyInput').value = (data.body || '').replace(/<br\s*\/?>/gi, '\n');
                        document.getElementById('newTemplateName').value = '';
                        alert('Plantilla guardada.');
                    } else if (data.redirect) window.location.href = data.redirect;
                    else window.location.reload();
                });
        });
    }
});
</script>
@endpush
@endsection
