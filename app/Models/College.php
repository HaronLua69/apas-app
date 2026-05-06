<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class College extends Model
{
    /** @use HasFactory<\Database\Factories\CollegeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'dean',
        'description',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
