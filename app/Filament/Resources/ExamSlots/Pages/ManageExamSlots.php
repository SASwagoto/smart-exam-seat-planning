<?php

namespace App\Filament\Resources\ExamSlots\Pages;

use App\Filament\Resources\ExamSlots\ExamSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageExamSlots extends ManageRecords
{
    protected static string $resource = ExamSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
