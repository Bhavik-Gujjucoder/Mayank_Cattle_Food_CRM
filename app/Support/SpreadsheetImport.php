<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class HeadingSheetImport implements ToCollection, WithHeadingRow
{
    public Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}

class SpreadsheetImport
{
    /**
     * First sheet only. Empty rows are dropped. Excel row numbers start at 2.
     *
     * @return Collection<int, array{row: int, data: array<string, mixed>}>
     */
    public static function rows(UploadedFile $file): Collection
    {
        $import = new HeadingSheetImport();
        Excel::import($import, $file);

        $parsed = collect();
        foreach ($import->rows as $index => $row) {
            $values = $row instanceof Collection ? $row->all() : (array) $row;
            $data = [];
            foreach ($values as $key => $value) {
                if ($key === null || $key === '') {
                    continue;
                }
                $data[(string) $key] = $value;
            }
            if (self::isEmpty($data)) {
                continue;
            }
            $parsed->push([
                'row'  => (int) $index + 2,
                'data' => $data,
            ]);
        }

        return $parsed;
    }

    public static function text(array $data, string $key): string
    {
        $value = $data[$key] ?? '';
        if (is_float($value) || is_int($value)) {
            if (is_float($value) && floor($value) == $value) {
                return (string) (int) $value;
            }

            return trim((string) $value);
        }

        return trim((string) $value);
    }

    public static function date(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->toDateString();
        }

        if (is_numeric($value) && (float) $value > 20000) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat('!' . $format, $text);
            } catch (\Throwable) {
                continue;
            }
            if ($parsed && $parsed->format($format) === $text) {
                return $parsed->toDateString();
            }
        }

        return null;
    }

    public static function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', trim($value));
        }
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    public static function wholeNumber(mixed $value): ?int
    {
        $number = self::number($value);
        if ($number === null || abs($number - (int) $number) > 0.000001) {
            return null;
        }

        return (int) $number;
    }

    /** @param  array<string, mixed>  $data */
    private static function isEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
