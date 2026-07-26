<?php

namespace App\Filament\Imports;

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\TeacherCourseAssignment;
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

            // 🔥 স্যাম্পল ডাউনলোড ও ম্যাপিংয়ে অপশনাল কলাম হিসেবে দেখানোর জন্য
            ImportColumn::make('academic_session')
                ->label('Academic Session Name (e.g. Spring 2026)')
                ->rules(['nullable', 'string']),

            ImportColumn::make('department')
                ->label('Department Code/Name (e.g. CSE)')
                ->rules(['nullable', 'string']),

            ImportColumn::make('course_codes')
                ->label('Course Codes (e.g. CSE101,CSE102)')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): Teacher
    {
        // এখানে আমরা শুধু Teacher টেবিলের কলামগুলোই সেভ করব
        return Teacher::updateOrCreate(
            [
                'teacher_id' => trim($this->data['teacher_id']),
            ],
            [
                'name' => trim($this->data['name']),
                'email' => trim($this->data['email']),
                'phone' => $this->data['phone'] ?: null,
                'designation' => $this->data['designation'] ?: null,
                'status' => $this->data['status'] ?: 'Active',
            ]
        );
    }

    protected function afterSave(): void
    {
        $teacher = $this->getRecord();

        $sessionName = trim($this->data['academic_session'] ?? '');
        $departmentInput = trim($this->data['department'] ?? '');
        $courseCodesRaw = trim($this->data['course_codes'] ?? '');

        if ($sessionName === '' || $departmentInput === '' || $courseCodesRaw === '') {
            return;
        }

        // Case-insensitive Academic Session
        $session = AcademicSession::whereRaw('LOWER(name) = ?', [
            strtolower($sessionName),
        ])->first();

        // Case-insensitive Department (Code অথবা Name)
        $department = Department::where(function ($query) use ($departmentInput) {
            $query->whereRaw('LOWER(code) = ?', [strtolower($departmentInput)])
                ->orWhereRaw('LOWER(name) = ?', [strtolower($departmentInput)]);
        })->first();

        if (! $session || ! $department) {
            return;
        }

        // Course Codes পরিষ্কার করা
        $courseCodes = collect(explode(',', $courseCodesRaw))
            ->map(fn ($code) => strtoupper(trim($code)))
            ->filter()
            ->values()
            ->all();

        // Case-insensitive Course Code
        $courseIds = Course::where('department_id', $department->id)
            ->where(function ($query) use ($courseCodes) {
                foreach ($courseCodes as $code) {
                    $query->orWhereRaw('LOWER(course_code) = ?', [
                        strtolower($code),
                    ]);
                }
            })
            ->pluck('id');

        foreach ($courseIds as $courseId) {
            TeacherCourseAssignment::firstOrCreate([
                'academic_session_id' => $session->id,
                'department_id' => $department->id,
                'teacher_id' => $teacher->id,
                'course_id' => $courseId,
            ]);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your teacher import has completed and '
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
