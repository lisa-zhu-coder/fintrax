<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\FinancialEntry;
use App\Models\Order;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\InvoiceDataExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:invoices.main.view')->only(['index', 'show', 'download', 'preview', 'serve']);
        $this->middleware('permission:invoices.main.create')->only(['create', 'store', 'upload', 'storeFromUpload']);
        $this->middleware('permission:invoices.main.edit')->only(['edit', 'update']);
        $this->middleware('permission:invoices.main.delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['creator']);

        // Filtros opcionales
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('supplier') && $request->supplier !== '') {
            $query->where('supplier_name', 'like', '%' . $request->supplier . '%');
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * Limpiar datos de sesión de archivo subido (cuando el usuario cancela)
     */
    public function clearUploadSession()
    {
        $filePath = session('uploaded_file_path');
        
        // Eliminar archivo temporal si existe
        if ($filePath && Storage::disk('local')->exists($filePath)) {
            try {
                Storage::disk('local')->delete($filePath);
            } catch (\Exception $e) {
                Log::warning('Error eliminando archivo temporal: ' . $e->getMessage());
            }
        }
        
        // Limpiar sesión
        session()->forget(['uploaded_file_path', 'uploaded_file_name', 'extracted_data', 'from_upload']);
        
        return redirect()->route('invoices.index')
            ->with('info', 'Subida de archivo cancelada');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = Store::all();
        
        // Obtener datos extraídos de la sesión si vienen de una subida de archivo
        $extractedData = session('extracted_data', []);
        $uploadedFileName = session('uploaded_file_name');
        
        return view('invoices.create', compact('stores', 'extractedData', 'uploadedFileName'));
    }

    /**
     * Show the form for uploading an invoice file.
     */
    public function upload()
    {
        return view('invoices.upload');
    }

    /**
     * Store a new invoice from uploaded file.
     * Guarda el archivo temporalmente y extrae los datos, pero NO crea la factura.
     * Redirige al formulario de creación con los datos prellenados.
     */
    public function storeFromUpload(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB máximo
        ]);

        try {
            $file = $request->file('file');
            $year = Carbon::now()->format('Y');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs("invoices/{$year}", $fileName, 'local');

            // Extraer datos automáticamente del archivo
            $extractor = new InvoiceDataExtractor();
            $extractedData = $extractor->extractFromFile(
                Storage::disk('local')->path($filePath),
                $file->getMimeType()
            );

            // Guardar archivo y datos extraídos en la sesión para prellenar el formulario
            session([
                'uploaded_file_path' => $filePath,
                'uploaded_file_name' => $file->getClientOriginalName(),
                'extracted_data' => [
                    'date' => $extractedData['date'] ?? Carbon::now()->toDateString(),
                    'invoice_number' => $extractedData['invoice_number'] ?? null,
                    'total_amount' => $extractedData['total_amount'] ?? 0,
                    'supplier_name' => $extractedData['supplier_name'] ?? '',
                    'details' => $extractedData['details'] ?? null,
                ],
            ]);

            $message = 'Archivo subido correctamente.';
            if ($extractedData['supplier_name'] || $extractedData['total_amount'] > 0 || $extractedData['invoice_number']) {
                $message .= ' Se han detectado algunos datos automáticamente. Revisa y completa los campos antes de guardar.';
            } else {
                $message .= ' Completa los datos de la factura antes de guardar.';
            }

            return redirect()->route('invoices.create')
                ->with('success', $message)
                ->with('from_upload', true);
        } catch (\Exception $e) {
            Log::error('Error subiendo factura: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error al subir el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'invoice_number' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'supplier_name' => 'required|string|max:255',
            'details' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB máximo
            'payment_method' => 'nullable|in:cash,card',
            'status' => 'required|in:pendiente,pagada',
            'crear_gasto' => 'nullable|boolean',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        try {
            $filePath = null;

            if (session('uploaded_file_path')) {
                $filePath = session('uploaded_file_path');
                session()->forget(['uploaded_file_path', 'uploaded_file_name', 'extracted_data', 'from_upload']);
            } elseif ($request->hasFile('file')) {
                $file = $request->file('file');
                $year = Carbon::parse($validated['date'])->format('Y');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs("invoices/{$year}", $fileName, 'local');
            }

            $invoice = Invoice::create([
                'date' => $validated['date'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'total_amount' => $validated['total_amount'],
                'supplier_name' => $validated['supplier_name'],
                'details' => $validated['details'] ?? null,
                'file_path' => $filePath,
                'payment_method' => $validated['payment_method'] ?? null,
                'status' => $validated['status'],
                'created_by' => Auth::id(),
            ]);

            // Buscar proveedor por nombre para asociar al gasto
            $supplier = Supplier::where('name', $validated['supplier_name'])->first();
            $hasRelatedOrders = !empty($validated['invoice_number'])
                && Order::where('invoice_number', $validated['invoice_number'])->exists();

            // Crear gasto automáticamente solo si no hay pedidos relacionados (los pedidos crean sus propios gastos)
            if (!$hasRelatedOrders && !empty($validated['store_id'])) {
                $supplier = Supplier::where('name', $validated['supplier_name'])->first();
                $expensePaymentMethod = match ($validated['payment_method'] ?? null) {
                    'cash' => 'cash',
                    'card' => 'card',
                    default => null,
                };

                FinancialEntry::create([
                    'date' => $validated['date'],
                    'store_id' => $validated['store_id'],
                    'supplier_id' => $supplier?->id,
                    'type' => 'expense',
                    'total_amount' => $validated['total_amount'],
                    'expense_amount' => $validated['total_amount'],
                    'amount' => $validated['total_amount'],
                    'concept' => 'Factura: ' . $validated['supplier_name'],
                    'expense_concept' => 'Factura: ' . $validated['supplier_name'],
                    'expense_source' => 'factura',
                    'expense_payment_method' => $expensePaymentMethod,
                    'notes' => 'Gasto creado automáticamente desde factura #' . $invoice->id,
                    'paid_amount' => 0,
                    'status' => 'pendiente',
                    'invoice_id' => $invoice->id,
                    'created_by' => Auth::id(),
                ]);
            }

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Factura creada correctamente');
        } catch (\Exception $e) {
            Log::error('Error creando factura: ' . $e->getMessage());
            
            // Si hay un error y había un archivo en sesión, mantenerlo para que el usuario no lo pierda
            // (solo se limpiará cuando se cree exitosamente o cuando el usuario cancele)
            
            return back()->withInput()->with('error', 'Error al crear la factura');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice = Invoice::with(['creator', 'financialEntries.store'])->findOrFail($id);
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $invoice = Invoice::findOrFail($id);
        
        // Obtener el gasto actual asociado a esta factura si existe
        $currentExpense = FinancialEntry::where('invoice_id', $invoice->id)->first();
        
        // Obtener gastos disponibles (tipo expense sin invoice_id asignado)
        // También incluir el gasto actual si existe para que aparezca en el select
        $availableExpenses = FinancialEntry::where('type', 'expense')
            ->where(function($query) use ($invoice, $currentExpense) {
                $query->whereNull('invoice_id');
                // Incluir el gasto actual si existe
                if ($currentExpense) {
                    $query->orWhere('id', $currentExpense->id);
                }
            })
            ->with(['store'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('invoices.edit', compact('invoice', 'availableExpenses', 'currentExpense'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date',
            'invoice_number' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'supplier_name' => 'required|string|max:255',
            'details' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB máximo
            'payment_method' => 'nullable|in:cash,card',
            'status' => 'required|in:pendiente,pagada',
            'expense_id' => 'nullable|exists:financial_entries,id',
        ]);

        // Validar que el gasto seleccionado sea de tipo expense
        if ($request->has('expense_id') && $request->expense_id) {
            $expense = FinancialEntry::findOrFail($request->expense_id);
            if ($expense->type !== 'expense') {
                return back()->withInput()->with('error', 'Solo se pueden asociar gastos de tipo expense');
            }
        }

        try {
            // Manejar subida de archivo si existe
            if ($request->hasFile('file')) {
                // Eliminar archivo anterior si existe
                if ($invoice->file_path && Storage::disk('local')->exists($invoice->file_path)) {
                    Storage::disk('local')->delete($invoice->file_path);
                }

                $file = $request->file('file');
                $year = Carbon::parse($validated['date'])->format('Y');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs("invoices/{$year}", $fileName, 'local');
                $validated['file_path'] = $filePath;
            } else {
                // Si no se sube nuevo archivo, mantener el existente
                $validated['file_path'] = $invoice->file_path;
            }

            $invoice->update($validated);

            // Manejar enlace con gasto existente
            if ($request->has('expense_id') && $request->expense_id) {
                // Desasociar gasto anterior si existe
                FinancialEntry::where('invoice_id', $invoice->id)
                    ->where('id', '!=', $request->expense_id)
                    ->update(['invoice_id' => null]);
                
                // Asociar el gasto seleccionado
                $expense = FinancialEntry::findOrFail($request->expense_id);
                $expense->update(['invoice_id' => $invoice->id]);
            } else {
                // Si no se selecciona ningún gasto, desasociar el actual si existe
                FinancialEntry::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
            }

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Factura actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error actualizando factura: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error al actualizar la factura');
        }
    }

    /**
     * Download the invoice file.
     */
    public function download(string $id)
    {
        $invoice = Invoice::findOrFail($id);

        if (!$invoice->file_path || !Storage::disk('local')->exists($invoice->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::disk('local')->download($invoice->file_path);
    }

    /**
     * Preview the invoice file in the browser.
     */
    public function preview(string $id)
    {
        $invoice = Invoice::findOrFail($id);

        if (!$invoice->file_path || !Storage::disk('local')->exists($invoice->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        $mimeType = Storage::disk('local')->mimeType($invoice->file_path);
        
        return view('invoices.preview', [
            'invoice' => $invoice,
            'mimeType' => $mimeType,
        ]);
    }

    /**
     * Serve the invoice file for preview.
     */
    public function serve(string $id)
    {
        $invoice = Invoice::findOrFail($id);

        if (!$invoice->file_path || !Storage::disk('local')->exists($invoice->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        $filePath = Storage::disk('local')->path($invoice->file_path);
        $mimeType = Storage::disk('local')->mimeType($invoice->file_path);
        
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($invoice->file_path) . '"',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            
            // NO eliminar archivo físico aquí para mantener integridad de papelera
            // El archivo se eliminará solo cuando se haga forceDelete() desde la papelera
            
            // Desasociar gastos relacionados (solo desasociar, no eliminar)
            FinancialEntry::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
            
            $invoice->delete();

            return redirect()->route('invoices.index')
                ->with('success', 'Factura eliminada correctamente');
        } catch (\Exception $e) {
            Log::error('Error eliminando factura: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar la factura');
        }
    }
}
