<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Services\Exam\ExamService;
use App\Services\Exam\SeatPlanService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    protected static bool $persistsDataInSession = true;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $exam = app(ExamService::class)->create($data);
        // app(SeatPlanService::class)->generate($exam);
        return $exam;
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getFormActionsAlignment(): string
    {
        return 'start';
    }
}
