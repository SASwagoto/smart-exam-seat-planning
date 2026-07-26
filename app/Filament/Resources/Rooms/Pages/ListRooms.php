<?php

namespace App\Filament\Resources\Rooms\Pages;

use App\Filament\Imports\RoomImporter;
use App\Filament\Resources\Rooms\RoomResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(RoomImporter::class)
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success'),
            CreateAction::make(),
        ];
    }
}
