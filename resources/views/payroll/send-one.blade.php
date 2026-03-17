@extends('layouts.app')

@section('title', 'Enviar nómina')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.show', $payroll->employee) }}" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700" title="Volver a la ficha del empleado">&larr;</a>
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Enviar nómina</h1>
                <p class="text-sm text-slate-500">{{ $payroll->file_name }} · {{ $payroll->employee->full_name }}</p>
            </div>
        </div>
    </header>

    <form method="POST" action="{{ route('payroll.send-one', $payroll) }}" class="space-y-6">
        @csrf
        <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
            <div class="space-y-4">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Plantilla</span>
                    <select id="templateSelect" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        <option value="">— Sin plantilla —</option>
                        @foreach($templates as $t)
                        <option value="{{ $t->id }}" data-subject="{{ e($t->subject) }}" data-body="{{ e($t->body) }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Email destinatario</span>
                    <input type="email" name="email" value="{{ old('email', $payroll->employee->email ?? '') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="email@ejemplo.com">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Asunto</span>
                    <input type="text" name="subject" id="subjectInput" value="{{ old('subject', $defaultSubject) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Texto del correo</span>
                    <textarea name="body" id="bodyInput" rows="4" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">{{ old('body', $defaultBody) }}</textarea>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('employees.show', $payroll->employee) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Enviar</button>
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
});
</script>
@endpush
@endsection
