@extends('layouts.app')

@section('title', 'Roles y Permisos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Roles y Permisos</h1>
                <p class="text-sm text-slate-500">Gestiona los permisos de cada rol</p>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        @foreach($roles as $role)
        <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold">{{ $role->name }}</h2>
                    <p class="text-xs text-slate-500">Nivel {{ $role->level }}</p>
                    @if($role->description)
                    <p class="mt-1 text-sm text-slate-600">{{ $role->description }}</p>
                    @endif
                </div>
                <a href="{{ route('roles.edit', $role) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Editar
                </a>
            </div>
            
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach(($role->permissions ?? []) as $key => $value)
                    @if(is_bool($value))
                        <div class="flex items-center justify-between text-sm gap-2">
                            <span class="text-slate-700 truncate" title="{{ $key }}">{{ $key }}</span>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2 py-1 text-xs font-medium {{ $value ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $value ? 'Sí' : 'No' }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
