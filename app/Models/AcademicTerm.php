<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcademicTerm extends Model
{
    /** @use HasFactory<\Database\Factories\AcademicTermFactory> */
    use HasFactory;

    public const TERM_OPTIONS = [
        '1st Semester',
        '2nd Semester',
        'Summer Term',
    ];

    protected $fillable = [
        'academic_year_start',
        'term_name',
        'date_from',
        'date_to',
    ];

    protected function casts(): array
    {
        return [
            'academic_year_start' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
        ];
    }

    public function academicYearEnd(): int
    {
        return $this->academic_year_start + 1;
    }

    public function academicYearLabel(): string
    {
        return 'A.Y. '.$this->academic_year_start.'-'.$this->academicYearEnd();
    }

    public function fullLabel(): string
    {
        return $this->term_name.', '.$this->academicYearLabel();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('academic_year_start')
            ->orderByRaw("case term_name when '1st Semester' then 1 when '2nd Semester' then 2 when 'Summer Term' then 3 else 99 end");
    }

    public function scopeActiveAt(Builder $query, CarbonInterface $moment): Builder
    {
        return $query
            ->whereDate('date_from', '<=', $moment->toDateString())
            ->whereDate('date_to', '>=', $moment->toDateString());
    }

    public static function active(?CarbonInterface $moment = null): ?self
    {
        return static::query()
            ->ordered()
            ->activeAt($moment ?? now())
            ->first();
    }
}
