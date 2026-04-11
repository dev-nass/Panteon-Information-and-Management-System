<?php

use App\Models\Applicant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deceased_records', function (Blueprint $table) {
            $table->id(); // bigint, auto-increment

            $table->foreignIdFor(Applicant::class)->nullable()->constrained()->nullOnDelete();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            $table->unsignedBigInteger('age')->nullable();

            $table->date('date_of_birth')->nullable();
            $table->date('date_of_death')->nullable();

            $table->string('cause_of_death')->nullable();
            $table->string('place_of_death')->nullable();

            $table->string('civil_status')->nullable();
            $table->string('religion')->nullable();
            $table->string('nationality')->nullable();

            $table->string('address')->nullable();
            $table->string('occupation')->nullable();

            $table->enum('corpse_disposal', [
                'burial',
                'cremation',
                'other',
            ])->nullable();

            $table->string('cremation_place')->nullable();
            $table->date('cremation_date')->nullable();

            $table->date('date_of_depository')->nullable()->comment('burial date');

            $table->string('company_address')->nullable();
            $table->string('company_supervisor_name')->nullable();

            $table->string('father_name')->nullable();
            $table->string('mother_maiden_name')->nullable();

            $table->string('burial_place')->nullable();

            $table->enum('part_of_LGBTQ', [
                'yes',
                'no',
                'prefer_not_to_say',
            ])->nullable();

            $table->unsignedBigInteger('precinct_num')->nullable();

            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deceased_records');
    }
};
