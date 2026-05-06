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
        Schema::table('subject_requisites', function (Blueprint $table) {
            $table->index('subject_id', 'subject_requisites_subject_id_fk_index');
            $table->index('requisite_subject_id', 'subject_requisites_requisite_subject_id_fk_index');
            $table->dropUnique(['subject_id', 'requisite_subject_id', 'type']);
            $table->foreignId('course_id')->nullable()->after('type')->constrained()->nullOnDelete();
            $table->foreignId('major_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
            $table->unique(
                ['subject_id', 'requisite_subject_id', 'type', 'course_id', 'major_id'],
                'subject_requisites_scope_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_requisites', function (Blueprint $table) {
            $table->dropUnique('subject_requisites_scope_unique');
            $table->dropConstrainedForeignId('major_id');
            $table->dropConstrainedForeignId('course_id');
            $table->dropIndex('subject_requisites_subject_id_fk_index');
            $table->dropIndex('subject_requisites_requisite_subject_id_fk_index');
            $table->unique(['subject_id', 'requisite_subject_id', 'type']);
        });
    }
};
