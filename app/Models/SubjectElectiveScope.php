<?php

namespace App\Models;

use App\Enums\ElectiveCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SubjectElectiveScope extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<SubjectElectiveScope>> */
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'course_id',
        'major_id',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'major_id' => 'integer',
            'category' => ElectiveCategory::class,
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function categoryLabel(): string
    {
        return $this->category?->label() ?? 'Elective';
    }
}
