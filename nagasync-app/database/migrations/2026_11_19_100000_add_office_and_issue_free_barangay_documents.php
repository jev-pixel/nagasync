<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Which City Hall office handles each type (mirrors DocumentRequest::OFFICE_FOR)
    private const OFFICE_FOR = [
        'Business Permit (New)'               => 'bplo',
        'Business Permit (Renewal)'           => 'bplo',
        'Community Tax Certificate'           => 'treasurer',
        'Certificate of Good Moral Character' => 'mayor',
        "Mayor's Clearance"                   => 'mayor',
        'City Certificate of Indigency'       => 'cswdo',
        'Medical Assistance Referral'         => 'lingkod',
    ];

    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $t) {
            $t->string('office', 20)->nullable()->after('level')->index();
        });

        // city_staff only. null = may work every office
        Schema::table('users', function (Blueprint $t) {
            $t->string('office', 20)->nullable()->after('role');
        });

        foreach (self::OFFICE_FOR as $type => $office) {
            DB::table('document_requests')->where('document_type', $type)->update(['office' => $office]);
        }

        // Barangay documents had no fee concept: freeze existing ones as free...
        DB::table('document_requests')
            ->where('level', 'barangay')->whereNull('fee_total')
            ->update(['fee_total' => 0, 'fee_computed_at' => now()]);

        // ...and issue the already-approved ones, since "released" is now what makes a document valid.
        DB::table('document_requests')
            ->where('level', 'barangay')->where('status', 'approved')
            ->update(['status' => 'released', 'amount_paid' => 0, 'released_at' => DB::raw('COALESCE(issued_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('office'));

        Schema::table('document_requests', function (Blueprint $t) {
            $t->dropIndex(['office']);
            $t->dropColumn('office');
        });
    }
};
