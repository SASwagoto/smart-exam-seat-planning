<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Imports\DepartmentImporter;
use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(DepartmentImporter::class)
                ->label('Import CSV')
                ->color('success')
                ->modalHeading('Import Departments via CSV'),
                
            CreateAction::make()
            ->modalWidth('md')
            ->label('New Department'),
        ];
    }
}
