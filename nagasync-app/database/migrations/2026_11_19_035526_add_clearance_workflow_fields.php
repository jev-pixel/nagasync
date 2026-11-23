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
    Schema::table('document_requests', function (Blueprint $t) {
        $t->string('purpose_key', 30)->nullable()->after('purpose')->index();
        $t->json('personal_info')->nullable();
        $t->string('photo_path')->nullable();
        $t->string('signatory_name')->nullable();
        $t->foreignId('payment_id')->nullable()->constrained('municipal_payments')->nullOnDelete();
    });
    Schema::table('document_requirements', function (Blueprint $t) {
        $t->json('purpose_keys')->nullable(); // null = applies to every purpose
    });
    Schema::table('barangays', function (Blueprint $t) {
        $t->string('captain_name')->nullable();
        $t->string('captain_signature_path')->nullable(); // private disk
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangays', fn (Blueprint $t) => $t->dropColumn(['captain_name', 'captain_signature_path']));
        Schema::table('document_requirements', fn (Blueprint $t) => $t->dropColumn('purpose_keys'));
        Schema::table('document_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('payment_id');
            $t->dropIndex(['purpose_key']);
            $t->dropColumn(['purpose_key', 'personal_info', 'photo_path', 'signatory_name']);
        });
    }
};
