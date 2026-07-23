<?php

namespace App\Filament\Resources\SectionCourseAssignments\Pages;

use App\Filament\Resources\SectionCourseAssignments\SectionCourseAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSectionCourseAssignment extends EditRecord
{
    protected static string $resource = SectionCourseAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
