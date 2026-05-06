<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class AdviserProfile extends Model
{
    /** @use HasFactory<\Database\Factories\AdviserProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'sex_at_birth',
        'rank',
        'home_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function adviserAssignments(): HasMany
    {
        return $this->hasMany(AdviserAssignment::class);
    }
}
