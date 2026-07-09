<?php

namespace App\Filament\Resources\TeacherCourseAssignments\Pages;

use App\Filament\Resources\TeacherCourseAssignments\TeacherCourseAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTeacherCourseAssignments extends ManageRecords
{
    protected static string $resource = TeacherCourseAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
