<?php

namespace App\Filament\Resources\ExamSchedules\Schemas;

use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamSlot;
use App\Models\Room;
use App\Models\SectionCourseAssignment;
use App\Models\SectionCourseAssignmentItem;
use App\Models\StudentCourseEnrollment;
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

                // 👈 বাম পাশে: মূল ইনপুট ফর্ম (7 Columns)
                FormSection::make('Exam Routine Setup')
                    ->columnSpan(['default' => 12, 'lg' => 7])
                    ->schema([

                        // ১. সিলেক্ট ডিপার্টমেন্ট ও সিলেক্ট এক্সাম (একই লাইনে)
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
                                    $set('holidays', []);
                                }),

                            Select::make('exam_id')
                                ->label('2. Select Exam')
                                ->options(function (Get $get) {
                                    $deptId = $get('department_id');
                                    if (! $deptId) {
                                        return [];
                                    }

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

                        // ২. ছুটির দিন নির্বাচন (এক্সামের ডেট রেঞ্জ থেকে)
                        Select::make('holidays')
                            ->label('3. Select Holidays / Off-Days')
                            ->multiple()
                            ->options(function (Get $get) {
                                $examId = $get('exam_id');
                                if (! $examId) {
                                    return [];
                                }

                                $exam = Exam::find($examId);
                                if (! $exam || ! $exam->start_date || ! $exam->end_date) {
                                    return [];
                                }

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

                        // ৩. এক্সাম স্লট
                        CheckboxList::make('exam_slot_ids')
                            ->label('4. Select Daily Exam Slots / Shifts')
                            ->options(ExamSlot::pluck('name', 'id'))
                            ->columns(4)
                            ->required()
                            ->live(),

                        // ৪. রুম সিলেক্ট
                        Select::make('room_ids')
                            ->label('5. Select Available Exam Rooms')
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

                // 👉 ডান পাশে: Smart Live Tracker (5 Columns)
                FormSection::make('📊 Auto-Detected Session & Capacity Tracker')
                    ->columnSpan(['default' => 12, 'lg' => 5])
                    ->schema([

                        // 🎯 অটো-ডিটেক্টেড একাডেমিক সেশন, এনরোল্ড স্টুডেন্ট ও কোর্স সামারি
                        Placeholder::make('session_auto_summary')
                            ->label('Academic Session Breakdown')
                            ->content(function (Get $get) {
                                $examId = $get('exam_id');
                                if (! $examId) {
                                    return '⚠️ Select an Exam to auto-detect Enrolled Batches, Sections & Courses.';
                                }

                                $exam = Exam::with(['academicSession', 'department'])->find($examId);
                                if (! $exam) {
                                    return 'Exam not found.';
                                }

                                $sessionId = $exam->academic_session_id;
                                $deptId = $exam->department_id;

                                // ১. সেকশন কোর্স অ্যাসাইনমেন্ট থেকে ওই সেশন ও ডিপার্টমেন্টের অ্যাসাইনমেন্ট বের করা
                                $assignments = SectionCourseAssignment::where('academic_session_id', $sessionId)
                                    ->where('department_id', $deptId)
                                    ->get();

                                // 🔥 ইউনিক ব্যাচ এবং সেকশনের সংখ্যা হিসাব
                                $totalBatches = $assignments->pluck('batch_id')->unique()->filter()->count();
                                $totalSections = $assignments->pluck('section_id')->unique()->filter()->count();

                                // কোর্স এবং অ্যাসাইনমেন্ট আইটেমের সংখ্যা
                                $assignmentIds = $assignments->pluck('id');
                                $totalAssignedCourses = SectionCourseAssignmentItem::whereIn('section_course_assignment_id', $assignmentIds)->count();

                                // ২. এনরোল্ড স্টুডেন্ট কাউন্ট
                                $totalEnrolledStudents = StudentCourseEnrollment::where('academic_session_id', $sessionId)
                                    ->whereHas('student', fn ($q) => $q->where('department_id', $deptId))
                                    ->count();

                                $sessionName = $exam->academicSession?->name ?? 'Current Session';

                                return "🎓 Session: {$sessionName}\n".
                                       "🏢 Enrolled Scope: {$totalBatches} Batches  |  {$totalSections} Sections\n".
                                       "👨‍🎓 Total Students: {$totalEnrolledStudents}\n".
                                       "📚 Total Course Exams: {$totalAssignedCourses} Papers";
                            }),

                        Placeholder::make('schedule_days_summary')
                            ->label('Duration & Available Slots')
                            ->content(function (Get $get) {
                                $examId = $get('exam_id');
                                $slotIds = (array) $get('exam_slot_ids');
                                $holidays = (array) $get('holidays');

                                if (! $examId) {
                                    return 'Select exam first.';
                                }

                                $exam = Exam::find($examId);
                                if (! $exam || ! $exam->start_date || ! $exam->end_date) {
                                    return 'Exam dates missing.';
                                }

                                $startDate = Carbon::parse($exam->start_date);
                                $endDate = Carbon::parse($exam->end_date);
                                $totalDays = $startDate->diffInDays($endDate) + 1;

                                $holidayCount = count($holidays);
                                $activeDays = max(0, $totalDays - $holidayCount);
                                $totalAvailableSlots = $activeDays * count($slotIds);

                                return '🗓️ Duration: '.$startDate->format('d M').' - '.$endDate->format('d M, Y')."\n".
                                       "📅 Days: {$activeDays} Active Days ({$totalDays} Total - {$holidayCount} Off)\n".
                                       "⏰ Available Slots: {$totalAvailableSlots} Total Slots";
                            }),

                        Placeholder::make('room_capacity_summary')
                            ->label('Room Capacity Breakdown')
                            ->content(function (Get $get) {
                                $roomIds = (array) $get('room_ids');
                                if (empty($roomIds)) {
                                    return 'Select rooms to see capacity.';
                                }

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
                                $examId = $get('exam_id');
                                $roomIds = (array) $get('room_ids');
                                $slotIds = (array) $get('exam_slot_ids');
                                $holidays = (array) $get('holidays');

                                if (! $examId || empty($roomIds) || empty($slotIds)) {
                                    return 'Pending Exam, Slots, and Room selection...';
                                }

                                $exam = Exam::find($examId);
                                $sessionId = $exam->academic_session_id;
                                $deptId = $exam->department_id;

                                // স্টুডেন্ট ও কোর্স কোয়েরি
                                $totalEnrolledStudents = StudentCourseEnrollment::where('academic_session_id', $sessionId)
                                    ->whereHas('student', fn ($q) => $q->where('department_id', $deptId))
                                    ->count();

                                $assignmentIds = SectionCourseAssignment::where('academic_session_id', $sessionId)
                                    ->where('department_id', $deptId)
                                    ->pluck('id');

                                $totalExamsToSchedule = SectionCourseAssignmentItem::whereIn('section_course_assignment_id', $assignmentIds)->count();

                                $capacityPerSlot = Room::whereIn('id', $roomIds)->get()->sum('effective_capacity');

                                $totalDays = Carbon::parse($exam->start_date)->diffInDays(Carbon::parse($exam->end_date)) + 1;
                                $activeDays = max(0, $totalDays - count($holidays));
                                $totalAvailableSlots = $activeDays * count($slotIds);

                                if ($capacityPerSlot < $totalEnrolledStudents) {
                                    return "⚠️ WARNING! Room capacity per slot ({$capacityPerSlot}) is less than total enrolled students ({$totalEnrolledStudents}). Add more rooms!";
                                }

                                if ($totalAvailableSlots < $totalExamsToSchedule) {
                                    return "⚠️ WARNING! You have {$totalExamsToSchedule} course exams to schedule, but only {$totalAvailableSlots} available slots. Increase daily slots or reduce holidays!";
                                }

                                return "✅ FEASIBLE! Capacity ({$capacityPerSlot}/slot) and Slots ({$totalAvailableSlots}) can accommodate all {$totalEnrolledStudents} students across {$totalExamsToSchedule} exams.";
                            }),
                    ]),

            ]);
    }
}
