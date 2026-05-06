<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Prospectus extends Model
{
    /** @use HasFactory<\Database\Factories\ProspectusFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'major_id',
        'title',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(ProspectusTerm::class)
            ->orderBy('year_level')
            ->orderBy('display_order');
    }
}
