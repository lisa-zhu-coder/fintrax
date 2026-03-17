<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pending_payroll_uploads')) {
            return;
        }
        Schema::create('pending_payroll_uploads', function (Blueprint $table) {
            $table->string('token', 64)->primary();
            $table->json('payload');
            $table->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_payroll_uploads');
    }
};
