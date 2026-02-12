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
            $table->boolean('is_sellable')->default(false)->after('name');
            $table->boolean('is_ingredient')->default(false)->after('is_sellable');
            $table->boolean('is_composed')->default(false)->after('is_ingredient');
        });

        // Migrar datos desde type
        $products = DB::table('products')->get();
        foreach ($products as $p) {
            $isSellable = in_array($p->type ?? '', ['venta_directa', 'compuesto']);
            $isIngredient = in_array($p->type ?? '', ['ingrediente', 'compuesto']);
            $isComposed = ($p->type ?? '') === 'compuesto';

            DB::table('products')->where('id', $p->id)->update([
                'is_sellable' => $isSellable,
                'is_ingredient' => $isIngredient,
                'is_composed' => $isComposed,
            ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->after('name');
        });

        $products = DB::table('products')->get();
        foreach ($products as $p) {
            $type = 'ingrediente';
            if ($p->is_composed) {
                $type = 'compuesto';
            } elseif ($p->is_sellable) {
                $type = 'venta_directa';
            } elseif ($p->is_ingredient) {
                $type = 'ingrediente';
            }

            DB::table('products')->where('id', $p->id)->update(['type' => $type]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->nullable(false)->default('ingrediente')->change();
            $table->dropColumn(['is_sellable', 'is_ingredient', 'is_composed']);
        });
    }
};
