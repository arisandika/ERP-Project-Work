<?php

namespace App\Traits;

trait GeneratesDocumentNumber
{
    private static function getRomanMonth(): string
    {
        $month = (int) now()->format('n');
        return match ($month) {
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            default => 'I',
        };
    }

    public static function generateDocNumber(string $prefix, string $columnName): string
    {
        $year = now()->format('Y');
        $romanMonth = self::getRomanMonth();
        $formatPrefix = "{$prefix}/NEX/{$romanMonth}/{$year}/";

        $query = static::query();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            $query = $query->withTrashed();
        }

        $lastRecord = $query->where($columnName, 'like', $formatPrefix . '%')
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRecord ? ((int) substr($lastRecord->{$columnName}, -3)) + 1 : 1;

        return $formatPrefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
