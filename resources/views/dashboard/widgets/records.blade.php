<div class="widget-content">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="text-xs uppercase text-slate-500 dark:text-slate-400">
                <tr>
                    <th class="px-3 py-2">Fecha</th>
                    <th class="px-3 py-2">Tienda</th>
                    <th class="px-3 py-2">Tipo</th>
                    <th class="px-3 py-2">Concepto</th>
                    <th class="px-3 py-2 text-right">Importe</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @forelse($entries->take(10) as $entry)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-3 py-2">{{ $entry->date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2">{{ $entry->store->name }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                {{ $entry->type === 'income' ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : '' }}
                                {{ $entry->type === 'expense' ? 'bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300' : '' }}
                                {{ $entry->type === 'daily_close' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : '' }}
                                {{ $entry->type === 'expense_refund' ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300' : '' }}
                            ">
                                {{ ucfirst(str_replace('_', ' ', $entry->type)) }}
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ $entry->concept ?? '—' }}</td>
                        <td class="px-3 py-2 text-right font-semibold {{ $entry->type === 'income' ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                            {{ number_format($entry->amount, 2, ',', '.') }} €
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-slate-500">No hay registros</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->count() > 10)
    <div class="mt-4 text-center">
        <a href="{{ route('financial.index') }}" class="text-sm text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300">Ver todos los registros →</a>
    </div>
    @endif
</div>
