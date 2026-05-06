<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prospectus_term_electives', function (Blueprint $table) {
            $table->decimal('units', 5, 2)->default(3)->after('name');
        });

        DB::table('prospectus_term_electives')->update([
            'units' => 3,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospectus_term_electives', function (Blueprint $table) {
            $table->dropColumn('units');
        });
    }
};
