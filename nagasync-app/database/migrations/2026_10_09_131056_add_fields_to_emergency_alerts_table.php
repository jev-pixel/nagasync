<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The table exists from the original guide migration with a different shape.
        // No alert data exists yet, so rebuild it, but refuse if that stops being true.
        if (Schema::hasTable('emergency_alerts') && DB::table('emergency_alerts')->exists()) {
            throw new RuntimeException(
                'emergency_alerts contains rows. Migrate them manually instead of dropping the table.'
            );
        }

        Schema::dropIfExists('emergency_alerts');

        Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
            $table->string('alert_type')->default('other');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('accuracy_m')->nullable();
            $table->string('note', 255)->nullable();
            $table->string('status')->default('active')->index(); // active, dispatched, resolved, false_alarm
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_alerts');
    }
};