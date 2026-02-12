<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->after('name');
            $table->string('base_unit', 20)->nullable()->after('type');
        });

        // Migrar datos: is_direct_sale + is_ingredient + ingredients -> type
        $products = DB::table('products')->get();
        foreach ($products as $p) {
            $hasIngredients = DB::table('product_ingredients')->where('product_id', $p->id)->exists();
            $type = 'ingrediente';
            if ($p->is_direct_sale && $hasIngredients) {
                $type = 'compuesto';
            } elseif ($p->is_direct_sale) {
                $type = 'venta_directa';
            } elseif ($p->is_ingredient) {
                $type = 'ingrediente';
            }
            $baseUnit = $p->stock_unit ?: 'unidades';
            if ($baseUnit === 'ud' || $baseUnit === 'g') {
                $baseUnit = $baseUnit === 'ud' ? 'unidades' : 'gr';
            } elseif ($baseUnit === 'l') {
                $baseUnit = 'litros';
            }
            DB::table('products')->where('id', $p->id)->update([
                'type' => $type,
                'base_unit' => $baseUnit,
            ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->nullable(false)->default('ingrediente')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_direct_sale', 'is_ingredient']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_direct_sale')->default(false)->after('name');
            $table->boolean('is_ingredient')->default(false)->after('is_direct_sale');
        });

        $products = DB::table('products')->get();
        foreach ($products as $p) {
            $isDirect = in_array($p->type, ['venta_directa', 'compuesto']);
            $isIngredient = in_array($p->type, ['ingrediente', 'compuesto']);
            DB::table('products')->where('id', $p->id)->update([
                'is_direct_sale' => $isDirect,
                'is_ingredient' => $isIngredient,
            ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'base_unit']);
        });
    }
};
