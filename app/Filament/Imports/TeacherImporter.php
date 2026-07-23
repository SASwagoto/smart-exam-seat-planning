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
                'name'        => trim($this->data['name']),
                'email'       => trim($this->data['email']),
                'phone'       => $this->data['phone'] ?: null,
                'designation' => $this->data['designation'] ?: null,
                'status'      => $this->data['status'] ?: 'Active',
            ]
        );
    }

    protected function afterSave(): void
    {
        $teacher = $this->getRecord();

        $sessionName     = isset($this->data['academic_session']) ? trim($this->data['academic_session']) : null;
        $departmentInput = isset($this->data['department']) ? trim($this->data['department']) : null;
        $courseCodesRaw  = isset($this->data['course_codes']) ? trim($this->data['course_codes']) : null;

        // যদি কোর্স বা সেশনের কোনো তথ্য না দেওয়া থাকে, তবে স্কিপ করবে
        if (! $sessionName || ! $departmentInput || ! $courseCodesRaw) {
            return;
        }

        // ১. সেশনের নাম দিয়ে AcademicSession-এর ID বের করা
        $session = AcademicSession::where('name', $sessionName)->first();

        // ২. ডিপার্টমেন্টের কোড বা নাম দিয়ে Department-এর ID বের করা
        $department = Department::where('code', $departmentInput)
            ->orWhere('name', $departmentInput)
            ->first();

        // যদি সেশন এবং ডিপার্টমেন্ট ডাটাবেজে সঠিক পাওয়া যায়
        if ($session && $department) {
            // কমা দিয়ে স্প্লিট করে কোর্স কোডগুলো আলাদা করা
            $courseCodes = array_map('trim', explode(',', $courseCodesRaw));

            // কোর্স আইডিগুলো বের করে আনা
            $courseIds = Course::whereIn('course_code', $courseCodes)
                ->where('department_id', $department->id)
                ->pluck('id');

            foreach ($courseIds as $courseId) {
                TeacherCourseAssignment::firstOrCreate([
                    'academic_session_id' => $session->id,
                    'department_id'       => $department->id,
                    'teacher_id'          => $teacher->id,
                    'course_id'           => $courseId,
                ]);
            }
        }
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