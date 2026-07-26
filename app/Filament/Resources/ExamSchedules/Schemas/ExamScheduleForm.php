<?php

namespace App\Filament\Resources\ExamSchedules\Schemas;

use App\Models\Batch;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamSlot;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionCourseAssignment;
use App\Models\Student;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class ExamScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                FormSection::make('Exam Routine Setup')
                    ->columnSpan(['default' => 12, 'lg' => 7])
                    ->schema([

                        
                        Grid::make(2)->schema([
                            Select::make('department_id')
                                ->label('1. Select Department')
                                ->options(Department::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($set) {
                                    $set('exam_id', null);
                                    $set('batch_ids', []);
                                    $set('section_ids', []);
                                    $set('holidays', []);
                                }),

                            Select::make('exam_id')
                                ->label('2. Select Exam')
                                ->options(function (Get $get) {
                                    $deptId = $get('department_id');
                                    if (! $deptId) return [];

                                    return Exam::where('department_id', $deptId)->pluck('name', 'id');
                                })
                                ->disabled(fn (Get $get) => ! $get('department_id'))
                                ->placeholder(fn (Get $get) => $get('department_id') ? 'Select an Exam' : '⚠️ First Select Department')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('holidays', [])),
                        ]),

                        
                        Select::make('holidays')
                            ->label('3. Select Holidays / Off-Days')
                            ->multiple()
                            ->options(function (Get $get) {
                                $examId = $get('exam_id');
                                if (! $examId) return [];

                                $exam = Exam::find($examId);
                                if (! $exam || ! $exam->start_date || ! $exam->end_date) return [];

                                $period = CarbonPeriod::create($exam->start_date, $exam->end_date);
                                $dates = [];
                                foreach ($period as $date) {
                                    $dates[$date->format('Y-m-d')] = $date->format('d M, Y (l)');
                                }

                                return $dates;
                            })
                            ->searchable()
                            ->placeholder('Select dates to exclude')
                            ->live(),

                    
                        CheckboxList::make('exam_slot_ids')
                            ->label('4. Select Daily Exam Slots / Shifts')
                            ->options(ExamSlot::pluck('name', 'id'))
                            ->columns(4)
                            ->required()
                            ->live(),

                        
                        Grid::make(2)->schema([
                            Select::make('batch_ids')
                                ->label('5. Select Batches')
                                ->multiple()
                                ->options(function (Get $get) {
                                    $deptId = $get('department_id');
                                    if (! $deptId) return Batch::pluck('batch_number', 'id');

                                    return Batch::where('department_id', $deptId)->pluck('batch_number', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('section_ids', [])),

                            Select::make('section_ids')
                                ->label('6. Select Sections')
                                ->multiple()
                                ->options(function (Get $get) {
                                    $batchIds = (array) $get('batch_ids');
                                    $deptId = $get('department_id');

                                    if (! empty($batchIds)) {
                                        return Section::whereIn('batch_id', $batchIds)->pluck('section_name', 'id');
                                    }

                                    if ($deptId) {
                                        return Section::whereHas('batch', fn ($q) => $q->where('department_id', $deptId))->pluck('section_name', 'id');
                                    }

                                    return Section::pluck('section_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(),
                        ]),

                        
                        Select::make('room_ids')
                            ->label('7. Select Exam Rooms')
                            ->multiple()
                            ->options(function () {
                                return Room::where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn ($room) => [
                                        $room->id => "{$room->room_number} (Cap: {$room->effective_capacity})",
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                    ]),

                
                FormSection::make('📊 Live Analytics & Capacity Tracker')
                    ->columnSpan(['default' => 12, 'lg' => 5])
                    ->schema([

                        Placeholder::make('student_course_summary')
                            ->label('Examinees & Courses')
                            ->content(function (Get $get) {
                                $sectionIds = (array) $get('section_ids');
                                if (empty($sectionIds)) return 'Select sections to calculate.';

                                $totalStudents = Student::whereIn('section_id', $sectionIds)->count();
                                $totalCourses = SectionCourseAssignment::whereIn('section_id', $sectionIds)->count();

                                return "👨‍🎓 {$totalStudents} Students  |  📚 {$totalCourses} Total Courses";
                            }),

                        
                        Placeholder::make('schedule_days_summary')
                            ->label('Duration, Active Days & Slots')
                            ->content(function (Get $get) {
                                $examId = $get('exam_id');
                                $slotIds = (array) $get('exam_slot_ids');
                                $holidays = (array) $get('holidays');

                                if (! $examId) return 'Select exam first to see schedule calculations.';

                                $exam = Exam::find($examId);
                                if (! $exam || ! $exam->start_date || ! $exam->end_date) return 'Exam dates missing.';

                                $startDate = Carbon::parse($exam->start_date);
                                $endDate = Carbon::parse($exam->end_date);
                                $totalDays = $startDate->diffInDays($endDate) + 1;

                                $holidayCount = count($holidays);
                                $activeDays = max(0, $totalDays - $holidayCount);
                                $totalAvailableSlots = $activeDays * count($slotIds);

                                return "🗓️ Range: " . $startDate->format('d M') . " - " . $endDate->format('d M, Y') . "\n" .
                                       "📅 Days: {$activeDays} Active Days ({$totalDays} Total - {$holidayCount} Off)\n" .
                                       "⏰ Total Slots: {$totalAvailableSlots} Available Slots";
                            }),

                        Placeholder::make('room_capacity_summary')
                            ->label('Room Capacity Breakdown')
                            ->content(function (Get $get) {
                                $roomIds = (array) $get('room_ids');
                                if (empty($roomIds)) return 'Select rooms to see capacity.';

                                $capacityPerSlot = Room::whereIn('id', $roomIds)->get()->sum('effective_capacity');
                                $examId = $get('exam_id');
                                $slotIds = (array) $get('exam_slot_ids');
                                $holidays = (array) $get('holidays');

                                $totalAvailableSlots = 0;
                                if ($examId && ! empty($slotIds)) {
                                    $exam = Exam::find($examId);
                                    if ($exam && $exam->start_date && $exam->end_date) {
                                        $totalDays = Carbon::parse($exam->start_date)->diffInDays(Carbon::parse($exam->end_date)) + 1;
                                        $activeDays = max(0, $totalDays - count($holidays));
                                        $totalAvailableSlots = $activeDays * count($slotIds);
                                    }
                                }

                                $totalOverallCapacity = $capacityPerSlot * $totalAvailableSlots;

                                return "🪑 {$capacityPerSlot} Seats / Slot\n🚀 {$totalOverallCapacity} Total Overall Capacity";
                            }),

                        Placeholder::make('feasibility_status')
                            ->label('Feasibility Status')
                            ->content(function (Get $get) {
                                $sectionIds = (array) $get('section_ids');
                                $roomIds = (array) $get('room_ids');

                                if (empty($sectionIds) || empty($roomIds)) {
                                    return 'Pending inputs...';
                                }

                                $totalStudents = Student::whereIn('section_id', $sectionIds)->count();
                                $capacityPerSlot = Room::whereIn('id', $roomIds)->get()->sum('effective_capacity');

                                if ($capacityPerSlot == 0) return 'No room capacity available.';

                                if ($capacityPerSlot >= $totalStudents) {
                                    return "✅ FEASIBLE! Single slot capacity ({$capacityPerSlot}) is enough for {$totalStudents} students.";
                                }

                                return "⚠️ WARNING! Capacity per slot ({$capacityPerSlot}) is less than total students ({$totalStudents}). Distribute exams across multiple slots!";
                            }),
                    ]),

            ]);
    }
}