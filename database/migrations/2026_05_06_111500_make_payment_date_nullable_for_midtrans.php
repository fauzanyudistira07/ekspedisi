<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE payments MODIFY COLUMN payment_date DATE NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE payments SET payment_date = COALESCE(payment_date, DATE(created_at), CURDATE()) WHERE payment_date IS NULL");
        DB::statement('ALTER TABLE payments MODIFY COLUMN payment_date DATE NOT NULL');
    }
};
