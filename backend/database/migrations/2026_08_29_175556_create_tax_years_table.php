<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A tax year holds the parameters that apply to everyone, whatever their region: the INPS
     * rates, the IRPEF bands and the reliefs. Anything that varies by where you live lives in
     * tax_regions and tax_municipalities instead.
     */
    public function up(): void
    {
        Schema::create('tax_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('label');
            // Until it is published the year is invisible to the calculator, so next year's
            // rates can be entered as the deliberations arrive rather than all at once.
            $table->timestamp('published_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_years');
    }
};
