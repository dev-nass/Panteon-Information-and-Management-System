<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('burial_records', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('updated_at')->index();
            $table->enum('archived_reason', ['pull_out', 'transfer', 'expired', 'other'])->nullable()->after('archived_at');
            $table->foreignIdFor(User::class, 'archived_by')->nullable()->constrained()->nullOnDelete()->after('archived_reason');
            $table->text('archived_notes')->nullable()->after('archived_by');
        });
    }

    public function down(): void
    {
        Schema::table('burial_records', function (Blueprint $table) {
            $table->dropForeign(['archived_by']);
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['archived_at', 'archived_reason', 'archived_by', 'archived_notes']);
        });
    }
};
