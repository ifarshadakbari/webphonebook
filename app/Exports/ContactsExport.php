<?php

namespace App\Exports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContactsExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithStyles
{
    protected $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

    public function query()
    {
        $query = Contact::query()->with('phones');

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        return $query;
    }

    public function map($contact): array
    {
        $phonesList = $contact->phones->map(function($phone) {
            return $phone->phone_number . ($phone->internal_number ? ' (' . $phone->internal_number . ')' : '');
        })->implode(' - ');

        return [
            $contact->social_title,
            $contact->first_name,
            $contact->last_name,
            $contact->mobile,
            $phonesList,
        ];
    }

    public function headings(): array
    {
        return [
            'عنوان اجتماعی',
            'نام',
            'نام خانوادگی',
            'تلفن همراه',
            'تلفن‌های ثابت (داخلی)',
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
