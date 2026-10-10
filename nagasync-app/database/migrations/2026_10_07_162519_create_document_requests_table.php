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
Schema::create('document_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
    $table->string('document_type');
    $table->string('purpose')->nullable();
    $table->string('tracking_number')->unique();
    $table->enum('status', ['pending', 'processing', 'approved', 'rejected', 'revoked'])->default('pending');
    $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('issued_at')->nullable();
    $table->text('remarks')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
