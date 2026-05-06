<?php

namespace App\Models;

use App\Enums\ElectiveCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ProspectusTermElective extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<ProspectusTermElective>> */
    use HasFactory;

    protected $fillable = [
        'prospectus_term_id',
        'name',
        'category',
        'units',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'category' => ElectiveCategory::class,
            'units' => 'decimal:2',
        ];
    }

    public function prospectusTerm(): BelongsTo
    {
        return $this->belongsTo(ProspectusTerm::class);
    }

    public function categoryLabel(): string
    {
        return $this->category?->label() ?? 'Elective';
    }
}
