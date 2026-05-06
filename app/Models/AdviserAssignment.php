<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class AdviserAssignment extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<AdviserAssignment>> */
    use HasFactory;

    protected $fillable = [
        'adviser_profile_id',
        'course_id',
        'major_id',
        'year_level',
    ];

    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
        ];
    }

    public function adviserProfile(): BelongsTo
    {
        return $this->belongsTo(AdviserProfile::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function studentAdviserBindings(): HasMany
    {
        return $this->hasMany(StudentAdviserBinding::class);
    }
}
