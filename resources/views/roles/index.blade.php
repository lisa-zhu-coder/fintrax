@extends('layouts.app')

@section('title', 'Roles y Permisos')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-100">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-100">
            {{ session('error') }}
        </div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Roles y Permisos</h1>
                <p class="text-sm text-slate-500">Gestiona los permisos de cada rol</p>
            </div>
            @if(auth()->user()->hasPermission('admin.roles.edit'))
            <a href="{{ route('roles.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Crear rol
            </a>
            @endif
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
