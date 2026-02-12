<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->date('date');
            $table->string('client_name');
            $table->string('phone')->nullable();
            $table->string('article')->nullable();
            $table->string('sku')->nullable();
            $table->string('reason')->nullable();
            $table->string('status'); // pending, fixed, notified, completed, cancelled
            $table->date('notification_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_repairs');
    }
};
