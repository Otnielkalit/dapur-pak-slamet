<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_entries', function (Blueprint $table) {
            $table->id();

            // Snapshot fields (so export/history remains stable)
            $table->string('customer_code');
            $table->string('customer_name');
            $table->string('customer_phone', 30)->nullable();
            $table->string('workplace_name');

            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workplace_id')->constrained()->cascadeOnDelete();

            $table->timestamp('eaten_at');
            $table->unsignedInteger('price');

            $table->boolean('paid')->default(false);
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['eaten_at', 'paid']);
            $table->index(['workplace_id', 'eaten_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_entries');
    }
};

