<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Municipalities mostly charge one rate behind an exemption, which is what `rate` and
     * `exemption_threshold` describe. A minority — Torino and Genova among the ten seeded
     * cities — use bands of their own; those will hang off tax_brackets, which is why the
     * rate is nullable rather than required.
     *
     * `cadastral_code` is the key used by the MEF list, so a future importer can match rows
     * against the official file without relying on the spelling of the name.
     */
    public function up(): void
    {
        Schema::create('tax_municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_region_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('province', 2);
            $table->string('cadastral_code', 4)->nullable();

            $table->decimal('rate', 8, 6)->nullable();
            $table->decimal('exemption_threshold', 12, 2)->default(0);

            // The deliberation is the primary source: number and date identify it exactly.
            $table->string('deliberation_number')->nullable();
            $table->date('deliberation_date')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_label')->nullable();

            $table->timestamps();

            $table->unique(['tax_year_id', 'name', 'province']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_municipalities');
    }
};
