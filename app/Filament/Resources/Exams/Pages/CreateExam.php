<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;
    protected static bool $persistsDataInSession = true;

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getFormActionsAlignment(): string
    {
        return 'start';
    }
}
