<?php

namespace App\Filament\Resources\SectionCourseAssignments\Pages;

use App\Filament\Resources\SectionCourseAssignments\SectionCourseAssignmentResource;
use App\Models\SectionCourseAssignment;
use App\Models\Student;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentEnrollmentCourse;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateSectionCourseAssignment extends CreateRecord
{
    protected static string $resource = SectionCourseAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $assignment = $this->record;

        $sectionId = $assignment->section_id;
        $academicSessionId = $assignment->academic_session_id;

        // ১. ওই সেকশনের সকল স্টুডেন্টদের আইডি বের করা
        $studentIds = Student::where('section_id', $sectionId)->pluck('id');

        if ($studentIds->isEmpty()) {
            return;
        }

        // ২. এই সেকশনে অ্যাসাইন করা কোর্সগুলোর আইডি লিস্ট
        $courseIds = $assignment->items()->pluck('course_id')->filter()->unique();

        if ($courseIds->isEmpty()) {
            return;
        }

        // 3. ডুপ্লিকেট এড়াতে Transaction ব্যবহার করে এনরোলমেন্ট সেভ করা
        DB::transaction(function () use ($studentIds, $courseIds, $academicSessionId) {
            foreach ($studentIds as $studentId) {
                // ক) স্টুডেন্টের মূল এনরোলমেন্ট রেকর্ড তৈরি বা বের করা
                $enrollment = StudentCourseEnrollment::firstOrCreate(
                    [
                        'academic_session_id' => $academicSessionId,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => 'approved', // বা আপনার প্রয়োজনমত 'active' / 'pending'
                    ]
                );

                // খ) এনরোলমেন্টের অধীনে কোর্সগুলো যুক্ত করা
                foreach ($courseIds as $courseId) {
                    StudentEnrollmentCourse::updateOrCreate(
                        [
                            'student_course_enrollment_id' => $enrollment->id,
                            'course_id' => $courseId,
                        ],
                        [
                            'enrollment_type' => 'regular', // বা ডিফল্ট টাইপ
                            'status' => 'enrolled',
                        ]
                    );
                }
            }
        });
    }
}
