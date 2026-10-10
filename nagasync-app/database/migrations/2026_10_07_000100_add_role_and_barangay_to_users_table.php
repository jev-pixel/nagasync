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
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('citizen')->index(); // citizen, barangay_staff, treasury, mdrrmo, admin
        $table->string('phone')->nullable();
        $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropConstrainedForeignId('barangay_id');
        $table->dropColumn(['role', 'phone']);
    });
}
};
