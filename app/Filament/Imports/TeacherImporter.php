<?php

namespace App\Filament\Imports;

use App\Models\Teacher;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class TeacherImporter extends Importer
{
    protected static ?string $model = Teacher::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('teacher_id')
                ->requiredMapping()
                ->rules(['required', 'max:50']),

            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),

            ImportColumn::make('phone')
                ->rules(['nullable', 'max:20']),

            ImportColumn::make('designation')
                ->rules(['nullable', 'max:100']),

            ImportColumn::make('status')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): Teacher
    {
        return Teacher::updateOrCreate(
            [
                'teacher_id' => trim($this->data['teacher_id']),
            ],
            [
                'name'        => trim($this->data['name']),
                'email'       => trim($this->data['email']),
                'phone'       => $this->data['phone'] ?: null,
                'designation' => $this->data['designation'] ?: null,
                'status'      => $this->data['status'] ?: 'Active',
            ]
        );
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your teacher import has completed and '
            . Number::format($import->successful_rows)
            . ' '
            . str('row')->plural($import->successful_rows)
            . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '
                . Number::format($failedRowsCount)
                . ' '
                . str('row')->plural($failedRowsCount)
                . ' failed to import.';
        }

        return $body;
    }
}
