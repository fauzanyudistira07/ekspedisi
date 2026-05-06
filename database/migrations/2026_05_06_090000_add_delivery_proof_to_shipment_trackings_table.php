<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_trackings', function (Blueprint $table) {
            $table->string('received_by', 100)->nullable()->after('description');
            $table->string('receiver_relation', 50)->nullable()->after('received_by');
            $table->string('proof_photo')->nullable()->after('receiver_relation');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_trackings', function (Blueprint $table) {
            $table->dropColumn([
                'received_by',
                'receiver_relation',
                'proof_photo',
            ]);
        });
    }
};
