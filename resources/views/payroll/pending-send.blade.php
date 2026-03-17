@extends('layouts.app')

@section('title', 'Envío de nóminas')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h1 class="text-lg font-semibold text-slate-900">Envío de nóminas</h1>
        <p class="text-sm text-slate-500">Marca las nóminas a enviar, revisa empleado y email, y pulsa Guardar y enviar.</p>
    </header>

    <form method="POST" action="{{ route('payroll.send-bulk') }}" id="formSendBulk" class="space-y-6">
        @csrf

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
                        <th class="w-12 py-2">Enviar</th>
                        <th class="py-2">Archivo</th>
                        <th class="py-2">Empleado</th>
                        <th class="py-2">Email</th>
                        <th class="py-2">Estado</th>
                        <th class="w-24 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payrolls as $p)
                    <tr class="border-b border-slate-100" data-payroll-id="{{ $p->id }}">
                        <td class="py-3">
                            @if(!$p->sent_at)
                            <input type="checkbox" name="ids[]" value="{{ $p->id }}" class="cb-send rounded border-slate-300 text-brand-600">
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 font-medium">{{ $p->file_name }}</td>
                        <td class="py-3">
                            @if(!$p->sent_at)
                            <select name="employee_id_{{ $p->id }}" class="payroll-employee-select w-full max-w-[200px] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm" data-payroll-id="{{ $p->id }}">
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $p->employee_id == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                                @endforeach
                            </select>
                            @else
                            {{ $p->employee->full_name ?? '—' }}
                            @endif
                        </td>
                        <td class="py-3">
                            <input type="text" name="email_{{ $p->id }}" value="{{ $p->employee->email ?? '' }}" class="payroll-email-input w-full max-w-[220px] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm" placeholder="email@ejemplo.com">
                        </td>
                        <td class="py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $p->sent_at ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $p->status }}</span>
                        </td>
                        <td class="py-3">
                            <button type="button" class="btn-delete-payroll text-rose-600 hover:text-rose-700 text-xs font-medium" data-payroll-id="{{ $p->id }}">Borrar</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('employees.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver sin enviar</a>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar y enviar</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
            var payrollId = this.dataset.payrollId;
            var employeeId = this.value;
            fetch('{{ url("payroll") }}/' + payrollId + '/assign', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ employee_id: employeeId })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success && data.email !== undefined) {
                    var row = document.querySelector('tr[data-payroll-id="' + payrollId + '"]');
                    if (row) {
                        var emailInput = row.querySelector('.payroll-email-input');
                        if (emailInput) emailInput.value = data.email;
                        var fileCell = row.querySelector('td:nth-child(2)');
                        if (fileCell && data.file_name) fileCell.textContent = data.file_name;
                    }
                }
            });
        });
    });
    document.querySelectorAll('.btn-delete-payroll').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('¿Eliminar esta nómina de la lista?')) return;
            var id = this.dataset.payrollId;
            fetch('{{ url("payroll") }}/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    var row = document.querySelector('tr[data-payroll-id="' + id + '"]');
                    if (row) row.remove();
                }
            });
        });
    });
    document.getElementById('formSendBulk').addEventListener('submit', function(e) {
        var checked = document.querySelectorAll('.cb-send:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('Marca al menos una nómina para enviar.');
        }
    });
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
