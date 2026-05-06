<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'college_id',
        'name',
        'abbreviation',
        'chairperson',
        'description',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function adviserProfiles(): HasMany
    {
        return $this->hasMany(AdviserProfile::class);
    }
}
