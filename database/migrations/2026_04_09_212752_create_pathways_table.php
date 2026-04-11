<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pathways', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_junction_id')->constrained('junctions')->cascadeOnDelete();
            $table->foreignId('to_junction_id')->constrained('junctions')->cascadeOnDelete();

            $table->decimal('distance_meters', 8, 2)
                ->comment('Calculated distance between junctions in meters');

            // ✅ Geometry column (LINESTRING)
            $table->geometry('coordinates', 4326)
                ->comment('LineString coordinates for pathway visualization');

            // ✅ Spatial index
            $table->spatialIndex('coordinates');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pathways');
    }
};
