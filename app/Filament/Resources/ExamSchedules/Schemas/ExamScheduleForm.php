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
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class ExamScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([

                // 👈 বাম পাশে: মূল ইনপুট ফর্ম (7 Columns)
                FormSection::make('⚙️ Exam Routine Setup')
                    ->columnSpan(['default' => 12, 'lg' => 7])
                    ->schema([

                        // ১. ডিপার্টমেন্ট ও এক্সাম সিলেক্ট
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
                                ->placeholder(fn (Get $get) => $get('department_id') ? 'Select an Exam' : '⚠️ Select Department First')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('holidays', [])),
                        ]),

                        // ২. ছুটির দিন নির্বাচন
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

                        // ৪. কোর্স ফিল্টারিং ও রুলস (Grid)
                        Grid::make(2)->schema([
                            Select::make('max_courses_per_slot')
                                ->label('5. Max Courses Per Slot')
                                ->options(function (Get $get) {
                                    $examId = $get('exam_id');

                                    if (! $examId) {
                                        return [
                                            '1' => '1 Course / Slot',
                                            '2' => '2 Courses / Slot',
                                        ];
                                    }

                                    $exam = Exam::find($examId);
                                    if (! $exam) {
                                        return [];
                                    }

                                    $totalBatches = SectionCourseAssignment::where('academic_session_id', $exam->academic_session_id)
                                        ->where('department_id', $exam->department_id)
                                        ->pluck('batch_id')
                                        ->unique()
                                        ->filter()
                                        ->count();

                                    $maxLimit = max(2, $totalBatches);
                                    $options = [];
                                    for ($i = 1; $i <= $maxLimit; $i++) {
                                        $options[(string) $i] = "{$i} ".($i === 1 ? 'Course' : 'Courses').' / Slot'.($i === $totalBatches ? ' (Max Batches)' : '');
                                    }

                                    return $options;
                                })
                                ->default('auto')
                                ->required()
                                ->live(),

                            Checkbox::make('exclude_lab_courses')
                                ->label('Exclude Lab Courses')
                                ->default(true)
                                ->live()
                                ->helperText('টিক দেওয়া থাকলে কেবল থিওরি কোর্স কাউন্ট হবে।'),
                        ]),

                        // ৫. ব্যাক-টু-ব্যাক স্লট রেস্ট্রিকশন ফিল্ড
                        Grid::make(1)->schema([
                            Toggle::make('prevent_back_to_back')
                                ->label('🚫 Prevent Back-to-Back Slot Exams for Same Batch/Student')
                                ->default(true)
                                ->live()
                                ->helperText('চালু থাকলে একই ব্যাচের পরীক্ষা পরপর দুটি স্লটে (যেমন Slot 1 ও Slot 2) দেওয়া যাবে না। মাঝখানে অন্তত ১টি স্লট বা দিনের গ্যাপ থাকবে।'),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('seating_algorithm')
                                ->label('Seating Arrangement Algorithm')
                                ->options([
                                    'zig_zag' => '⚡ Zig-Zag Pattern (Mixes Batch Students)',
                                    'column_skip' => 'Column Skip (A|B|A)',
                                ])
                                ->default('zig_zag')
                                ->required(),
                            Select::make('routine_algorithm')
                            ->label('Routine Generator Strategy')
                            ->options([
                                'backtracking' => 'Backtracking (Strict Conflict Avoidance)',
                                'balanced' => 'Balanced Distribution (Equal Spread)',
                            ])
                            ->default('backtracking')
                            ->required(),
                        ]),
                        Select::make('room_ids')
                                ->label('6. Select Available Exam Rooms')
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

                // 👉 ডান পাশে: Smart Live Tracker Dashboard (5 Columns)
                FormSection::make('📊 Live Schedule & Feasibility Tracker')
                    ->columnSpan(['default' => 12, 'lg' => 5])
                    ->schema([

                        // 🎯 1. Dynamic Key Metrics (Visual Cards)
                        Placeholder::make('live_metrics_summary')
                            ->label('')
                            ->content(function (Get $get) {
                                $examId = $get('exam_id');
                                $roomIds = (array) $get('room_ids');
                                $slotIds = (array) $get('exam_slot_ids');
                                $holidays = (array) $get('holidays');
                                $excludeLab = $get('exclude_lab_courses');
                                $userCourseSelection = $get('max_courses_per_slot') ?? 'auto';

                                if (! $examId) {
                                    return new HtmlString("
                                        <div style='background: #F3F4F6; border-radius: 10px; padding: 15px; text-align: center; color: #6B7280; font-weight: 500;'>
                                            👈 অনুগ্রহ করে বাম পাশে পরীক্ষা ও অন্যান্য তথ্য সিলেক্ট করুন।
                                        </div>
                                    ");
                                }

                                $exam = Exam::find($examId);
                                $sessionId = $exam->academic_session_id;
                                $deptId = $exam->department_id;

                                // Total Students & Batches
                                $totalEnrolledStudents = StudentCourseEnrollment::where('academic_session_id', $sessionId)
                                    ->whereHas('student', fn ($q) => $q->where('department_id', $deptId))
                                    ->distinct('student_id')
                                    ->count('student_id');

                                $totalBatches = SectionCourseAssignment::where('academic_session_id', $sessionId)
                                    ->where('department_id', $deptId)
                                    ->pluck('batch_id')
                                    ->unique()
                                    ->count() ?: 1;

                                $avgStudentsPerCourse = ceil($totalEnrolledStudents / $totalBatches);

                                // Course Count
                                $assignmentIds = SectionCourseAssignment::where('academic_session_id', $sessionId)
                                    ->where('department_id', $deptId)
                                    ->pluck('id');

                                $courseQuery = SectionCourseAssignmentItem::whereIn('section_course_assignment_id', $assignmentIds);
                                if ($excludeLab) {
                                    $courseQuery->whereHas('course', fn ($q) => $q->where('type', '!=', 'lab'));
                                }
                                $totalExamsToSchedule = $courseQuery->distinct('course_id')->count('course_id');

                                // Room & Seats Capacity
                                $selectedRooms = Room::whereIn('id', $roomIds)->get();
                                $capacityPerSlot = $selectedRooms->sum('effective_capacity');
                                $avgRoomCap = $selectedRooms->avg('effective_capacity') ?: 40;

                                // Available Slots
                                $totalDays = Carbon::parse($exam->start_date)->diffInDays(Carbon::parse($exam->end_date)) + 1;
                                $activeDays = max(0, $totalDays - count($holidays));
                                $totalAvailableSlots = $activeDays * count($slotIds);

                                // Courses Per Slot Target
                                $autoDetectedCourses = $avgStudentsPerCourse > 0 ? max(1, floor($capacityPerSlot / $avgStudentsPerCourse)) : 1;
                                $effectiveCoursesPerSlot = ($userCourseSelection === 'auto') ? $autoDetectedCourses : (int) $userCourseSelection;

                                // Calculations (Required Seats & Slots)
                                $totalStudentsInSlot = $effectiveCoursesPerSlot * $avgStudentsPerCourse;
                                $requiredSeatsPerSlot = ($effectiveCoursesPerSlot === 1) ? ($totalStudentsInSlot * 2) : $totalStudentsInSlot;
                                $requiredRoomsPerSlot = ceil($requiredSeatsPerSlot / max(1, $avgRoomCap));
                                $requiredSlotsTotal = ceil($totalExamsToSchedule / max(1, $effectiveCoursesPerSlot));

                                // Badges/Colors
                                $seatStatusColor = ($capacityPerSlot >= $requiredSeatsPerSlot && $requiredSeatsPerSlot > 0) ? '#10B981' : '#EF4444';
                                $slotStatusColor = ($totalAvailableSlots >= $requiredSlotsTotal && $requiredSlotsTotal > 0) ? '#10B981' : '#EF4444';

                                return new HtmlString("
                                    <div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-family: sans-serif;'>
                                        
                                        <!-- Card 1: Seats Breakdown -->
                                        <div style='background: #FFFFFF; border: 1px solid #E5E7EB; border-left: 4px solid {$seatStatusColor}; padding: 12px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);'>
                                            <div style='font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6B7280; letter-spacing: 0.5px;'>🪑 Seat Capacity / Slot</div>
                                            <div style='margin-top: 6px; font-size: 18px; font-weight: 700; color: #111827;'>
                                                <span style='color: {$seatStatusColor};'>{$capacityPerSlot}</span> <span style='font-size: 12px; color: #6B7280; font-weight: normal;'>/ {$requiredSeatsPerSlot} Req.</span>
                                            </div>
                                            <div style='margin-top: 4px; font-size: 11px; color: #4B5563;'>
                                                Need ~<strong>{$requiredRoomsPerSlot} Rooms</strong> (Selected: ".count($roomIds).")
                                            </div>
                                        </div>

                                        <!-- Card 2: Slots Breakdown -->
                                        <div style='background: #FFFFFF; border: 1px solid #E5E7EB; border-left: 4px solid {$slotStatusColor}; padding: 12px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);'>
                                            <div style='font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6B7280; letter-spacing: 0.5px;'>⏰ Exam Slots Needed</div>
                                            <div style='margin-top: 6px; font-size: 18px; font-weight: 700; color: #111827;'>
                                                <span style='color: {$slotStatusColor};'>{$totalAvailableSlots} Avail.</span> <span style='font-size: 12px; color: #6B7280; font-weight: normal;'>/ {$requiredSlotsTotal} Req.</span>
                                            </div>
                                            <div style='margin-top: 4px; font-size: 11px; color: #4B5563;'>
                                                Total <strong>{$totalExamsToSchedule} Papers</strong> @ {$effectiveCoursesPerSlot}/Slot
                                            </div>
                                        </div>

                                    </div>
                                ");
                            }),

                        // 🎯 2. Structured Smart Status Alert Box
                        Placeholder::make('actionable_feasibility_status')
                            ->label('')
                            ->content(function (Get $get) {
                                $examId = $get('exam_id');
                                $roomIds = (array) $get('room_ids');
                                $slotIds = (array) $get('exam_slot_ids');
                                $holidays = (array) $get('holidays');
                                $excludeLab = $get('exclude_lab_courses');
                                $preventBackToBack = $get('prevent_back_to_back');
                                $userCourseSelection = $get('max_courses_per_slot') ?? 'auto';

                                if (! $examId) {
                                    return null;
                                }

                                $exam = Exam::find($examId);
                                $sessionId = $exam->academic_session_id;
                                $deptId = $exam->department_id;

                                // Basic Data Fetching
                                $totalEnrolledStudents = StudentCourseEnrollment::where('academic_session_id', $sessionId)
                                    ->whereHas('student', fn ($q) => $q->where('department_id', $deptId))
                                    ->distinct('student_id')
                                    ->count('student_id');

                                $totalBatches = SectionCourseAssignment::where('academic_session_id', $sessionId)
                                    ->where('department_id', $deptId)
                                    ->pluck('batch_id')
                                    ->unique()
                                    ->count() ?: 1;

                                $avgStudentsPerCourse = ceil($totalEnrolledStudents / $totalBatches);

                                $assignmentIds = SectionCourseAssignment::where('academic_session_id', $sessionId)
                                    ->where('department_id', $deptId)
                                    ->pluck('id');

                                $courseQuery = SectionCourseAssignmentItem::whereIn('section_course_assignment_id', $assignmentIds);
                                if ($excludeLab) {
                                    $courseQuery->whereHas('course', fn ($q) => $q->where('type', '!=', 'lab'));
                                }
                                $totalExamsToSchedule = $courseQuery->distinct('course_id')->count('course_id');

                                $selectedRooms = Room::whereIn('id', $roomIds)->get();
                                $capacityPerSlot = $selectedRooms->sum('effective_capacity');
                                $avgRoomCap = $selectedRooms->avg('effective_capacity') ?: 40;

                                $totalDays = Carbon::parse($exam->start_date)->diffInDays(Carbon::parse($exam->end_date)) + 1;
                                $activeDays = max(0, $totalDays - count($holidays));
                                $totalAvailableSlots = $activeDays * count($slotIds);

                                $autoDetectedCourses = $avgStudentsPerCourse > 0 ? max(1, floor($capacityPerSlot / $avgStudentsPerCourse)) : 1;
                                $effectiveCoursesPerSlot = ($userCourseSelection === 'auto') ? $autoDetectedCourses : (int) $userCourseSelection;

                                $totalStudentsInSlot = $effectiveCoursesPerSlot * $avgStudentsPerCourse;
                                $requiredSeatsPerSlot = ($effectiveCoursesPerSlot === 1) ? ($totalStudentsInSlot * 2) : $totalStudentsInSlot;
                                $requiredSlotsTotal = ceil($totalExamsToSchedule / max(1, $effectiveCoursesPerSlot));

                                // Validations & Actionable Instructions
                                $issues = [];

                                if (empty($slotIds)) {
                                    $issues[] = '👉 <strong>স্লট বাছাই করুন:</strong> অন্তত ১টি Daily Slot/Shift নির্বাচন করুন।';
                                }

                                if (empty($roomIds)) {
                                    $issues[] = '👉 <strong>রুম সিলেক্ট করুন:</strong> বাম পাশ থেকে রুম সিলেক্ট না করলে সিট ক্যালকুলেশন সম্ভব নয়।';
                                } elseif ($capacityPerSlot < $requiredSeatsPerSlot) {
                                    $shortageSeats = $requiredSeatsPerSlot - $capacityPerSlot;
                                    $shortageRooms = ceil($shortageSeats / max(1, $avgRoomCap));
                                    $issues[] = "👉 <strong>রুম বাড়ান:</strong> আপনার সিলেক্ট করা সিটে ঘাটতি রয়েছে! আরও অন্তত <strong>{$shortageSeats} টি সিট</strong> (কমপক্ষে <strong>{$shortageRooms} টি রুম</strong>) যোগ করুন।";
                                }

                                if (! empty($slotIds) && $totalAvailableSlots < $requiredSlotsTotal) {
                                    $shortageSlots = $requiredSlotsTotal - $totalAvailableSlots;
                                    $issues[] = "👉 <strong>স্লট বা দিন বাড়ান:</strong> সকল কোর্স নিতে আরও <strong>{$shortageSlots} টি স্লট</strong> প্রয়োজন। আরও Shift যোগ করুন, ছুটির দিন কমান, অথবা Exam Duration বাড়ান।";
                                }

                                // Back-to-Back Warning Validation
                                if ($preventBackToBack && count($slotIds) > 1 && $activeDays > 0) {
                                    $dailySlotsCount = count($slotIds);
                                    if ($dailySlotsCount == 2 && $requiredSlotsTotal > $activeDays) {
                                        $issues[] = '⚠️ <strong>Back-to-Back সংঘাত ঝুঁকি:</strong> একই দিনে ২ টি স্লট বেছে নিয়েছেন এবং Back-to-Back ব্লক चालू রাখা হয়েছে। একই ব্যাচের পরপর ২ স্লটে পরীক্ষা রোধ করতে <strong>পরীক্ষার দিন সংখ্যা বাড়ান</strong> অথবা <strong>Daily Slots বাড়ান</strong>।';
                                    }
                                }

                                // 🔴 CASE 1: If Any Issues Found
                                if (! empty($issues)) {
                                    $issuesListHtml = '';
                                    foreach ($issues as $issue) {
                                        $issuesListHtml .= "<li style='margin-bottom: 6px;'>{$issue}</li>";
                                    }

                                    return new HtmlString("
                                        <div style='background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 8px; padding: 14px; margin-top: 10px;'>
                                            <div style='display: flex; align-items: center; gap: 8px; color: #991B1B; font-weight: 700; font-size: 14px;'>
                                                <span>⚠️ করণীয় (Action Required)</span>
                                            </div>
                                            <ul style='margin-top: 8px; margin-bottom: 0; padding-left: 18px; color: #7F1D1D; font-size: 13px; line-height: 1.6;'>
                                                {$issuesListHtml}
                                            </ul>
                                        </div>
                                    ");
                                }

                                // 🟢 CASE 2: All Perfectly Feasible
                                $courseScopeLabel = $excludeLab ? 'Theory Only (Labs Excluded)' : 'Theory & Labs Included';
                                $b2bText = $preventBackToBack ? ' [🚫 Back-to-Back Blocked]' : '';

                                return new HtmlString("
                                    <div style='background: #ECFDF5; border: 1px solid #6EE7B7; border-radius: 8px; padding: 14px; margin-top: 10px;'>
                                        <div style='display: flex; align-items: center; gap: 8px; color: #065F46; font-weight: 700; font-size: 14px;'>
                                            <span>✅ সিডিউল সম্পূর্ণ পারফেক্ট! (Ready to Generate)</span>
                                        </div>
                                        <div style='margin-top: 8px; color: #047857; font-size: 12px; line-height: 1.6;'>
                                            • <strong>কোর্স স্কোর:</strong> {$totalExamsToSchedule} Papers ({$courseScopeLabel})<br>
                                            • <strong>নিয়মকানুন:</strong>একই ব্যাচের/সেকশনের ব্যাক-টু-ব্যাক স্লট পরীক্ষা ব্লক করা আছে{$b2bText}।<br>
                                            • <strong>স্লট ব্যবহার:</strong> মোট {$totalAvailableSlots} টি স্লটের মধ্যে <strong>{$requiredSlotsTotal} টি</strong> ব্যবহৃত হবে।
                                        </div>
                                    </div>
                                ");
                            }),

                        // 🎯 3. Detail Session Info Snapshot
                        Placeholder::make('session_info_table')
                            ->label('')
                            ->content(function (Get $get) {
                                $examId = $get('exam_id');
                                $excludeLab = $get('exclude_lab_courses');
                                if (! $examId) {
                                    return null;
                                }

                                $exam = Exam::with(['academicSession', 'department'])->find($examId);
                                if (! $exam) {
                                    return null;
                                }

                                $sessionId = $exam->academic_session_id;
                                $deptId = $exam->department_id;

                                $assignments = SectionCourseAssignment::where('academic_session_id', $sessionId)
                                    ->where('department_id', $deptId)->get();

                                $totalBatches = $assignments->pluck('batch_id')->unique()->filter()->count();
                                $totalSections = $assignments->pluck('section_id')->unique()->filter()->count();

                                $assignmentIds = $assignments->pluck('id');
                                $courseQuery = SectionCourseAssignmentItem::whereIn('section_course_assignment_id', $assignmentIds);
                                if ($excludeLab) {
                                    $courseQuery->whereHas('course', fn ($q) => $q->where('type', '!=', 'lab'));
                                }
                                $totalExamsToSchedule = $courseQuery->distinct('course_id')->count('course_id');

                                $totalEnrolledStudents = StudentCourseEnrollment::where('academic_session_id', $sessionId)
                                    ->whereHas('student', fn ($q) => $q->where('department_id', $deptId))
                                    ->distinct('student_id')
                                    ->count('student_id');

                                $avgStudentsPerCourse = ($totalBatches > 0) ? ceil($totalEnrolledStudents / $totalBatches) : 0;

                                return new HtmlString("
                                    <div style='background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px; font-size: 12px; color: #374151; margin-top: 5px;'>
                                        <div style='font-weight: 700; color: #111827; margin-bottom: 8px; font-size: 12px;'>📌 Quick Info Snapshot</div>
                                        <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 8px;'>
                                            <div style='background: #FFFFFF; border: 1px solid #F3F4F6; padding: 6px 8px; border-radius: 6px;'>🎓 <strong>Session:</strong> {$exam->academicSession?->name}</div>
                                            <div style='background: #FFFFFF; border: 1px solid #F3F4F6; padding: 6px 8px; border-radius: 6px;'>👨‍🎓 <strong>Total Enrolled:</strong> {$totalEnrolledStudents} Students</div>
                                            <div style='background: #FFFFFF; border: 1px solid #F3F4F6; padding: 6px 8px; border-radius: 6px;'>🏢 <strong>Batches:</strong> {$totalBatches} ({$totalSections} Sections)</div>
                                            <div style='background: #FFFFFF; border: 1px solid #F3F4F6; padding: 6px 8px; border-radius: 6px;'>📚 <strong>Exams:</strong> {$totalExamsToSchedule} Papers</div>
                                            
                                            <div style='grid-column: span 2; background: #EEF2FF; border: 1px solid #C7D2FE; padding: 6px 8px; border-radius: 6px; color: #3730A3;'>
                                                📊 <strong>Avg. Students / Course:</strong> ~{$avgStudentsPerCourse} Students / Batch
                                            </div>
                                        </div>
                                    </div>
                                ");
                            }),
                    ]),

            ]);
    }
}