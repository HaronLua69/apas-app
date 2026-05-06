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
            $table->string('category')->nullable()->after('name');
        });

        Schema::table('subject_elective_scopes', function (Blueprint $table) {
            $table->string('category')->nullable()->after('major_id');
        });

        DB::table('prospectus_term_electives')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function ($elective): void {
                $category = match (true) {
                    str_contains(strtolower($elective->name), 'technical elective') => 'technical-elective',
                    str_contains(strtolower($elective->name), 'cognate') => 'cognate-course',
                    str_contains(strtolower($elective->name), 'language') => 'language-elective',
                    str_contains(strtolower($elective->name), 'free elective') => 'free-elective',
                    default => 'free-elective',
                };

                DB::table('prospectus_term_electives')
                    ->where('id', $elective->id)
                    ->update(['category' => $category]);
            });

        DB::table('subject_elective_scopes')->whereNull('category')->update([
            'category' => 'technical-elective',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_elective_scopes', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('prospectus_term_electives', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
