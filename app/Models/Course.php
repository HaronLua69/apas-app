<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'abbreviation',
        'description',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
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
