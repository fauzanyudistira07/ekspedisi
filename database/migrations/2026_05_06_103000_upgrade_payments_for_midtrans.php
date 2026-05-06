<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash','transfer','e-wallet','midtrans') NOT NULL");

        DB::table('payments')->update([
            'payment_method' => 'midtrans',
        ]);

        DB::table('payments')
            ->where('payment_status', 'waiting_verification')
            ->update([
                'payment_status' => 'pending',
            ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_provider', 50)->default('midtrans')->after('amount');
            $table->string('gateway_order_id', 100)->nullable()->after('gateway_provider');
            $table->string('gateway_transaction_id', 100)->nullable()->after('gateway_order_id');
            $table->string('payment_channel', 100)->nullable()->after('payment_method');
            $table->string('snap_token')->nullable()->after('payment_channel');
            $table->text('snap_redirect_url')->nullable()->after('snap_token');
            $table->string('midtrans_transaction_status', 50)->nullable()->after('payment_status');
            $table->json('gateway_payload')->nullable()->after('notes');
            $table->timestamp('paid_at')->nullable()->after('payment_date');
        });

        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('midtrans') NOT NULL DEFAULT 'midtrans'");
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_status ENUM('pending','paid','failed','expired','refunded') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash','transfer','e-wallet') NOT NULL");
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_status ENUM('pending','waiting_verification','paid','failed','expired','refunded') NOT NULL DEFAULT 'pending'");

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_provider',
                'gateway_order_id',
                'gateway_transaction_id',
                'payment_channel',
                'snap_token',
                'snap_redirect_url',
                'midtrans_transaction_status',
                'gateway_payload',
                'paid_at',
            ]);
        });
    }
};
