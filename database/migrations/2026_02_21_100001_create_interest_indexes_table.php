<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_indexes', function (Blueprint $table) {
            $table->id();
            $table->string('index_name', 50);
            $table->date('date');
            $table->decimal('rate', 8, 4);
            $table->timestamps();
        });
        Schema::table('interest_indexes', function (Blueprint $table) {
            $table->unique(['index_name', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_indexes');
    }
};
