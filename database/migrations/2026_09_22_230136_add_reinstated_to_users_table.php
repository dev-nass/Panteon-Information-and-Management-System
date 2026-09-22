<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('reinstated_at')->nullable()->after('terminated_notes')->index();
            $table->enum('reinstated_reason', ['rehired', 'contract_renewed', 'error_correction', 'appeal_approved', 'other'])->nullable()->after('reinstated_at');
            $table->foreignIdFor(User::class, 'reinstated_by')->nullable()->constrained('users')->nullOnDelete()->after('reinstated_reason');
            $table->text('reinstated_notes')->nullable()->after('reinstated_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['reinstated_by']);
            $table->dropIndex(['reinstated_at']);
            $table->dropColumn(['reinstated_at', 'reinstated_reason', 'reinstated_by', 'reinstated_notes']);
        });
    }
};
