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
        Schema::create('junctions', function (Blueprint $table) {
            $table->id();

            $table->string('junction_number')->unique();
            $table->enum('type', ['entrance', 'intersection', 'endpoint'])->default('intersection');
            
            // ✅ Geometry column (POINT)
            $table->geometry('coordinates', 4326);
            
            // ✅ Spatial index
            $table->spatialIndex('coordinates');
            
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('junctions');
    }
};
