<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inventory_purchase_records');
        Schema::dropIfExists('inventory_lines');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('inventory_base_products');
        Schema::dropIfExists('inventory_bases');
    }

    public function down(): void
    {
        // No restore - fresh start per plan
    }
};
