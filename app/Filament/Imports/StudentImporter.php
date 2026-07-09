<?php

namespace App\Filament\Imports;

use App\Models\Batch;
use App\Models\Department;
use App\Models\Section;
use App\Models\Student;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class StudentImporter extends Importer
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('department')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('batch')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('section')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('student_id')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->rules(['nullable', 'email']),
            ImportColumn::make('phone')
                ->rules(['nullable', 'max:20']),
        ];
    }

    public function resolveRecord(): Student
    {

        $department = Department::query()
            ->where(function ($query) {
                $value = trim($this->data['department']);
                $query->where('code', $value)
                      ->orWhere('name', $value);
            })
            ->first();

        if (! $department) {
            throw new \Exception(
                "Department '{$this->data['department']}' not found."
            );
        }
       
        $batch = Batch::firstOrCreate(
            [
                'department_id' => $department->id,
                'batch_number' => trim($this->data['batch']),
            ]
        );
        $section = Section::firstOrCreate(
            [
                'batch_id' => $batch->id,
                'section_name' => strtoupper(trim($this->data['section'])),
            ],
            [
                'capacity' => 50,
            ]
        );

        
        return Student::updateOrCreate(
            [
                'student_id' => trim($this->data['student_id']),
            ],
            [
                'department_id' => $department->id,
                'batch_id' => $batch->id,
                'section_id' => $section->id,
                'name' => trim($this->data['name']),
                'email' => $this->data['email'] ?: null,
                'phone' => $this->data['phone'] ?: null,
                'status' => 'Active',
            ]
        );
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your student import has completed and '
        .Number::format($import->successful_rows)
        .' '
        .str('row')->plural($import->successful_rows)
        .' imported.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '
            .Number::format($failedRowsCount)
            .' '
            .str('row')->plural($failedRowsCount)
            .' failed to import.';
        }

        return $body;
    }
}
