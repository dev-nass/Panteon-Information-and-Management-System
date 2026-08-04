<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deceased_records', function (Blueprint $table) {
            $table->enum('corpse_disposal', ['burial', 'muslim', 'cremation'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deceased_records', function (Blueprint $table) {
            $table->enum('corpse_disposal', ['burial', 'cremation', 'other'])->nullable()->change();
        });
    }
};
