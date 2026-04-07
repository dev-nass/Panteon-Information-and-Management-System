<?php

use App\Models\Cluster;
use App\Models\Phase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Cluster::class)->constrained()->cascadeOnDelete();

            // APT. NUM combination of these two
            $table->string('column')->comment('Position/Column letter e.g., 1, 2, 3...');
            $table->string('row')->comment('Level/Row number e.g., A, B, C...');

            // ✅ Geometry column
            $table->geometry('coordinates', 4326);

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
        Schema::dropIfExists('lots');
    }
};
