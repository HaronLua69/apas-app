<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SubjectRequisite extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'requisite_subject_id',
        'type',
        'course_id',
        'major_id',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'major_id' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function requisiteSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'requisite_subject_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function appliesTo(?int $courseId, ?int $majorId): bool
    {
        if ($this->major_id !== null) {
            return $this->major_id === $majorId;
        }

        if ($this->course_id !== null) {
            return $this->course_id === $courseId;
        }

        return true;
    }

    public function scopeLabel(): string
    {
        if ($this->major !== null) {
            return $this->course?->name !== null
                ? $this->course->name.' - Major in '.$this->major->name
                : 'Major in '.$this->major->name;
        }

        if ($this->course !== null) {
            return $this->course->name;
        }

        return 'All programs and majors';
    }
}