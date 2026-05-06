<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class EvaluationTemplateClassification extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<EvaluationTemplateClassification>> */
    use HasFactory;

    protected $fillable = [
        'evaluation_template_id',
        'name',
        'description',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    public function evaluationTemplate(): BelongsTo
    {
        return $this->belongsTo(EvaluationTemplate::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'evaluation_template_classification_subject'
        )
            ->withPivot('display_order')
            ->withTimestamps()
            ->orderByPivot('display_order');
    }
}
