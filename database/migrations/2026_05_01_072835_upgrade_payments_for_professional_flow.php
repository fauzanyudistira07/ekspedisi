<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference_number', 120)->nullable()->after('payment_method');
            $table->string('proof_file')->nullable()->after('reference_number');
            $table->timestamp('verified_at')->nullable()->after('payment_date');
            $table->timestamp('expired_at')->nullable()->after('verified_at');
            $table->foreignId('verified_by')->nullable()->after('expired_at')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('verified_by');
        });

        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_status ENUM('pending','waiting_verification','paid','failed','expired','refunded') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending'");

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'reference_number',
                'proof_file',
                'verified_at',
                'expired_at',
                'notes',
            ]);
        });
    }
};
