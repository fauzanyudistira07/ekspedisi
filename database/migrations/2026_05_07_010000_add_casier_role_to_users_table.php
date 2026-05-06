<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','cashier','casier','courier','manager') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'cashier' WHERE role = 'casier'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','cashier','courier','manager') NOT NULL");
    }
};
