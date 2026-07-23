<?php

namespace App\Filament\Resources\SectionCourseAssignments\Pages;

use App\Filament\Resources\SectionCourseAssignments\SectionCourseAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSectionCourseAssignments extends ListRecords
{
    protected static string $resource = SectionCourseAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
