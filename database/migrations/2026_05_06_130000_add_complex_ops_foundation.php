<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('exception_code', 50)->nullable()->after('status');
            $table->text('exception_notes')->nullable()->after('exception_code');
            $table->timestamp('last_exception_at')->nullable()->after('exception_notes');
        });

        Schema::table('shipment_trackings', function (Blueprint $table) {
            $table->string('checkpoint_type', 50)->nullable()->after('description');
            $table->foreignId('branch_id')->nullable()->after('shipment_id')->constrained('branches')->nullOnDelete();
        });

        DB::statement("
            ALTER TABLE shipments
            MODIFY COLUMN status ENUM(
                'pending',
                'picked_up',
                'in_transit',
                'arrived_at_branch',
                'out_for_delivery',
                'delivered',
                'failed_delivery',
                'exception_hold',
                'returned_to_sender',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");

        DB::statement("
            ALTER TABLE shipment_trackings
            MODIFY COLUMN status ENUM(
                'picked_up',
                'in_transit',
                'arrived_at_branch',
                'out_for_delivery',
                'delivered',
                'failed_delivery',
                'exception_hold',
                'returned_to_sender'
            ) NOT NULL
        ");

        Schema::create('shipment_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('manifest_number')->unique();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('manifest_type', ['pickup', 'linehaul', 'arrival', 'delivery']);
            $table->enum('status', ['draft', 'in_progress', 'closed'])->default('draft');
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('manifest_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manifest_id')->constrained('shipment_manifests')->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->timestamp('loaded_at')->nullable();
            $table->timestamp('unloaded_at')->nullable();
            $table->string('checkpoint_status', 50)->nullable();
            $table->text('checkpoint_notes')->nullable();
            $table->timestamps();

            $table->unique(['manifest_id', 'shipment_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event', 80);
            $table->string('summary');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('manifest_shipments');
        Schema::dropIfExists('shipment_manifests');

        DB::statement("
            ALTER TABLE shipment_trackings
            MODIFY COLUMN status ENUM(
                'picked_up',
                'in_transit',
                'arrived_at_branch',
                'out_for_delivery',
                'delivered'
            ) NOT NULL
        ");

        DB::statement("
            ALTER TABLE shipments
            MODIFY COLUMN status ENUM(
                'pending',
                'picked_up',
                'in_transit',
                'arrived_at_branch',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");

        Schema::table('shipment_trackings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('checkpoint_type');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['exception_code', 'exception_notes', 'last_exception_at']);
        });
    }
};
