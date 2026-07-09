<?php

namespace App\Filament\Imports;

use App\Models\Course;
use App\Models\Department;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class CourseImporter extends Importer
{
    protected static ?string $model = Course::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('department')
                ->requiredMapping()
                ->relationship(resolveUsing: function (string $state): ?Department {
                    return Department::where('code', '=', $state)
                        ->orWhere('name', 'like', "%{$state}%")
                        ->first();
                })
                ->rules(['required']),

            ImportColumn::make('course_code')
                ->requiredMapping()
                ->rules(['required', 'max:50']),


            ImportColumn::make('course_title')
                ->requiredMapping()
                ->rules(['required', 'max:255']),


            ImportColumn::make('credit')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),

            ImportColumn::make('type')
                ->requiredMapping()
                ->rules(['required', 'in:Theory,Lab']),
        ];
    }

    public function resolveRecord(): Course
    {
        return Course::firstOrNew([
            'course_code' => $this->data['course_code'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your course import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
