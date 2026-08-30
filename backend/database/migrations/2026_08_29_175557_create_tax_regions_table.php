<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The regional surtax is always progressive, so a region carries no rate of its own: its
     * bands live in tax_brackets. What is here is the identity and the source.
     */
    public function up(): void
    {
        Schema::create('tax_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_year_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('source_url')->nullable();
            $table->string('source_label')->nullable();
            $table->timestamps();

            $table->unique(['tax_year_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_regions');
    }
};
