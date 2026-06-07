@php
    $sortColumn = $column;
    $sortDefaultDir = $defaultDir ?? 'asc';
    $sortByKey = $sortByKey ?? 'sort_by';
    $sortDirKey = $sortDirKey ?? 'sort_dir';
    $currentSort = request($sortByKey);
    $currentDir = request($sortDirKey);
    $flexAlign = match($align ?? 'left') {
        'right' => 'justify-end',
        'center' => 'justify-center',
        default => '',
    };
    $thAlign = match($align ?? 'left') {
        'right' => 'text-right',
        'center' => 'text-center',
        default => '',
    };
    $linkClass = trim('flex items-center gap-1 text-inherit ' . $flexAlign);
    $paddingClass = ($class ?? '') !== '' ? $class : 'py-3';
@endphp
<th class="px-3 {{ $paddingClass }} cursor-pointer hover:bg-slate-100 select-none {{ $thAlign }}">
    <a href="{{ $url }}" class="{{ $linkClass }}">
        {{ $label }}
        @if($currentSort === $sortColumn)
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ $currentDir === 'asc' ? '' : 'rotate-180' }}">
                <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        @endif
    </a>
</th>
