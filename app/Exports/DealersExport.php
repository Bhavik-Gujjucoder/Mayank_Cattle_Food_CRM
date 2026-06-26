<?php

namespace App\Exports;

use App\Exports\Concerns\StyledExportHeading;
use App\Models\DealerManagement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class DealersExport implements FromCollection, WithHeadings, WithStyles
{
    use StyledExportHeading;

    public function __construct(
        protected Collection $rows
    ) {}

    public static function fromQuery($query): self
    {
        return new self($query->get());
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn (DealerManagement $row) => [
            $row->code_no,
            $row->user?->name ?? '—',
            $row->user?->phone_no ?? '—',
            $row->user?->email ?? '—',
            $row->firm_shop_name,
            $row->firm_shop_address,
            $row->broker?->name ?? '—',
            $row->brand?->name ?? '—',
            $row->city?->name ?? '—',
            $row->state?->name ?? '—',
            $row->postal_code ?? '—',
            (int) $row->user?->status === 1 ? 'Active' : 'Inactive',
        ]);
    }

    public function headings(): array
    {
        return [
            'Code No',
            'Applicant Name',
            'Mobile',
            'Email',
            'Firm / Shop Name',
            'Address',
            'Broker',
            'Brand',
            'City',
            'State',
            'Postal Code',
            'Status',
        ];
    }
}
