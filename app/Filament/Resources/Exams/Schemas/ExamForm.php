<?php

namespace App\Filament\Resources\Exams\Schemas;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Department;
use App\Models\ExamSlot;
use App\Models\Room;
use App\Models\SectionCourseAssignment;
use App\Models\SectionCourseAssignmentItem;
use App\Models\StudentEnrollmentCourse;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12) 
            ->components([
                // 📌 বাম পাশের কলাম (৪ গ্রিড) - Exam Details
                Section::make('Exam Details')
                    ->columnSpan([
                        'default' => 12,
                        'lg' => 4, 
                    ])
                    ->schema([
                        Select::make('department_id')
                            ->label('Department')
                            ->options(Department::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live(),

                        Select::make('academic_session_id')
                            ->label('Academic Session')
                            ->options(AcademicSession::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),

                        TextInput::make('name')
                            ->label('Exam Name')
                            ->placeholder('e.g. Mid Term Spring 2026')
                            ->required(),

                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->minDate(now()) 
                            ->live() 
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $schedules = $get('schedules') ?? [];

                                if (!empty($schedules)) {
                                    $schedules[array_key_first($schedules)]['date'] = $state;
                                } else {
                                    $schedules[] = ['date' => $state, 'slot_details' => []];
                                }

                                $set('schedules', $schedules);
                            })
                            ->required(),
                    ]),

                // 📌 ডান পাশের কলাম (৮ গ্রিড) - Schedules Repeater
                Repeater::make('schedules')
                    ->columnSpan([
                        'default' => 12,
                        'lg' => 8,
                    ])
                    ->hiddenLabel()
                    ->hidden(fn (Get $get): bool => ! $get('start_date'))
                    ->addActionLabel('+ Add Exam Date Row')
                    ->defaultItems(1)
                    ->schema([
                        // 🔹 ১. প্রথম রো: তারিখ ও স্লট চেকবক্স
                        Grid::make(12)
                            ->schema([
                                DatePicker::make('date')
                                    ->hiddenLabel()
                                    ->placeholder('Exam Date')
                                    ->native(false)
                                    ->default(fn (Get $get) => $get('../../start_date'))
                                    ->minDate(fn (Get $get) => $get('../../start_date'))
                                    ->required()
                                    ->columnSpan(4),

                                CheckboxList::make('exam_slots')
                                    ->hiddenLabel()
                                    ->options(ExamSlot::pluck('name', 'id'))
                                    ->columns(4)
                                    ->gridDirection('row')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $selectedSlotIds = $state ?? [];
                                        $currentSlotDetails = $get('slot_details') ?? [];
                                        
                                        $newSlotDetails = [];
                                        foreach ($selectedSlotIds as $slotId) {
                                            $existing = collect($currentSlotDetails)->firstWhere('exam_slot_id', $slotId);
                                            $newSlotDetails[] = $existing ?? [
                                                'exam_slot_id' => $slotId,
                                                'batch_courses' => [],
                                                'room_ids' => []
                                            ];
                                        }
                                        $set('slot_details', $newSlotDetails);
                                    })
                                    ->required()
                                    ->columnSpan(8),
                            ]),

                        // 🔹 ২. স্লটভিত্তিক রো
                        Repeater::make('slot_details')
                            ->hiddenLabel()
                            ->hidden(fn (Get $get): bool => empty($get('exam_slots')))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->extraAttributes([
                                'class' => '[&_.fi-fo-repeater-item-header]:!hidden [&_.fi-fo-repeater-item]:!bg-transparent [&_.fi-fo-repeater-item]:!border-0 [&_.fi-fo-repeater-item]:!shadow-none [&_.fi-fo-repeater-item]:!p-0'
                            ])
                            ->schema([
                                // 🟢 স্লট হেডার: স্পেসিং ও সুন্দর ব্যাজ লেআউট
                                Placeholder::make('slot_title')
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $slotId = $get('exam_slot_id');
                                        $slotName = ExamSlot::find($slotId)?->name ?? 'Exam Slot';

                                        $batchCourses = $get('batch_courses') ?? [];
                                        $sessionId = $get('academic_session_id') 
                                                  ?? $get('../../../../../academic_session_id')
                                                  ?? $get('../../../../../../academic_session_id');

                                        $totalSlotStudents = 0;
                                        $uniqueBatches = [];

                                        foreach ($batchCourses as $item) {
                                            $bId = $item['batch_id'] ?? null;
                                            $cId = $item['course_id'] ?? null;

                                            if ($bId) {
                                                $uniqueBatches[$bId] = true;
                                                if ($cId) {
                                                    $totalSlotStudents += StudentEnrollmentCourse::query()
                                                        ->where('course_id', $cId)
                                                        ->whereHas('studentCourseEnrollment', function ($q) use ($bId, $sessionId) {
                                                            if ($sessionId) {
                                                                $q->where('academic_session_id', $sessionId);
                                                            }
                                                            $q->whereHas('student', fn ($sq) => $sq->where('batch_id', $bId));
                                                        })
                                                        ->count();
                                                } else {
                                                    $batch = Batch::find($bId);
                                                    if ($batch && method_exists($batch, 'students')) {
                                                        $totalSlotStudents += $batch->students()->count();
                                                    }
                                                }
                                            }
                                        }

                                        // লজিক: একক ব্যাচ হলে দ্বিগুণ সিট (২ x স্টুডেন্ট) লাগবে
                                        $batchCount = count($uniqueBatches);
                                        $requiredSeats = ($batchCount === 1) ? ($totalSlotStudents * 2) : $totalSlotStudents;

                                        return new HtmlString("
                                            <div class='flex flex-wrap items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-2 mt-5 mb-3 gap-2'>
                                                <span class='font-bold text-base text-gray-800 dark:text-gray-100'>{$slotName}</span>
                                                <div class='flex items-center gap-2'>
                                                    <span class='text-xs font-semibold bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-300 px-3 py-1 rounded-full border border-blue-200 dark:border-blue-800'>
                                                        Total Std: <b>{$totalSlotStudents}</b>
                                                    </span>
                                                    <span class='text-xs font-semibold bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-300 px-3 py-1 rounded-full border border-amber-200 dark:border-amber-800'>
                                                        Required Seats: <b>{$requiredSeats}</b>
                                                    </span>
                                                </div>
                                            </div>
                                        ");
                                    }),

                                // 🔹 ব্যাচ ও কোর্স রিপিটার
                                Repeater::make('batch_courses')
                                    ->hiddenLabel()
                                    ->addActionLabel('+ Add Batch')
                                    ->reorderable(false)
                                    ->defaultItems(1)
                                    ->extraAttributes([
                                        'class' => '[&_.fi-fo-repeater-item-header]:!hidden [&_.fi-fo-repeater-item]:!bg-transparent [&_.fi-fo-repeater-item]:!border-0 [&_.fi-fo-repeater-item]:!shadow-none [&_.fi-fo-repeater-item]:!p-0 [&_.fi-fo-repeater-item]:!mb-2'
                                    ])
                                    ->schema([
                                        Grid::make(12)
                                            ->schema([
                                                Select::make('batch_id')
                                                    ->hiddenLabel()
                                                    ->placeholder('Select Batch')
                                                    ->options(function (Get $get) {
                                                        $deptId = $get('department_id') 
                                                                ?? $get('../../../../../department_id')
                                                                ?? $get('../../../../../../department_id');

                                                        $sessionId = $get('academic_session_id') 
                                                                  ?? $get('../../../../../academic_session_id')
                                                                  ?? $get('../../../../../../academic_session_id');

                                                        $assignmentQuery = SectionCourseAssignment::query();

                                                        if ($deptId) {
                                                            $assignmentQuery->where('department_id', $deptId);
                                                        }

                                                        if ($sessionId) {
                                                            $assignmentQuery->where('academic_session_id', $sessionId);
                                                        }

                                                        $assignedBatchIds = $assignmentQuery->pluck('batch_id')
                                                            ->unique()
                                                            ->filter()
                                                            ->toArray();

                                                        if (empty($assignedBatchIds)) {
                                                            return [];
                                                        }

                                                        $query = Batch::whereIn('id', $assignedBatchIds);

                                                        $siblingBatchCourses = $get('../') ?? [];
                                                        $currentBatchId = $get('batch_id');

                                                        $usedBatchIds = collect($siblingBatchCourses)
                                                            ->pluck('batch_id')
                                                            ->filter()
                                                            ->reject(fn ($id) => $id == $currentBatchId)
                                                            ->toArray();

                                                        if (!empty($usedBatchIds)) {
                                                            $query->whereNotIn('id', $usedBatchIds);
                                                        }

                                                        return $query->pluck('batch_number', 'id');
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->required()
                                                    ->columnSpan(3),

                                                Select::make('course_id')
                                                    ->hiddenLabel()
                                                    ->placeholder('Select Course')
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->options(function (Get $get) {
                                                        $batchId = $get('batch_id');
                                                        $sessionId = $get('academic_session_id')
                                                                  ?? $get('../../../../../academic_session_id') 
                                                                  ?? $get('../../../../../../academic_session_id');

                                                        if (! $batchId) {
                                                            return [];
                                                        }

                                                        $batch = Batch::find($batchId);
                                                        if (! $batch) return [];

                                                        $query = SectionCourseAssignmentItem::query()
                                                            ->whereHas('sectionCourseAssignment', function ($q) use ($batchId, $sessionId) {
                                                                $q->where('batch_id', $batchId);
                                                                if ($sessionId) {
                                                                    $q->where('academic_session_id', $sessionId);
                                                                }
                                                            });

                                                        $assignedCourseIds = $query->pluck('course_id')->unique()->filter()->toArray();

                                                        $allSchedules = $get('../../../../../../schedules') 
                                                                    ?? $get('../../../../../schedules') 
                                                                    ?? [];

                                                        $usedCourseIds = [];
                                                        foreach ($allSchedules as $schedule) {
                                                            foreach ($schedule['slot_details'] ?? [] as $slotDetail) {
                                                                foreach ($slotDetail['batch_courses'] ?? [] as $bc) {
                                                                    if (($bc['batch_id'] ?? null) == $batchId && !empty($bc['course_id'])) {
                                                                        $usedCourseIds[] = $bc['course_id'];
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        $currentCourseId = $get('course_id');
                                                        $usedCourseIds = array_diff($usedCourseIds, [$currentCourseId]);

                                                        $availableCourseIds = array_diff($assignedCourseIds, $usedCourseIds);

                                                        $courses = Course::whereIn('id', $availableCourseIds)
                                                            ->get()
                                                            ->mapWithKeys(function ($course) {
                                                                $code = $course->course_code ?? $course->code ?? '';
                                                                $title = $course->course_title ?? $course->name ?? '';
                                                                return [$course->id => trim("{$code} - {$title}", ' -')];
                                                            })
                                                            ->toArray();

                                                        return !empty($courses) ? ["Batch {$batch->batch_number}" => $courses] : [];
                                                    })
                                                    ->required()
                                                    ->columnSpan(6),

                                                Placeholder::make('total_students')
                                                    ->hiddenLabel()
                                                    ->content(function (Get $get) {
                                                        $batchId = $get('batch_id');
                                                        $courseId = $get('course_id');
                                                        $sessionId = $get('academic_session_id') 
                                                                  ?? $get('../../../../../academic_session_id')
                                                                  ?? $get('../../../../../../academic_session_id');

                                                        if (! $batchId) {
                                                            return 'Std: 0';
                                                        }

                                                        if ($courseId) {
                                                            $count = StudentEnrollmentCourse::query()
                                                                ->where('course_id', $courseId)
                                                                ->whereHas('studentCourseEnrollment', function ($q) use ($batchId, $sessionId) {
                                                                    if ($sessionId) {
                                                                        $q->where('academic_session_id', $sessionId);
                                                                    }
                                                                    $q->whereHas('student', function ($sq) use ($batchId) {
                                                                        $sq->where('batch_id', $batchId);
                                                                    });
                                                                })
                                                                ->count();

                                                            return "Std: {$count}";
                                                        }

                                                        $batch = Batch::find($batchId);
                                                        $count = 0;
                                                        if ($batch && method_exists($batch, 'students')) {
                                                            $count = $batch->students()->count();
                                                        }

                                                        return "Std: {$count}";
                                                    })
                                                    ->columnSpan(3),
                                            ]),
                                    ]),

                                // 🟢 ৩. রুম সিলেক্ট ও সিট ক্যাপাসিটি ফিল্ড (Add Batch বাটনের নিচে)
                                Grid::make(12)
                                    ->schema([
                                        Select::make('room_ids')
                                            ->label('Select Exam Rooms')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->options(function () {
                                                // রুম মডেলের নাম ও ক্যাপাসিটি লোড (যদি ফিল্ডের নাম capacity বা total_seats হয়)
                                                return Room::all()->mapWithKeys(function ($room) {
                                                    $cap = $room->capacity ?? $room->total_seats ?? $room->seats ?? 0;
                                                    return [$room->id => "{$room->room_number} (Cap: {$cap})"];
                                                });
                                            })
                                            ->columnSpan(8),

                                        Placeholder::make('room_capacity_status')
                                            ->label('Available Seats')
                                            ->content(function (Get $get) {
                                                $selectedRoomIds = $get('room_ids') ?? [];
                                                $batchCourses = $get('batch_courses') ?? [];
                                                $sessionId = $get('academic_session_id') 
                                                          ?? $get('../../../../../academic_session_id')
                                                          ?? $get('../../../../../../academic_session_id');

                                                // নির্বাচিত রুমগুলোর মোট সিট গণনা
                                                $totalAvailableSeats = 0;
                                                if (!empty($selectedRoomIds)) {
                                                    $rooms = Room::whereIn('id', $selectedRoomIds)->get();
                                                    foreach ($rooms as $room) {
                                                        $totalAvailableSeats += $room->capacity ?? $room->total_seats ?? $room->seats ?? 0;
                                                    }
                                                }

                                                // মোট স্টুডেন্ট ও ব্যাচ গণনা
                                                $totalSlotStudents = 0;
                                                $uniqueBatches = [];

                                                foreach ($batchCourses as $item) {
                                                    $bId = $item['batch_id'] ?? null;
                                                    $cId = $item['course_id'] ?? null;

                                                    if ($bId) {
                                                        $uniqueBatches[$bId] = true;
                                                        if ($cId) {
                                                            $totalSlotStudents += StudentEnrollmentCourse::query()
                                                                ->where('course_id', $cId)
                                                                ->whereHas('studentCourseEnrollment', function ($q) use ($bId, $sessionId) {
                                                                    if ($sessionId) {
                                                                        $q->where('academic_session_id', $sessionId);
                                                                    }
                                                                    $q->whereHas('student', fn ($sq) => $sq->where('batch_id', $bId));
                                                                })
                                                                ->count();
                                                        } else {
                                                            $batch = Batch::find($bId);
                                                            if ($batch && method_exists($batch, 'students')) {
                                                                $totalSlotStudents += $batch->students()->count();
                                                            }
                                                        }
                                                    }
                                                }

                                                $batchCount = count($uniqueBatches);
                                                $requiredSeats = ($batchCount === 1) ? ($totalSlotStudents * 2) : $totalSlotStudents;

                                                // খালি রুমের অবস্থা
                                                if (empty($selectedRoomIds)) {
                                                    return new HtmlString("<span class='text-sm text-gray-500'>Please select room(s)</span>");
                                                }

                                                // 🟢 ওয়ার্নিং লজিক
                                                if ($totalAvailableSeats < $requiredSeats) {
                                                    $shortage = $requiredSeats - $totalAvailableSeats;
                                                    $reasonMsg = ($batchCount === 1) 
                                                        ? " (Single batch requires 2x seats: {$requiredSeats})" 
                                                        : "";

                                                    return new HtmlString("
                                                        <div class='text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/50 p-2 rounded border border-red-200 dark:border-red-900'>
                                                            ⚠️ Available: {$totalAvailableSeats} Seats <br>
                                                            Shortage: {$shortage} seats{$reasonMsg}
                                                        </div>
                                                    ");
                                                }

                                                return new HtmlString("
                                                    <div class='text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 p-2 rounded border border-emerald-200 dark:border-emerald-900'>
                                                        ✅ Available: {$totalAvailableSeats} Seats
                                                    </div>
                                                ");
                                            })
                                            ->columnSpan(4),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}