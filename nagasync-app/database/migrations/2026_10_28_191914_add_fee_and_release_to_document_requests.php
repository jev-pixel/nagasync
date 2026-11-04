<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->decimal('fee_total', 10, 2)->nullable();
            $table->json('fee_breakdown')->nullable();
            $table->timestamp('fee_computed_at')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('official_receipt_no', 50)->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // If `status` is an ENUM, add 'released' while keeping every existing value.
        $col = DB::selectOne("SHOW COLUMNS FROM document_requests WHERE Field = 'status'");
        if ($col && str_starts_with(strtolower($col->Type), 'enum(')) {
            preg_match_all("/'([^']+)'/", $col->Type, $m);
            $values = array_values(array_unique([...$m[1], 'released']));
            $list = "'" . implode("','", $values) . "'";
            DB::statement("ALTER TABLE document_requests MODIFY status ENUM($list) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('released_by');
            $table->dropColumn(['fee_total', 'fee_breakdown', 'fee_computed_at', 'amount_paid', 'official_receipt_no', 'released_at']);
        });
    }
};