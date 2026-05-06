<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ProspectusTerm extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<ProspectusTerm>> */
    use HasFactory;

    protected $fillable = [
        'prospectus_id',
        'year_level',
        'term_name',
        'display_order',
        'total_units',
    ];

    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'display_order' => 'integer',
            'total_units' => 'decimal:2',
        ];
    }

    public function prospectus(): BelongsTo
    {
        return $this->belongsTo(Prospectus::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'prospectus_term_subject')
            ->withPivot('display_order')
            ->withTimestamps()
            ->orderByPivot('display_order');
    }

    public function electives(): HasMany
    {
        return $this->hasMany(ProspectusTermElective::class)
            ->orderBy('display_order');
    }
}
