<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\StyledExportHeading;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArraySheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    use StyledExportHeading {
        styles as headingStyles;
    }

    /**
     * @param  list<string>  $headings
     * @param  list<list<string|int|float>>  $rows
     * @param  array<string, string>  $comments  Heading text => comment shown on that column
     */
    public function __construct(
        private string $title,
        private array $headings,
        private array $rows,
        private array $comments = [],
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        foreach ($this->comments as $heading => $text) {
            $index = array_search($heading, $this->headings, true);
            if ($index === false) {
                continue;
            }

            $cell = Coordinate::stringFromColumnIndex($index + 1) . '1';
            $comment = $sheet->getComment($cell);
            $comment->setAuthor('Sample');
            $comment->getText()->createTextRun($text);
            $comment->setWidth('280px');
            $comment->setHeight('90px');
        }

        return $this->headingStyles($sheet);
    }
}
