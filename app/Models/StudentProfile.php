<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    /** @use HasFactory<\Database\Factories\StudentProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sex_at_birth',
        'year_level',
        'home_address',
        'is_graduating',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'is_graduating' => 'boolean',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(StudentAdmission::class);
    }

    public function activeAdmission(): HasOne
    {
        return $this->hasOne(StudentAdmission::class)->where('is_active', true);
    }

    public function subjectEnrollments(): HasMany
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }

    public function adviserBinding(): HasOne
    {
        return $this->hasOne(StudentAdviserBinding::class);
    }

    public function boundAdviser(): ?User
    {
        return $this->adviserBinding?->adviserAssignment?->adviserProfile?->user;
    }
}
