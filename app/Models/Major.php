<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    /** @use HasFactory<\Database\Factories\MajorFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'description',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function prospectuses(): HasMany
    {
        return $this->hasMany(Prospectus::class);
    }

    public function evaluationTemplates(): HasMany
    {
        return $this->hasMany(EvaluationTemplate::class);
    }

    public function adviserAssignments(): HasMany
    {
        return $this->hasMany(AdviserAssignment::class);
    }

    public function studentAdmissions(): HasMany
    {
        return $this->hasMany(StudentAdmission::class);
    }
}
