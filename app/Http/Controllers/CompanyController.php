<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyBusiness;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:admin.company.view')->only(['show']);
        $this->middleware('permission:admin.company.edit')->only(['update', 'storeBusiness', 'updateBusiness', 'destroyBusiness']);
    }

    public function show()
    {
        $company = Company::first();
        $businesses = CompanyBusiness::all();

        // Sincronizar negocios (empresa) con stores (tiendas usadas por registros/pedidos/empleados)
        // Esto evita que los selects de tienda aparezcan vacíos si solo se han creado negocios en Empresa.
        try {
            DB::transaction(function () use ($businesses) {
                foreach ($businesses as $business) {
                    $slug = $business->slug;
                    if (!$slug) {
                        $slug = Str::slug($business->name, '_');
                        // asegurar unicidad en ambas tablas
                        $base = $slug;
                        $i = 1;
                        while (
                            CompanyBusiness::where('slug', $slug)->where('id', '!=', $business->id)->exists() ||
                            Store::where('slug', $slug)->exists()
                        ) {
                            $slug = $base . '_' . $i;
                            $i++;
                        }
                        $business->slug = $slug;
                        $business->save();
                    }

                    $store = Store::where('slug', $slug)->first();
                    if (!$store) {
                        Store::create([
                            'name' => $business->name,
                            'slug' => $slug,
                        ]);
                    } else {
                        // Mantener nombre sincronizado
                        if ($store->name !== $business->name) {
                            $store->name = $business->name;
                            $store->save();
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            // No bloquear la vista por un fallo de sincronización
            report($e);
        }
        
        return view('company.show', compact('company', 'businesses'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cif' => 'nullable|string|max:255',
            'fiscal_street' => 'nullable|string|max:255',
            'fiscal_postal_code' => 'nullable|string|max:10',
            'fiscal_city' => 'nullable|string|max:255',
            'fiscal_email' => 'nullable|email|max:255',
        ]);

        $company = Company::first();
        if ($company) {
            $company->update($validated);
        } else {
            Company::create($validated);
        }

        return redirect()->route('company.show')->with('success', 'Datos de la empresa actualizados correctamente.');
    }

    public function storeBusiness(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'street' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        // Generar slug automáticamente desde el nombre
        $slug = Str::slug($request->name, '_');
        
        // Asegurar que el slug sea único
        $originalSlug = $slug;
        $counter = 1;
        while (CompanyBusiness::where('slug', $slug)->exists() || Store::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '_' . $counter;
            $counter++;
        }

        $validated['slug'] = $slug;

        try {
            DB::transaction(function () use ($validated, $slug) {
                // Crear Store (fuente usada por selects en registros/pedidos/empleados)
                Store::create([
                    'name' => $validated['name'],
                    'slug' => $slug,
                ]);
                // Crear CompanyBusiness (detalle de empresa)
                CompanyBusiness::create($validated);
            });
            return redirect()->route('company.show')->with('success', 'Negocio creado correctamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Error de base de datos (probablemente slug duplicado)
            if (str_contains($e->getMessage(), 'UNIQUE constraint')) {
                return redirect()->route('company.show')
                    ->withInput()
                    ->withErrors(['name' => 'Ya existe un negocio con un nombre similar.']);
            }
            return redirect()->route('company.show')
                ->withInput()
                ->withErrors(['error' => 'Error al crear el negocio: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()->route('company.show')
                ->withInput()
                ->withErrors(['error' => 'Error al crear el negocio: ' . $e->getMessage()]);
        }
    }

    public function updateBusiness(Request $request, CompanyBusiness $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'street' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $oldSlug = $business->slug;

        // Generar slug automáticamente desde el nombre si cambió
        if ($request->name !== $business->name) {
            $slug = Str::slug($request->name, '_');
            
            // Asegurar que el slug sea único (excepto el actual) en ambas tablas
            $originalSlug = $slug;
            $counter = 1;
            while (
                CompanyBusiness::where('slug', $slug)->where('id', '!=', $business->id)->exists() ||
                Store::where('slug', $slug)->where('slug', '!=', $oldSlug)->exists()
            ) {
                $slug = $originalSlug . '_' . $counter;
                $counter++;
            }
            
            $validated['slug'] = $slug;
        }

        try {
            DB::transaction(function () use ($business, $validated, $oldSlug) {
                $business->update($validated);

                $newSlug = $business->slug;
                // Actualizar Store asociado (por slug)
                $store = Store::where('slug', $oldSlug)->first();
                if (!$store) {
                    // Si por cualquier motivo no existe, crearlo
                    $store = Store::create([
                        'name' => $business->name,
                        'slug' => $newSlug,
                    ]);
                } else {
                    $store->name = $business->name;
                    $store->slug = $newSlug;
                    $store->save();
                }
            });
            return redirect()->route('company.show')->with('success', 'Negocio actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('company.show')
                ->withInput()
                ->withErrors(['error' => 'Error al actualizar el negocio: ' . $e->getMessage()]);
        }
    }

    public function destroyBusiness(CompanyBusiness $business)
    {
        try {
            DB::transaction(function () use ($business) {
                $store = $business->slug ? Store::where('slug', $business->slug)->first() : null;

                if ($store) {
                    // Si la tienda está en uso, no permitir eliminar (para mantener coherencia con selects)
                    $hasUsage =
                        $store->financialEntries()->exists() ||
                        $store->orders()->exists() ||
                        $store->employees()->exists() ||
                        $store->users()->exists();

                    if ($hasUsage) {
                        throw new \RuntimeException('No se puede eliminar este negocio porque ya tiene datos asociados (registros, pedidos, empleados o usuarios).');
                    }

                    $store->delete();
                }

                $business->delete();
            });

            return redirect()->route('company.show')->with('success', 'Negocio eliminado correctamente.');
        } catch (\Throwable $e) {
            return redirect()->route('company.show')->with('error', $e->getMessage());
        }
    }
}
