<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StudentAdviserBinding extends Model
{
    /** @use HasFactory<\Database\Factories\StudentAdviserBindingFactory> */
    use HasFactory;

    protected $fillable = [
        'student_profile_id',
        'adviser_assignment_id',
        'assigned_by_user_id',
    ];

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function adviserAssignment(): BelongsTo
    {
        return $this->belongsTo(AdviserAssignment::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
