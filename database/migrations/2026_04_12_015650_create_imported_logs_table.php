<?php

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
        Schema::create('imported_logs', function (Blueprint $table) {
            $table->id();

            $table->string('file_name');
            $table->enum('status', ['pending', 'processing', 'successful', 'failed'])
                  ->default('pending');

            $table->foreignIdFor(User::class, 'imported_by')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_logs');
    }
};
