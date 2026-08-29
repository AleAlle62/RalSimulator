<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A saved simulation keeps the answer, not the ingredients to recompute it.
     *
     * `result` is a snapshot taken when the simulation was run. Recomputing on read would
     * mean a link shared today shows different numbers next year, once the rates behind it
     * have moved — the reader would see figures the sender never saw.
     *
     * `user_id` is nullable on purpose: simulating needs no account, and an anonymous
     * simulation is still reachable through its token.
     */
    public function up(): void
    {
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 26)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('tax_year_id')->constrained();
            $table->foreignId('tax_municipality_id')->constrained();

            $table->decimal('gross_annual_salary', 12, 2);
            $table->unsignedTinyInteger('monthly_payments_count');
            $table->string('sector');

            $table->json('result');

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulations');
    }
};
