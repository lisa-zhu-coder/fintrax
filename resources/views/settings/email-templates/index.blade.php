@extends('layouts.app')

@section('title', 'Plantillas de email RRHH')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="mb-1 text-xs text-slate-500">
                    <span class="text-slate-700">Ajustes</span><span class="mx-1">/</span><span>Plantillas de email RRHH</span>
                </nav>
                <h1 class="text-lg font-semibold">Plantillas de email RRHH</h1>
                <p class="text-sm text-slate-500">Plantillas para el envío de nóminas y documentos a empleados. Variables: &#123;&#123;nombre&#125;&#125;, &#123;&#123;mes&#125;&#125;, &#123;&#123;empresa&#125;&#125;.</p>
            </div>
            <a href="{{ route('email-templates-settings.create') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Nueva plantilla</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if($templates->isEmpty())
        <p class="text-slate-600">No hay plantillas. Crea una para usarla en el envío de nóminas.</p>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="rounded-tl px-3 py-2">Nombre</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">Asunto</th>
                        <th class="rounded-tr px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($templates as $t)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 font-medium">{{ $t->name }}</td>
                        <td class="px-3 py-2">{{ $t->type === 'payroll' ? 'Nómina' : 'Documento' }}</td>
                        <td class="max-w-xs truncate px-3 py-2 text-slate-600">{{ $t->subject }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('email-templates-settings.edit', $t) }}" class="rounded-lg px-2 py-1 text-brand-600 hover:bg-brand-50">Editar</a>
                            <form method="POST" action="{{ route('email-templates-settings.destroy', $t) }}" class="inline" onsubmit="return confirm('¿Eliminar esta plantilla?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-2 py-1 text-rose-600 hover:bg-rose-50">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
