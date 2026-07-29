<?php

namespace App\Filament\Widgets;

use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Students', Student::count())
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            // দ্বিতীয় কার্ড: মোট পরীক্ষার রুম
            Stat::make('Total Teachers', Teacher::count() ?? 0)
                ->descriptionIcon('heroicon-m-users'),

            // তৃতীয় কার্ড: মোট সিট সংখ্যা
            Stat::make('Total Rooms', Room::count() ?? 0)
                ->descriptionIcon('heroicon-m-home')
                ->color('warning'),

            Stat::make('Total Seats', Room::sum('total_seats') ?? 0)
                ->descriptionIcon('heroicon-m-home')
                ->color('warning'),
        ];
    }
}
