<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE activity_logs MODIFY COLUMN action ENUM('created','updated','deleted','role_changed','imported','generated','archived','restored','terminated','reinstated') NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE activity_logs MODIFY COLUMN action ENUM('created','updated','deleted','role_changed','imported','generated','archived','restored') NOT NULL");
    }
};
