<?php

namespace App\Filament\Resources\ExamSchedules\Pages;

use App\Filament\Resources\ExamSchedules\ExamScheduleResource;
use App\Models\ExamSchedule;
use App\Services\ExamSchedulerService;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExamSchedule extends CreateRecord
{
    protected static string $resource = ExamScheduleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // ১. সার্ভিস ক্লাসের অবজেক্ট তৈরি করে ডাটা পাস করা হলো
            $scheduler = new ExamSchedulerService($data);
            
            // ২. সার্ভিস মেথড এক্সিকিউট করা
            $scheduler->handle();

            Notification::make()
                ->title('🎉 Exam Schedule & Seating Plan Generated Successfully!')
                ->success()
                ->send();

            // ৩. Filament একটি Eloquent Model আশা করে, তাই মডেল রিটার্ন করা হচ্ছে
            return new ExamSchedule();

        } catch (Exception $e) {
            // সার্ভিস ক্লাসে কোনো সমস্যা হলে তা হ্যান্ডেল করা
            Notification::make()
                ->title('Generation Error')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            // ফর্মের সাবমিশন আটকে দেওয়া
            $this->halt();
        }
    }
}
