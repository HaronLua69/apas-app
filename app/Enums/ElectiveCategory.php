<?php

namespace App\Enums;

enum ElectiveCategory: string
{
    case TechnicalElective = 'technical-elective';
    case CognateCourse = 'cognate-course';
    case LanguageElective = 'language-elective';
    case FreeElective = 'free-elective';

    public function label(): string
    {
        return match ($this) {
            self::TechnicalElective => 'Technical Elective',
            self::CognateCourse => 'Cognate Course',
            self::LanguageElective => 'Language Elective',
            self::FreeElective => 'Free Elective',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $category) => $category->value, self::cases());
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $category) => [
            'value' => $category->value,
            'label' => $category->label(),
        ], self::cases());
    }

    public static function inferFromName(string $name): self
    {
        $normalized = str($name)->lower()->trim()->toString();

        return match (true) {
            str_contains($normalized, 'technical elective') => self::TechnicalElective,
            str_contains($normalized, 'cognate') => self::CognateCourse,
            str_contains($normalized, 'language') => self::LanguageElective,
            str_contains($normalized, 'free elective') => self::FreeElective,
            default => self::FreeElective,
        };
    }
}