<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class EvaluationTemplate extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<EvaluationTemplate>> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'major_id',
        'title',
        'description',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function classifications(): HasMany
    {
        return $this->hasMany(EvaluationTemplateClassification::class)
            ->orderBy('display_order');
    }
}
