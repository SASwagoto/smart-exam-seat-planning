<?php

namespace App\Filament\Resources\ExamSchedules\Pages;

use App\Filament\Resources\ExamSchedules\ExamScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExamSchedules extends ListRecords
{
    protected static string $resource = ExamScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
