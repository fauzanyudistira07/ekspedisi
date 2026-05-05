<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();

            $table->foreignId('sender_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('customers')->cascadeOnDelete();

            $table->foreignId('origin_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('courier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rate_id')->constrained('rates')->cascadeOnDelete();

            $table->decimal('total_weight', 10, 2);
            $table->decimal('total_price', 15, 2);

            $table->enum('status', [
                'pending',
                'picked_up',
                'in_transit',
                'arrived_at_branch',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ])->default('pending');

            $table->date('shipment_date');
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};