<?php

namespace App\Support;

final class CsvSafe
{
    public static function cell(mixed $value): string
    {
        $value = strip_tags((string) ($value ?? ''));

        return preg_match('/^[=+\-@\t\r]/u', $value) ? "'".$value : $value;
    }

    public static function row(iterable $values): array
    {
        return collect($values)->map(fn ($value) => self::cell($value))->all();
    }
}
