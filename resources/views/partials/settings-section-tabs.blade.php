@php
    use App\Support\SettingsNavigation;
    $tabs = SettingsNavigation::tabsFor($group ?? '');
@endphp

@if($tabs->count() > 1)
<nav class="mb-4 flex flex-wrap gap-1 border-b border-slate-200 dark:border-slate-600" aria-label="Ajustes del módulo">
    @foreach($tabs as $tab)
    <a href="{{ $tab['href'] }}"
       class="inline-flex items-center border-b-2 px-3 py-2 text-sm font-medium transition-colors -mb-px {{ $tab['active'] ? 'border-brand-600 text-brand-700 dark:text-brand-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:border-slate-500 dark:hover:text-slate-200' }}">
        {{ $tab['label'] }}
    </a>
    @endforeach
</nav>
@endif
