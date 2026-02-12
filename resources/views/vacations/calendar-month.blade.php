@extends('layouts.app')

@section('title', 'Vacaciones — ' . $store->name . ' — ' . $monthName . ' ' . $year)

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('vacations.index') }}" class="hover:text-brand-600">Vacaciones</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('vacations.store', ['store' => $store, 'year' => $year]) }}" class="hover:text-brand-600">{{ $store->name }}</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('vacations.calendar-months', ['store' => $store, 'year' => $year]) }}" class="hover:text-brand-600">{{ $year }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $monthName }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $monthName }} {{ $year }}</h1>
                <p class="text-sm text-slate-500">Haz clic en cada celda para marcar o desmarcar vacaciones.</p>
            </div>
            <div class="flex gap-2">
                @if(auth()->user()->hasPermission('hr.vacations.edit'))
                <button type="button" onclick="document.getElementById('register-modal').classList.remove('hidden')" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Registrar vacaciones</button>
                @endif
                <a href="{{ route('vacations.calendar-months', ['store' => $store, 'year' => $year]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Meses</a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100 overflow-x-auto">
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th class="px-2 py-2 text-left text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200 sticky left-0 bg-slate-50 z-10 min-w-[120px]">Empleado</th>
                    @foreach($weeks as $week)
                        <th colspan="7" class="px-1 py-1 text-center text-xs font-semibold text-slate-600 bg-slate-100 border border-slate-200">Semana {{ $week->num }}</th>
                    @endforeach
                    <th class="px-2 py-2 text-center text-xs font-semibold text-slate-600 bg-slate-100 border border-slate-200 sticky right-0 bg-slate-100 z-10">Vacaciones disfrutadas</th>
                </tr>
                <tr>
                    <th class="px-2 py-1 text-left text-xs text-slate-500 bg-slate-50 border border-slate-200 sticky left-0 bg-slate-50 z-10"></th>
                    @foreach($weeks as $week)
                        @foreach($week->days as $dayCell)
                            <th class="px-1 py-1 text-center text-xs text-slate-500 bg-slate-50 border border-slate-200 w-8">{{ $dayCell->weekdayName }}</th>
                        @endforeach
                    @endforeach
                    <th class="px-2 py-1 text-center text-xs text-slate-500 bg-slate-50 border border-slate-200 sticky right-0 bg-slate-50 z-10"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                    @php
                        $empVacationCount = (int) ($vacationDaysByEmployee[$employee->id] ?? 0);
                    @endphp
                    <tr>
                        <td class="px-2 py-1 font-medium text-slate-800 border border-slate-200 sticky left-0 bg-white z-10">{{ $employee->full_name }}</td>
                        @foreach($weeks as $week)
                            @foreach($week->days as $dayCell)
                                @php
                                    $key = $employee->id . '_' . $dayCell->date;
                                    $isVacation = isset($vacationDays[$key]);
                                @endphp
                                <td class="p-0 border border-slate-200 align-top">
                                    @if(auth()->user()->hasPermission('hr.vacations.edit'))
                                    <button type="button"
                                        class="vacation-cell w-full min-h-[2rem] px-1 py-1 text-xs {{ $isVacation ? 'bg-emerald-200 hover:bg-emerald-300' : 'bg-white hover:bg-slate-100' }} cursor-pointer transition-colors disabled:opacity-70 disabled:cursor-wait"
                                        data-employee-id="{{ $employee->id }}"
                                        data-date="{{ $dayCell->date }}"
                                        data-vacation="{{ $isVacation ? '1' : '0' }}"
                                        title="{{ $dayCell->date }}"
                                    >{{ $dayCell->day }}</button>
                                    @else
                                    <div class="w-full min-h-[2rem] px-1 py-1 text-xs {{ $isVacation ? 'bg-emerald-200' : 'bg-white' }}">{{ $dayCell->day }}</div>
                                    @endif
                                </td>
                            @endforeach
                        @endforeach
                        <td class="px-2 py-1 text-center font-medium text-slate-700 border border-slate-200 sticky right-0 bg-white z-10 vacation-total-cell" data-employee-id="{{ $employee->id }}">{{ $empVacationCount }}</td>
                    </tr>
                @endforeach
                @if($employees->isEmpty())
                    <tr>
                        <td colspan="{{ 1 + count($weeks) * 7 + 1 }}" class="px-4 py-8 text-center text-slate-500">No hay empleados en esta tienda.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if(auth()->user()->hasPermission('hr.vacations.edit'))
    {{-- Modal Registrar vacaciones por semanas --}}
    <div id="register-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" onclick="document.getElementById('register-modal').classList.add('hidden')"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold mb-4">Registrar vacaciones por semanas</h2>
                <form method="POST" action="{{ route('vacations.register-weeks') }}">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $store->id }}">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Empleado *</label>
                            <select name="employee_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Selecciona empleado</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Semanas del mes</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($weeks as $week)
                                    @php
                                        $firstDay = $week->days[0];
                                        $lastDay = $week->days[6];
                                    @endphp
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="weeks[]" value="{{ $week->num }}" class="rounded border-slate-300 text-brand-600">
                                        <span class="text-sm">Semana {{ $week->num }} ({{ \Carbon\Carbon::parse($firstDay->date)->format('d/m') }}–{{ \Carbon\Carbon::parse($lastDay->date)->format('d/m') }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-2 justify-end">
                        <button type="button" onclick="document.getElementById('register-modal').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

@if(auth()->user()->hasPermission('hr.vacations.edit'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.vacation-cell').forEach(function(cell) {
        cell.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var btn = this;
            if (btn.disabled) return;
            btn.disabled = true;

            var formData = new FormData();
            formData.append('employee_id', btn.dataset.employeeId);
            formData.append('date', btn.dataset.date);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("vacations.toggle-day") }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData
            })
            .then(function(r) {
                if (!r.ok) return r.text().then(function(t) { throw new Error('HTTP ' + r.status + ': ' + t.slice(0, 200)); });
                return r.json();
            })
            .then(function(data) {
                if (data.success) {
                    if (data.is_vacation) {
                        btn.classList.add('bg-emerald-200', 'hover:bg-emerald-300');
                        btn.classList.remove('bg-white', 'hover:bg-slate-100');
                        btn.dataset.vacation = '1';
                    } else {
                        btn.classList.remove('bg-emerald-200', 'hover:bg-emerald-300');
                        btn.classList.add('bg-white', 'hover:bg-slate-100');
                        btn.dataset.vacation = '0';
                    }
                    var row = btn.closest('tr');
                    var count = row.querySelectorAll('.vacation-cell[data-vacation="1"]').length;
                    var totalCell = row.querySelector('.vacation-total-cell');
                    if (totalCell) totalCell.textContent = count;
                }
            })
            .catch(function(err) { console.error('Vacaciones toggle:', err); })
            .finally(function() { btn.disabled = false; });
        });
    });
});
</script>
@endif
@endsection
