<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_purchase_records');
        Schema::dropIfExists('product_inventories');
    }

    public function down(): void
    {
        // No restore - data was migrated to inventory_lines
    }
};
