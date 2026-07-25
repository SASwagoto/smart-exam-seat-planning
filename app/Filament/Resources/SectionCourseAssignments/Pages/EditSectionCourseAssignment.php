<?php

namespace App\Filament\Resources\SectionCourseAssignments\Pages;

use App\Filament\Resources\SectionCourseAssignments\SectionCourseAssignmentResource;
use App\Models\Student;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentEnrollmentCourse;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditSectionCourseAssignment extends EditRecord
{
    protected static string $resource = SectionCourseAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $assignment = $this->record;

        $sectionId = $assignment->section_id;
        $academicSessionId = $assignment->academic_session_id;

        $studentIds = Student::where('section_id', $sectionId)->pluck('id');

        if ($studentIds->isEmpty()) {
            return;
        }

        $courseIds = $assignment->items()->pluck('course_id')->filter()->unique();

        DB::transaction(function () use ($studentIds, $courseIds, $academicSessionId) {
            foreach ($studentIds as $studentId) {
                $enrollment = StudentCourseEnrollment::firstOrCreate(
                    [
                        'academic_session_id' => $academicSessionId,
                        'student_id'          => $studentId,
                    ],
                    [
                        'status' => 'approved',
                    ]
                );

                foreach ($courseIds as $courseId) {
                    StudentEnrollmentCourse::updateOrCreate(
                        [
                            'student_course_enrollment_id' => $enrollment->id,
                            'course_id'                    => $courseId,
                        ],
                        [
                            'enrollment_type' => 'regular',
                            'status'          => 'enrolled',
                        ]
                    );
                }
            }
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
