<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Imports\TeacherImporter;
use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;

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
            CreateAction::make(),
        ];
    }
}
