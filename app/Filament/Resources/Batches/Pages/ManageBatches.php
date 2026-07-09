<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Filament\Imports\BatchImporter;
use App\Filament\Resources\Batches\BatchResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBatches extends ManageRecords
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(BatchImporter::class)
                ->label('Import CSV')
                ->color('success')
                ->modalHeading('Import Batches & Sections via CSV'),

            CreateAction::make(),
        ];
    }
}
