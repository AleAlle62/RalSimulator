<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The twenty single figures the engine needs that are the same everywhere: contribution
     * rates and ceilings, the relief thresholds and amounts, the 75 euro offset. The municipal
     * rate and its exemption are not among them — they change from town to town, so they are
     * columns of tax_municipalities.
     *
     * Deliberately key and value rather than one column per figure. A named column per
     * constant would mean a migration every time the law adds a parameter, and would leave
     * nowhere to record the source of each one. The price is that a key is a string, so the
     * allowed keys live in App\TaxTables\TaxConstantKey and a test asserts every one of them
     * is present.
     */
    public function up(): void
    {
        Schema::create('tax_constants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_year_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->decimal('value', 14, 6);

            $table->string('source_url')->nullable();
            $table->string('source_label')->nullable();

            $table->timestamps();

            $table->unique(['tax_year_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_constants');
    }
};
