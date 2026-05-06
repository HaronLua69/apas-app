<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Subject extends Model
{
    /** @use HasFactory<\Database\Factories\SubjectFactory> */
    use HasFactory;

    protected $fillable = [
        'department_id',
        'subject_code',
        'subject_title',
        'subject_types',
        'credit_units',
        'grading_system',
        'description',
        'counts_toward_gpa',
    ];

    protected function casts(): array
    {
        return [
            'subject_types' => 'array',
            'credit_units' => 'decimal:2',
            'counts_toward_gpa' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requisites(): HasMany
    {
        return $this->hasMany(SubjectRequisite::class);
    }

    public function requiredBy(): HasMany
    {
        return $this->hasMany(SubjectRequisite::class, 'requisite_subject_id');
    }

    public function prospectusTerms(): BelongsToMany
    {
        return $this->belongsToMany(ProspectusTerm::class, 'prospectus_term_subject')
            ->withPivot('display_order')
            ->withTimestamps();
    }

    public function evaluationTemplateClassifications(): BelongsToMany
    {
        return $this->belongsToMany(
            EvaluationTemplateClassification::class,
            'evaluation_template_classification_subject'
        )
            ->withPivot('display_order')
            ->withTimestamps();
    }

    public function studentSubjectEnrollments(): HasMany
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }

    public function electiveScopes(): HasMany
    {
        return $this->hasMany(SubjectElectiveScope::class);
    }

    /**
     * @return Collection<int, SubjectRequisite>
     */
    public function applicableRequisites(?int $courseId = null, ?int $majorId = null, ?string $type = null): Collection
    {
        return $this->requisites
            ->when($type !== null, fn (Collection $requisites) => $requisites->where('type', $type))
            ->filter(fn (SubjectRequisite $requisite) => $requisite->appliesTo($courseId, $majorId))
            ->values();
    }
}
