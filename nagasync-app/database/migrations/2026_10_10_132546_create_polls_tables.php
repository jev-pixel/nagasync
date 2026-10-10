<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('polls', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description')->nullable();
        $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete(); // null = city-wide
        // dateTime, not timestamp: on MariaDB 10.4 a NOT NULL timestamp can silently auto-update
        $table->dateTime('opens_at');
        $table->dateTime('closes_at');
        $table->boolean('is_published')->default(false);
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    });

    Schema::create('poll_options', function (Blueprint $table) {
        $table->id();
        $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
        $table->string('label', 150);
        $table->unsignedTinyInteger('position')->default(0);
    });

    // WHO voted (identity)...
    Schema::create('poll_participations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->timestamp('created_at')->nullable();
        $table->unique(['poll_id', 'user_id']);
    });

    // ...and WHAT was chosen (ballot): deliberately no user_id and no timestamps
    Schema::create('poll_ballots', function (Blueprint $table) {
        $table->id();
        $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
        $table->foreignId('poll_option_id')->constrained('poll_options')->cascadeOnDelete();
    });
}

public function down(): void
{
    Schema::dropIfExists('poll_ballots');
    Schema::dropIfExists('poll_participations');
    Schema::dropIfExists('poll_options');
    Schema::dropIfExists('polls');
}
};
