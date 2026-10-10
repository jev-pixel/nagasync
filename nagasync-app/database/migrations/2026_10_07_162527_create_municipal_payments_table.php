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
Schema::create('municipal_payments', function (Blueprint $table) {
    $table->id();
    $table->string('reference_no')->unique();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
    $table->string('payment_type');
    $table->unsignedBigInteger('amount');          // centavos
    $table->unsignedInteger('convenience_fee');    // centavos
    $table->unsignedBigInteger('total');           // centavos
    $table->string('currency', 3)->default('PHP');
    $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'refunded'])->default('pending')->index();
    $table->string('gateway')->default('sandbox');
    $table->string('gateway_ref')->nullable()->index();
    $table->timestamp('paid_at')->nullable();
    $table->json('meta')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipal_payments');
    }
};
