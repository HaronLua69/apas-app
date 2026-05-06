<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StudentSubjectEnrollment extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<StudentSubjectEnrollment>> */
    use HasFactory;

    protected $fillable = [
        'student_profile_id',
        'subject_id',
        'academic_term_id',
        'school_year',
        'year_level',
        'term_name',
        'attempt_number',
        'status',
        'grade',
        'submitted_grade',
        'submitted_at',
        'confirmed_at',
        'completion_grade',
        'completion_submitted_at',
        'completion_confirmed_at',
        'status_resolved_at',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'attempt_number' => 'integer',
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completion_submitted_at' => 'datetime',
            'completion_confirmed_at' => 'datetime',
            'status_resolved_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
