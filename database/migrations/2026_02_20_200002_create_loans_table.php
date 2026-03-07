<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('loan_type_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 20); // company_owes | they_owe_us
            $table->decimal('initial_amount', 12, 2);
            $table->decimal('interest_rate', 8, 4)->nullable();
            $table->string('interest_type', 20)->nullable(); // fixed | variable
            $table->unsignedInteger('term_months')->nullable();
            $table->date('start_date')->nullable();
            $table->decimal('opening_fee', 12, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
