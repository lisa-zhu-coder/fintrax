@extends('layouts.app')

@section('title', 'Subir Factura')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Subir Factura</h1>
                <p class="text-sm text-slate-500">Sube un archivo PDF o imagen de factura</p>
            </div>
            <a href="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                ← Volver
            </a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                @foreach($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('invoices.upload.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                <label class="block cursor-pointer">
                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="hidden" id="fileInput" onchange="updateFileName(this)"/>
                    <div class="space-y-4">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="mx-auto text-slate-400">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Haz clic para seleccionar un archivo</p>
                            <p class="mt-1 text-xs text-slate-500">Formatos permitidos: PDF, JPG, PNG (máx. 10MB)</p>
                            <p id="fileName" class="mt-2 text-sm font-medium text-brand-600 hidden"></p>
                        </div>
                    </div>
                </label>
            </div>

            @error('file')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <a href="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Subir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateFileName(input) {
    const fileName = document.getElementById('fileName');
    if (input.files && input.files[0]) {
        fileName.textContent = 'Archivo seleccionado: ' + input.files[0].name;
        fileName.classList.remove('hidden');
    } else {
        fileName.classList.add('hidden');
    }
}
</script>
@endsection
