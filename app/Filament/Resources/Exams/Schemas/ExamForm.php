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
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                // 📌 বাম পাশের কলাম (৪ গ্রিড) - Exam Details & Seating Capacity
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

                                if (! empty($schedules)) {
                                    $schedules[array_key_first($schedules)]['date'] = $state;
                                } else {
                                    $schedules[] = ['date' => $state, 'slot_details' => []];
                                }

                                $set('schedules', $schedules);
                            })
                            ->required(),

                        Select::make('algorithm_type')
                            ->label('Seating Algorithm')
                            ->options([
                                'zig_zag_mixing' => 'Zig Zag Mixing (Cross Pattern)',
                                'column_separate' => 'Column Separate (Alternate Columns)',
                            ])
                            ->default('column_separate')
                            ->live()
                            ->required(),

                        Checkbox::make('skip_lab_courses')
                            ->label('Skip Lab / Practical Courses')
                            ->helperText('চেক দেওয়া থাকলে শুধু থিওরি কোর্সগুলো শো করবে।')
                            ->default(true)
                            ->live(),

                        Placeholder::make('effective_capacity_info')
                            ->hiddenLabel()
                            ->content(function (Get $get) {
                                $algorithm = $get('algorithm_type') ?? 'zig_zag_mixing';

                                $totalEffectiveSeats = Room::where('is_active', true)
                                    ->get()
                                    ->sum(fn ($room) => $room->effective_capacity);

                                if ($algorithm === 'column_separate') {
                                    $algorithmText = 'Column Separate Mode: পাশাপাশি কলামে ভিন্ন ব্যাচ বসবে। একক ব্যাচ হলে ১টি করে কলাম ফাঁকা থাকবে।';
                                } else {
                                    $algorithmText = 'Zig Zag Mixing Mode: ভিন্ন ব্যাচের শিক্ষার্থীরা পাশাপাশি ও সামনে-পেছনে জিক-জ্যাক মিক্সিং হয়ে বসবে।';
                                }

                                return new HtmlString("
                                    <div class='p-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg space-y-1.5 mt-2'>
                                        <div class='flex justify-between items-center'>
                                            <span class='text-xs font-semibold text-gray-500 dark:text-gray-400'>Active Total Effective Seats:</span>
                                            <span class='text-xs font-bold text-emerald-600 dark:text-emerald-400'>{$totalEffectiveSeats} Seats</span>
                                        </div>
                                        <p class='text-[11px] text-gray-500 dark:text-gray-400 leading-tight border-t border-gray-200 dark:border-gray-800 pt-1.5'>
                                            💡 {$algorithmText}
                                        </p>
                                    </div>
                                ");
                            }),
                    ]),

                // 📌 ডান পাশের কলাম (৮ গ্রিড) - Schedules Repeater
                Repeater::make('schedules')
                    ->columnSpan([
                        'default' => 12,
                        'lg' => 8,
                    ])
                    ->hiddenLabel()
                    ->collapsible()
                    ->itemLabel(function (array $state): ?string {
                        return ! empty($state['date']) ? Carbon::parse($state['date'])->format('M d, Y (l)') : 'Exam Date Row';
                    })
                    ->hidden(fn (Get $get): bool => ! $get('start_date'))
                    ->addAction(function (Action $action) {
                        return $action
                            ->label('+ Add Exam Date Row')
                            ->action(function (array $arguments, Repeater $component, Get $get) {
                                $state = $component->getState();
                                $lastRow = end($state);

                                $lastDateStr = $lastRow['date'] ?? $get('start_date');

                                if ($lastDateStr) {
                                    $nextDate = Carbon::parse($lastDateStr)->addDay();

                                    if ($nextDate->isSunday()) {
                                        $skippedSunday = $nextDate->format('d M, Y');
                                        $nextDate->addDay();

                                        Notification::make()
                                            ->warning()
                                            ->title('Sunday Skipped!')
                                            ->body("The next day {$skippedSunday} is Sunday. Automatically skipped to Monday ({$nextDate->format('d M, Y')}).")
                                            ->persistent()
                                            ->send();
                                    }

                                    $newDateStr = $nextDate->format('Y-m-d');
                                } else {
                                    $newDateStr = now()->format('Y-m-d');
                                }

                                $state[] = [
                                    'date' => $newDateStr,
                                    'exam_slots' => [],
                                    'slot_details' => [],
                                ];

                                $component->state($state);
                            });
                    })
                    ->defaultItems(1)
                    ->rules([
                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $schedules = $value ?? [];
                            $sessionId = $get('academic_session_id');

                            $totalEffectiveSeats = Room::where('is_active', true)
                                ->get()
                                ->sum(fn ($room) => $room->effective_capacity);

                            // ডেটাবেজের সমস্ত আসল স্লট ক্রমানুসারে নিয়ে রাখা
                            $allMasterSlots = ExamSlot::orderBy('start_time', 'asc')->pluck('id')->toArray();

                            foreach ($schedules as $sIndex => $schedule) {
                                $dateStr = ! empty($schedule['date']) ? Carbon::parse($schedule['date'])->format('d M, Y') : 'Row #'.($sIndex + 1);

                                if (empty($schedule['exam_slots'])) {
                                    $fail("{$dateStr} তারিখের জন্য কোনো Exam Slot নির্বাচন করা হয়নি।");

                                    return;
                                }

                                $slotDetails = $schedule['slot_details'] ?? [];

                                foreach ($slotDetails as $slot) {
                                    $slotId = $slot['exam_slot_id'] ?? null;
                                    $slotModel = ExamSlot::find($slotId);
                                    $slotName = $slotModel?->name ?? 'Slot';

                                    $batchCourses = $slot['batch_courses'] ?? [];

                                    if (empty($batchCourses)) {
                                        $fail("{$dateStr} তারিখের {$slotName}-এ অন্তত ১টি Batch ও Course যোগ করুন।");

                                        return;
                                    }

                                    $currentSlotBatchIds = [];
                                    $totalSlotStudents = 0;

                                    foreach ($batchCourses as $bc) {
                                        $bId = $bc['batch_id'] ?? null;
                                        $cId = $bc['course_id'] ?? null;

                                        if ($bId && ! $cId) {
                                            $batchNum = Batch::find($bId)?->batch_number ?? 'Batch';
                                            $fail("{$dateStr} -> {$slotName}: {$batchNum} ব্যাচের জন্য Course নির্বাচন করা হয়নি।");

                                            return;
                                        }

                                        if ($bId && $cId) {
                                            $currentSlotBatchIds[] = $bId;

                                            $totalSlotStudents += StudentEnrollmentCourse::query()
                                                ->where('course_id', $cId)
                                                ->whereHas('studentCourseEnrollment', function ($q) use ($bId, $sessionId) {
                                                    if ($sessionId) {
                                                        $q->where('academic_session_id', $sessionId);
                                                    }
                                                    $q->whereHas('student', fn ($sq) => $sq->where('batch_id', $bId));
                                                })
                                                ->count();
                                        }
                                    }

                                    // ৪. ক্যাপাসিটি চেক
                                    $batchCount = count(array_unique($currentSlotBatchIds));
                                    $maxAllowed = ($batchCount <= 1) ? floor($totalEffectiveSeats / 2) : $totalEffectiveSeats;

                                    if ($totalSlotStudents > $maxAllowed) {
                                        $fail("{$dateStr} -> {$slotName}: মোট শিক্ষার্থী ({$totalSlotStudents}) আপনার সর্বোচ্চ রুম ধারণক্ষমতা ({$maxAllowed}) ছাড়িয়ে গেছে!");

                                        return;
                                    }

                                    // 🟢 ৫. টাইম-সিকোয়েন্স ব্যাক-টু-ব্যাক ভ্যালিডেশন (শুধুমাত্র একই তারিখের $slotDetails থেকে চেক)
                                    $currentMasterIndex = array_search($slotId, $allMasterSlots);

                                    if ($currentMasterIndex !== false) {
                                        // ঠিক আগের আসল স্লট (একই তারিখের মধ্যে)
                                        $prevMasterSlotId = $allMasterSlots[$currentMasterIndex - 1] ?? null;
                                        if ($prevMasterSlotId) {
                                            $prevSlotDetail = collect($slotDetails)->firstWhere('exam_slot_id', $prevMasterSlotId);
                                            if ($prevSlotDetail) {
                                                $prevBatchIds = collect($prevSlotDetail['batch_courses'] ?? [])->pluck('batch_id')->filter()->toArray();
                                                $commonBatches = array_intersect($prevBatchIds, $currentSlotBatchIds);

                                                if (! empty($commonBatches)) {
                                                    $matchedNames = Batch::whereIn('id', $commonBatches)->pluck('batch_number')->implode(', ');
                                                    $fail("{$dateStr}: Batch {$matchedNames} একই দিনে ব্যাক-টু-ব্যাক স্লটে পরীক্ষায় অংশ নিতে পারবে না।");

                                                    return;
                                                }
                                            }
                                        }

                                        // ঠিক পরের আসল স্লট (একই তারিখের মধ্যে)
                                        $nextMasterSlotId = $allMasterSlots[$currentMasterIndex + 1] ?? null;
                                        if ($nextMasterSlotId) {
                                            $nextSlotDetail = collect($slotDetails)->firstWhere('exam_slot_id', $nextMasterSlotId);
                                            if ($nextSlotDetail) {
                                                $nextBatchIds = collect($nextSlotDetail['batch_courses'] ?? [])->pluck('batch_id')->filter()->toArray();
                                                $commonBatches = array_intersect($nextBatchIds, $currentSlotBatchIds);

                                                if (! empty($commonBatches)) {
                                                    $matchedNames = Batch::whereIn('id', $commonBatches)->pluck('batch_number')->implode(', ');
                                                    $fail("{$dateStr}: Batch {$matchedNames} একই দিনে ব্যাক-টু-ব্যাক স্লটে পরীক্ষায় অংশ নিতে পারবে না।");

                                                    return;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        },
                    ])
                    ->schema([
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
                                    ->options(function () {
                                        return ExamSlot::query()
                                            ->orderBy('start_time', 'asc')
                                            ->pluck('name', 'id');
                                    })
                                    ->columns(4)
                                    ->gridDirection('row')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $selectedSlotIds = $state ?? [];
                                        $currentSlotDetails = $get('slot_details') ?? [];

                                        $orderedSlots = ExamSlot::whereIn('id', $selectedSlotIds)
                                            ->orderBy('start_time', 'asc')
                                            ->get();

                                        $newSlotDetails = [];
                                        foreach ($orderedSlots as $slot) {
                                            $existing = collect($currentSlotDetails)->firstWhere('exam_slot_id', $slot->id);
                                            $newSlotDetails[] = $existing ?? [
                                                'exam_slot_id' => $slot->id,
                                                'batch_courses' => [],
                                            ];
                                        }
                                        $set('slot_details', $newSlotDetails);
                                    })
                                    ->required()
                                    ->columnSpan(8),
                            ]),

                        Repeater::make('slot_details')
                            ->hiddenLabel()
                            ->hidden(fn (Get $get): bool => empty($get('exam_slots')))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(function (array $state, Get $get): HtmlString {
                                $slotId = $state['exam_slot_id'] ?? null;
                                $slotName = ExamSlot::find($slotId)?->name ?? 'Exam Slot';

                                $batchCourses = $state['batch_courses'] ?? [];
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

                                $totalEffectiveSeats = Room::where('is_active', true)
                                    ->get()
                                    ->sum(fn ($room) => $room->effective_capacity);

                                $batchCount = count($uniqueBatches);

                                if ($batchCount <= 1) {
                                    $maxAllowed = floor($totalEffectiveSeats / 2);
                                    $notice = ' (Single Batch Limit)';
                                } else {
                                    $maxAllowed = $totalEffectiveSeats;
                                    $notice = '';
                                }

                                $isOverflow = $totalSlotStudents > $maxAllowed;

                                $badgeColor = $isOverflow
                                    ? 'bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800'
                                    : 'bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-300 border-blue-200 dark:border-blue-800';

                                return new HtmlString("
                                    <div class='flex items-center justify-between w-full pr-4'>
                                        <span class='font-bold text-sm text-gray-800 dark:text-gray-100'>{$slotName}</span>
                                        <span class='text-xs font-semibold {$badgeColor} px-3 py-0.5 rounded-full border'>
                                            Slot Selected Std: <b>{$totalSlotStudents}</b> / {$maxAllowed} Max{$notice}
                                        </span>
                                    </div>
                                ");
                            })
                            ->schema([
                                Repeater::make('batch_courses')
                                    ->hiddenLabel()
                                    ->addActionLabel('+ Add Batch')
                                    ->reorderable(false)
                                    ->defaultItems(1)
                                    ->extraAttributes([
                                        'class' => '[&_.fi-fo-repeater-item-header]:!hidden [&_.fi-fo-repeater-item]:!bg-transparent [&_.fi-fo-repeater-item]:!border-0 [&_.fi-fo-repeater-item]:!shadow-none [&_.fi-fo-repeater-item]:!p-0 [&_.fi-fo-repeater-item]:!mb-2',
                                    ])
                                    ->schema([
                                        Grid::make(12)
                                            ->schema([
                                                Select::make('batch_id')
                                                    ->hiddenLabel()
                                                    ->placeholder('Select Batch')
                                                    ->options(function (Get $get) {
                                                        // ১. ডিপার্টমেন্ট ও সেশন অনুযায়ী অ্যাসাইন করা ব্যাচসমূহ ফিল্টার
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

                                                        // 🟢 ২. একই স্লটের ভেতরে একই ব্যাচ একাধিকবার সিলেক্ট না করার ফিল্টার
                                                        $siblingBatchCourses = $get('../') ?? [];
                                                        $currentBatchId = $get('batch_id');

                                                        $usedBatchIds = collect($siblingBatchCourses)
                                                            ->pluck('batch_id')
                                                            ->filter()
                                                            ->reject(fn ($id) => $id == $currentBatchId)
                                                            ->toArray();

                                                        // 🟢 ৩. রিয়েল টাইম-সিকোয়েন্স অনুযায়ী ব্যাক-টু-ব্যাক স্লট ফিল্টারিং (শুধুমাত্র একই তারিখের জন্য)
                                                        $currentSlotId = $get('../../exam_slot_id');

                                                        // প্যারেন্ট লেভেল থেকে বর্তমান তারিখটি তুলে আনা
                                                        $currentScheduleDate = $get('../../date')
                                                                            ?? $get('../../../date')
                                                                            ?? $get('../../../../date');

                                                        // রুট থেকে সব schedules অ্যারে তুলে আনা
                                                        $schedules = $get('/schedules')
                                                                  ?? $get('../../schedules')
                                                                  ?? $get('../../../../../../schedules')
                                                                  ?? [];

                                                        // বর্তমান তারিখের Schedule খুঁজে বের করা
                                                        $currentDateSchedule = collect($schedules)->first(function ($schedule) use ($currentScheduleDate) {
                                                            if (empty($schedule['date']) || empty($currentScheduleDate)) {
                                                                return false;
                                                            }

                                                            return $schedule['date'] === $currentScheduleDate
                                                                || date('Y-m-d', strtotime($schedule['date'])) === date('Y-m-d', strtotime($currentScheduleDate));
                                                        });

                                                        if ($currentDateSchedule) {
                                                            $slotDetails = $currentDateSchedule['slot_details'] ?? [];

                                                            if (! empty($slotDetails)) {
                                                                // ডেটাবেজ থেকে সমস্ত স্লট সময় অনুযায়ী নিয়ে মাস্টার সিকোয়েন্স তৈরি
                                                                $allMasterSlots = ExamSlot::orderBy('start_time', 'asc')->pluck('id')->toArray();

                                                                // বর্তমান স্লটের ইনডেক্স
                                                                $currentMasterIndex = array_search($currentSlotId, $allMasterSlots);

                                                                if ($currentMasterIndex !== false) {
                                                                    // ক) ঠিক আগের স্লট (Master Index - 1)
                                                                    $prevMasterSlotId = $allMasterSlots[$currentMasterIndex - 1] ?? null;
                                                                    if ($prevMasterSlotId) {
                                                                        $prevSlotDetail = collect($slotDetails)->firstWhere('exam_slot_id', $prevMasterSlotId);
                                                                        if ($prevSlotDetail) {
                                                                            foreach ($prevSlotDetail['batch_courses'] ?? [] as $bc) {
                                                                                if (! empty($bc['batch_id'])) {
                                                                                    $usedBatchIds[] = $bc['batch_id'];
                                                                                }
                                                                            }
                                                                        }
                                                                    }

                                                                    // খ) ঠিক পরের স্লট (Master Index + 1)
                                                                    $nextMasterSlotId = $allMasterSlots[$currentMasterIndex + 1] ?? null;
                                                                    if ($nextMasterSlotId) {
                                                                        $nextSlotDetail = collect($slotDetails)->firstWhere('exam_slot_id', $nextMasterSlotId);
                                                                        if ($nextSlotDetail) {
                                                                            foreach ($nextSlotDetail['batch_courses'] ?? [] as $bc) {
                                                                                if (! empty($bc['batch_id'])) {
                                                                                    $usedBatchIds[] = $bc['batch_id'];
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        // ব্যবহৃত ব্যাচগুলো বাদ দেওয়া (Exclude Used Batches)
                                                        if (! empty($usedBatchIds)) {
                                                            $query->whereNotIn('id', array_unique($usedBatchIds));
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
                                                    ->afterStateUpdated(function ($state, Get $get) {
                                                        if (! $state) {
                                                            return;
                                                        }

                                                        $siblingBatchCourses = $get('../') ?? [];
                                                        $sameCourseCount = 0;

                                                        foreach ($siblingBatchCourses as $bc) {
                                                            if (($bc['course_id'] ?? null) == $state) {
                                                                $sameCourseCount++;
                                                            }
                                                        }

                                                        if ($sameCourseCount > 1) {
                                                            $courseTitle = Course::find($state)?->course_title ?? 'Selected Course';
                                                            Notification::make()
                                                                ->warning()
                                                                ->title('Same Course Assigned!')
                                                                ->body("Warning: '{$courseTitle}' কোর্সটি একই স্লটে একাধিক ব্যাচে সিলেক্ট করা হয়েছে।")
                                                                ->persistent()
                                                                ->send();
                                                        }
                                                    })
                                                    ->options(function (Get $get) {
                                                        $batchId = $get('batch_id');
                                                        $sessionId = $get('academic_session_id')
                                                                  ?? $get('../../../../../academic_session_id')
                                                                  ?? $get('../../../../../../academic_session_id');

                                                        if (! $batchId) {
                                                            return [];
                                                        }

                                                        $batch = Batch::find($batchId);
                                                        if (! $batch) {
                                                            return [];
                                                        }

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
                                                                    if (($bc['batch_id'] ?? null) == $batchId && ! empty($bc['course_id'])) {
                                                                        $usedCourseIds[] = $bc['course_id'];
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        $currentCourseId = $get('course_id');
                                                        $usedCourseIds = array_diff($usedCourseIds, [$currentCourseId]);

                                                        $availableCourseIds = array_diff($assignedCourseIds, $usedCourseIds);

                                                        $skipLab = $get('skip_lab_courses')
                                                                ?? $get('../../../../../skip_lab_courses')
                                                                ?? $get('../../../../../../skip_lab_courses')
                                                                ?? true;

                                                        $coursesQuery = Course::whereIn('id', $availableCourseIds);

                                                        $courses = $coursesQuery->get()->filter(function ($course) use ($skipLab) {
                                                            if (! $skipLab) {
                                                                return true;
                                                            }

                                                            if (isset($course->type) && in_array(strtolower($course->type), ['lab', 'practical', 'sessional'])) {
                                                                return false;
                                                            }
                                                            if (isset($course->is_lab) && $course->is_lab) {
                                                                return false;
                                                            }

                                                            $code = strtolower($course->course_code ?? $course->code ?? '');
                                                            $title = strtolower($course->course_title ?? $course->name ?? '');

                                                            $keywords = ['lab', 'laboratory', 'sessional', 'practical', 'project', 'thesis', 'studio'];

                                                            foreach ($keywords as $keyword) {
                                                                if (str_contains($code, $keyword) || str_contains($title, $keyword)) {
                                                                    return false;
                                                                }
                                                            }

                                                            return true;
                                                        })
                                                            ->mapWithKeys(function ($course) {
                                                                $code = $course->course_code ?? $course->code ?? '';
                                                                $title = $course->course_title ?? $course->name ?? '';

                                                                return [$course->id => trim("{$code} - {$title}", ' -')];
                                                            })
                                                            ->toArray();

                                                        return ! empty($courses) ? ["Batch {$batch->batch_number}" => $courses] : [];
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
                            ]),
                    ]),
            ]);
    }
}
