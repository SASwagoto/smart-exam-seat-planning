<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Imports\CourseImporter;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCourses extends ManageRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(CourseImporter::class)
                ->label('Import CSV')
                ->color('success')
                ->modalHeading('Import Courses via CSV'),

            CreateAction::make(),
        ];
    }
}
