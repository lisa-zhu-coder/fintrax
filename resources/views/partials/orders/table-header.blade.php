@php
    $alignClass = match($column['align'] ?? 'left') {
        'right' => 'text-right',
        'center' => 'text-center',
        default => '',
    };
@endphp
@if(!empty($column['sortable']) && !empty($sortUrlCallback) && !empty($column['sort_key']))
    @include('partials.sortable-th', [
        'label' => $column['label'],
        'column' => $column['sort_key'],
        'defaultDir' => $defaultDir ?? 'asc',
        'url' => $sortUrlCallback($column['sort_key'], $defaultDir ?? 'asc'),
        'class' => 'py-2',
        'align' => $column['align'] ?? 'left',
        'sortByKey' => $sortByKey ?? 'sort_by',
        'sortDirKey' => $sortDirKey ?? 'sort_dir',
    ])
@else
    <th class="px-3 py-2 {{ $alignClass }}">{{ $column['label'] }}</th>
@endif
