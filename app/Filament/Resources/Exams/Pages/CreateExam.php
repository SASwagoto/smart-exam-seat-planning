<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;
    protected static bool $persistsDataInSession = true;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 🎯 ফর্মের সম্পূর্ণ ডেটা ব্রাউজারে ফরম্যাট করে থামিয়ে দেখাবে
        dd($data);

        return $data;
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
