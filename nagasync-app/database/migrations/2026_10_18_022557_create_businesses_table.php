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
    Schema::create('businesses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
        $table->string('name', 120);
        $table->string('slug')->unique();
        $table->string('category', 30)->index();
        $table->string('tagline', 140)->nullable();
        $table->text('description')->nullable();
        $table->string('address');
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->string('phone', 20)->nullable();
        $table->string('email', 120)->nullable();
        $table->string('website')->nullable();
        $table->string('facebook_url')->nullable();
        $table->string('hours')->nullable();
        $table->unsignedTinyInteger('price_range')->nullable();
        $table->json('tags')->nullable();
        $table->string('permit_no', 60)->nullable();   // private: staff and the owner only
        $table->string('cover_path')->nullable();
        $table->string('status', 20)->default('pending');
        $table->text('review_note')->nullable();
        $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('reviewed_at')->nullable();
        $table->boolean('is_featured')->default(false);
        $table->timestamps();

        $table->index(['status', 'category']);
        $table->index(['status', 'is_featured']);
    });
}

public function down(): void
{
    Schema::dropIfExists('businesses');
}
};
