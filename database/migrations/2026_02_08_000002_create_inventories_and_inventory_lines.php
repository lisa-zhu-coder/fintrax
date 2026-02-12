<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week_number')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'product_category_id', 'year', 'month']);
        });

        Schema::create('inventory_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('initial_quantity')->default(0);
            $table->integer('acquired_quantity')->default(0);
            $table->integer('used_quantity')->default(0);
            $table->integer('sold_quantity')->default(0);
            $table->integer('real_quantity')->default(0);
            $table->timestamps();

            $table->unique(['inventory_id', 'product_id']);
        });

        Schema::create('inventory_line_purchase_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_line_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->date('purchase_date');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Migrar datos de product_inventories a inventories + inventory_lines
        if (Schema::hasTable('product_inventories')) {
            $rows = DB::table('product_inventories')->get();
            $inventoriesByKey = [];
            $piToLineId = []; // product_inventory_id -> inventory_line_id

            foreach ($rows as $row) {
                $product = DB::table('products')->find($row->product_id);
                if (!$product || !$product->product_category_id) continue;

                $key = "{$row->company_id}_{$product->product_category_id}_{$row->year}_{$row->month}";
                if (!isset($inventoriesByKey[$key])) {
                    $monthNames = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
                    $invId = DB::table('inventories')->insertGetId([
                        'company_id' => $row->company_id,
                        'product_category_id' => $product->product_category_id,
                        'name' => ($monthNames[$row->month] ?? $row->month) . ' ' . $row->year,
                        'year' => $row->year,
                        'month' => $row->month,
                        'week_number' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $inventoriesByKey[$key] = $invId;
                }
                $invId = $inventoriesByKey[$key];

                $lineId = DB::table('inventory_lines')->insertGetId([
                    'inventory_id' => $invId,
                    'product_id' => $row->product_id,
                    'initial_quantity' => $row->initial_quantity ?? 0,
                    'acquired_quantity' => $row->acquired_quantity ?? 0,
                    'used_quantity' => $row->used_quantity ?? 0,
                    'sold_quantity' => $row->sold_quantity ?? 0,
                    'real_quantity' => $row->real_quantity ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $piToLineId[$row->id] = $lineId;
            }

            // Migrar product_purchase_records a inventory_line_purchase_records
            if (Schema::hasTable('product_purchase_records')) {
                foreach (DB::table('product_purchase_records')->get() as $pr) {
                    $lineId = $piToLineId[$pr->product_inventory_id] ?? null;
                    if (!$lineId) continue;
                    DB::table('inventory_line_purchase_records')->insert([
                        'inventory_line_id' => $lineId,
                        'quantity' => $pr->quantity,
                        'purchase_date' => $pr->purchase_date,
                        'user_id' => $pr->user_id,
                        'created_at' => $pr->created_at ?? now(),
                        'updated_at' => $pr->updated_at ?? now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_line_purchase_records');
        Schema::dropIfExists('inventory_lines');
        Schema::dropIfExists('inventories');
    }
};
