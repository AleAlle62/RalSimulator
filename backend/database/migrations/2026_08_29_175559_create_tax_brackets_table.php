<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every list of income bands in the simulator, in one table: the IRPEF bands, the exempt
     * wedge cut rates, each region's surtax, and eventually the municipalities that use bands
     * of their own. `kind` says which, and `owner_id` points at the region or municipality
     * when the list belongs to one.
     *
     * `position` matters: bands are read in order, and a list sorted by rate rather than by
     * bound would compute a plausible but wrong tax.
     */
    public function up(): void
    {
        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_year_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->unsignedBigInteger('owner_id')->nullable();

            // Null means the band has no upper bound: everything above the previous one.
            $table->decimal('upper_bound', 12, 2)->nullable();
            $table->decimal('rate', 8, 6);
            $table->unsignedTinyInteger('position');

            $table->string('source_url')->nullable();
            $table->string('source_label')->nullable();

            $table->timestamps();

            $table->index(['tax_year_id', 'kind', 'owner_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_brackets');
    }
};
