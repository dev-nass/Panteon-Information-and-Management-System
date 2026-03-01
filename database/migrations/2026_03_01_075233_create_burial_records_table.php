<?php

use App\Models\DeceasedRecord;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('burial_records', function (Blueprint $table) {
            $table->id();

            // added nullable to ensure nullOnDelete works
            $table->foreignIdFor(Lot::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(DeceasedRecord::class, 'deceased_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('burial_records');
    }
};
