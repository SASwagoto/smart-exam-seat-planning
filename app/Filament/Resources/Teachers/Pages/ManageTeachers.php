<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Imports\TeacherImporter;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\TeacherCourseAssignment;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageTeachers extends ManageRecords
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(TeacherImporter::class)
                ->label('Import ERP CSV')
                ->color('success'),
            CreateAction::make()
                ->using(function (array $data): Model {
                    // ১. কোর্স অ্যাসাইনমেন্টের ফিল্ডগুলো বের করে নেওয়া
                    $academicSessionId = $data['academic_session_id'] ?? null;
                    $departmentId      = $data['department_id'] ?? null;
                    $courseIds         = $data['course_ids'] ?? [];

                    // ২. টিচার রেকর্ড তৈরি
                    $teacher = static::getModel()::create($data);

                    // ৩. কোর্স সিলেক্ট থাকলে সেগুলোকে অ্যাসাইন করে দেওয়া
                    if ($academicSessionId && $departmentId && !empty($courseIds)) {
                        foreach ($courseIds as $courseId) {
                            TeacherCourseAssignment::firstOrCreate([
                                'academic_session_id' => $academicSessionId,
                                'department_id'       => $departmentId,
                                'teacher_id'          => $teacher->id,
                                'course_id'           => $courseId,
                            ]);
                        }
                    }

                    return $teacher;
                }),
        ];
    }
}
