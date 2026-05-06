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
        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            $table->foreignId('academic_term_id')->nullable()->after('subject_id')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('attempt_number')->default(1)->after('term_name');
            $table->string('status')->default('confirmed')->after('attempt_number');
            $table->string('submitted_grade')->nullable()->after('grade');
            $table->timestamp('submitted_at')->nullable()->after('submitted_grade');
            $table->timestamp('confirmed_at')->nullable()->after('submitted_at');
            $table->string('completion_grade')->nullable()->after('confirmed_at');
            $table->timestamp('completion_submitted_at')->nullable()->after('completion_grade');
            $table->timestamp('completion_confirmed_at')->nullable()->after('completion_submitted_at');
            $table->timestamp('status_resolved_at')->nullable()->after('completion_confirmed_at');

            $table->index(['student_profile_id', 'subject_id', 'attempt_number'], 'student_subject_enrollments_attempt_lookup');
            $table->index(['academic_term_id', 'status'], 'student_subject_enrollments_term_status');
        });

        $attemptNumbers = [];

        DB::table('student_subject_enrollments')
            ->orderBy('student_profile_id')
            ->orderBy('subject_id')
            ->orderBy('school_year')
            ->orderBy('year_level')
            ->orderByRaw("case term_name when '1st Semester' then 1 when '2nd Semester' then 2 when 'Summer Term' then 3 else 99 end")
            ->orderBy('id')
            ->get()
            ->each(function (object $enrollment) use (&$attemptNumbers): void {
                $attemptKey = $enrollment->student_profile_id.'|'.$enrollment->subject_id;
                $attemptNumbers[$attemptKey] = ($attemptNumbers[$attemptKey] ?? 0) + 1;
                $hasRecordedGrade = filled($enrollment->grade);

                DB::table('student_subject_enrollments')
                    ->where('id', $enrollment->id)
                    ->update([
                        'attempt_number' => $attemptNumbers[$attemptKey],
                        'status' => $hasRecordedGrade ? 'confirmed' : 'in_progress',
                        'confirmed_at' => $hasRecordedGrade ? ($enrollment->updated_at ?? $enrollment->created_at ?? now()) : null,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            $table->dropIndex('student_subject_enrollments_term_status');
            $table->dropIndex('student_subject_enrollments_attempt_lookup');
            $table->dropConstrainedForeignId('academic_term_id');
            $table->dropColumn([
                'attempt_number',
                'status',
                'submitted_grade',
                'submitted_at',
                'confirmed_at',
                'completion_grade',
                'completion_submitted_at',
                'completion_confirmed_at',
                'status_resolved_at',
            ]);
        });
    }
};
