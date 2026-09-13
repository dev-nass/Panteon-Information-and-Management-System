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
            $table->timestamp('terminated_at')->nullable()->after('updated_at')->index();
            $table->enum('terminated_reason', ['resigned', 'retired', 'terminated', 'end_of_contract', 'transferred', 'other'])->nullable()->after('terminated_at');
            $table->foreignIdFor(User::class, 'terminated_by')->nullable()->constrained('users')->nullOnDelete()->after('terminated_reason');
            $table->text('terminated_notes')->nullable()->after('terminated_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['terminated_by']);
            $table->dropIndex(['terminated_at']);
            $table->dropColumn(['terminated_at', 'terminated_reason', 'terminated_by', 'terminated_notes']);
        });
    }
};
