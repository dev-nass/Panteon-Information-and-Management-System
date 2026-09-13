<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE activity_logs MODIFY COLUMN action ENUM('created','updated','deleted','role_changed','imported','generated','archived','restored') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE activity_logs MODIFY COLUMN action ENUM('created','updated','deleted','role_changed','imported','generated') NOT NULL");
    }
};
