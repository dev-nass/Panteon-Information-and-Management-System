<?php

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
        Schema::create('clusters', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Phase::class)->constrained()->cascadeOnDelete();
            $table->string('cluster_name')->unique();
            $table->enum('cluster_type', ['underground', 'apartment', 'columbarium']);
            $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
            $table->bigInteger('total_capacity')->nullable();

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
        Schema::dropIfExists('clusters');
    }
};
