<?php

namespace App\Filament\Resources\StudentEnrollments\Pages;

use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentCourse;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentEnrollments extends ManageRecords
{
    protected static string $resource = StudentEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()

                ->using(function (array $data) {

                    // পরে এখানে Logic লিখবো
                    dd($data);
                    // return new StudentEnrollment();
                })

                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Enrollment completed.')
                ),
        ];
    }
}