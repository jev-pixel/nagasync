<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A document keeps the signature that was on file when it was approved (like signatory_name),
 * so a new captain's signature never appears on a certificate the old captain signed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', fn (Blueprint $t) => $t->string('signatory_signature_path')->nullable()->after('signatory_name'));
    }

    public function down(): void
    {
        Schema::table('document_requests', fn (Blueprint $t) => $t->dropColumn('signatory_signature_path'));
    }
};
