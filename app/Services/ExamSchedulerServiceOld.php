<?php

namespace App\Services;

use App\Models\Exam;
// আপনার সিডিউল টেবিল
// আপনার সিটিং প্ল্যান টেবিল
use App\Models\ExamSchedule;
use App\Models\ExamScheduleCourse;
use App\Models\ExamScheduleRoom;
use App\Models\Room;
use App\Models\SeatAllocation;
use App\Models\SectionCourseAssignment;
use App\Models\SectionCourseAssignmentItem;
use App\Models\Student;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\DB;

class ExamSchedulerService
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handleAndStore(array $roomIds): bool
    {
        return DB::transaction(function () use ($roomIds) {
            $exam = Exam::findOrFail($this->data['exam_id']);
            $availableDates = $this->getAvailableDates($exam->start_date, $exam->end_date, $this->data['holidays'] ?? []);
            $coursesGroupedByBatch = $this->getCoursesToSchedule($exam, $this->data['exclude_lab_courses'] ?? true);

            $uniqueCourses = [];
            foreach ($coursesGroupedByBatch as $batchId => $items) {
                foreach ($items as $item) {
                    $courseId = $item->course_id;
                    if (! isset($uniqueCourses[$courseId])) {
                        $uniqueCourses[$courseId] = [
                            'course_id' => $courseId,
                            'course_code' => $item->course->course_code,
                            'course_title' => $item->course->course_title,
                            'batches' => [],
                        ];
                    }
                    if (! in_array($batchId, $uniqueCourses[$courseId]['batches'])) {
                        $uniqueCourses[$courseId]['batches'][] = $batchId;
                    }
                }
            }

            // ১. রুটিন ম্যাট্রিক্স জেনারেট
            $routine = $this->generateScheduleMatrix(
                $uniqueCourses,
                $availableDates,
                $this->data['exam_slot_ids'] ?? [],
                (int) ($this->data['max_courses_per_slot'] ?? 3),
                (bool) ($this->data['prevent_back_to_back'] ?? true)
            );

            // ২. জেনারেট করা রুটিন এবং সিট এলোকেশন DB-তে স্টোর
            $rooms = Room::whereIn('id', $roomIds)->get();

            foreach ($routine as $date => $slots) {
                foreach ($slots as $slotId => $courses) {

                    // exam_schedules টেবিলে ইনসার্ট
                    $examSchedule = ExamSchedule::create([
                        'exam_id' => $exam->id,
                        'exam_slot_id' => $slotId,
                        'date' => $date,
                        'status' => 'draft',
                    ]);

                    $slotStudentsQueue = [];
                    $totalStudentsInSlot = 0;

                    foreach ($courses as $courseData) {
                        $assignments = SectionCourseAssignment::with([
                            'items' => function ($q) use ($courseData) {
                                $q->where('course_id', $courseData['course_id']);
                            },
                        ])
                            ->where('academic_session_id', $exam->academic_session_id)
                            ->where('department_id', $exam->department_id)
                            ->whereHas('items', function ($q) use ($courseData) {
                                $q->where('course_id', $courseData['course_id']);
                            })
                            ->get();

                        foreach ($assignments as $assignment) {

                            // এই assignment-এর মধ্যে যে item-টা বর্তমান course-এর জন্য, সেটা বের করো
                            $assignmentItem = $assignment->items->first();

                            if (! $assignmentItem) {
                                continue;
                            }

                            $students = Student::where('section_id', $assignment->section_id)
                                ->pluck('id')
                                ->toArray();

                            $studentCount = count($students);
                            $totalStudentsInSlot += $studentCount;

                            ExamScheduleCourse::create([
                                'exam_schedule_id' => $examSchedule->id,
                                'section_course_assignment_id' => $assignment->id,
                                'section_course_assignment_item_id' => $assignmentItem->id,
                                'course_id' => $assignmentItem->course_id,
                                'batch_id' => $assignment->batch_id,
                                'student_count' => $studentCount,
                            ]);

                            foreach ($students as $studentId) {
                                $slotStudentsQueue[] = [
                                    'student_id' => $studentId,
                                    'section_course_assignment_id' => $assignment->id,
                                    'course_id' => $assignmentItem->course_id,
                                ];
                            }
                        }
                    }

                    // স্টুডেন্টদের জিগজ্যাগ করে সাজানো
                    $interleavedStudents = $this->interleaveStudentsByCourse($slotStudentsQueue);

                    // রুমে সিট অ্যালট করা
                    $allocatedCount = $this->allocateSeatsForSlot($examSchedule, $rooms, $interleavedStudents);

                    $examSchedule->update([
                        'total_students' => $totalStudentsInSlot,
                        'total_allocated_seats' => $allocatedCount,
                    ]);
                }
            }

            return true;
        });
    }

    protected function getAvailableDates($startDate, $endDate, array $holidays): array
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];

        foreach ($period as $date) {
            $formatted = $date->format('Y-m-d');
            if (! in_array($formatted, $holidays)) {
                $dates[] = $formatted;
            }
        }

        return $dates;
    }

    protected function getCoursesToSchedule($exam, bool $excludeLab)
    {
        $assignmentIds = SectionCourseAssignment::where('academic_session_id', $exam->academic_session_id)
            ->where('department_id', $exam->department_id)
            ->pluck('id');

        $query = SectionCourseAssignmentItem::whereIn('section_course_assignment_id', $assignmentIds)
            ->with(['course', 'sectionCourseAssignment.batch']);

        // যদি থিওরি কোর্স ফিল্টার অন থাকে (Lab কোর্স বাদ যাবে)
        if ($excludeLab) {
            $query->whereHas('course', function ($q) {
                $q->where('type', '!=', 'lab'); // বা আপনার DB অনুযায়ী lab কোর্সের কন্ডিশন
            });
        }

        return $query->get()->groupBy(function ($item) {
            return $item->sectionCourseAssignment->batch_id; // Batch ওয়াইজ গ্রুপ করা
        });
    }

    protected function generateScheduleMatrix(
        array $courses,
        array $dates,
        array $slotIds,
        int $maxCoursesPerSlot,
        bool $preventBackToBack
    ): array {
        $routine = [];
        $batchScheduleTracker = []; // [batch_id][date][slot_id] = true
        $batchDatesTracker = [];    // [batch_id][] = 'Y-m-d'
        $slotIndexes = array_flip(array_values($slotIds));

        // 🎯 ১. সিনিয়র এবং জুনিয়র ব্যাচের ট্র্যাকিং সহজ করার জন্য
        // ব্যাচ আইডিগুলোকে ছোট থেকে বড় (Senior -> Junior) সাজানো
        $allBatchIds = [];
        foreach ($courses as $c) {
            $allBatchIds = array_merge($allBatchIds, $c['batches']);
        }
        $allBatchIds = array_unique($allBatchIds);
        sort($allBatchIds); // সর্বনিম্ন ID = সিনিয়র, সর্বোচ্চ ID = জুনিয়র

        $totalCourses = count($courses);
        $totalAvailableSlots = count($dates) * count($slotIds);

        // প্রতিটি স্লটে কতটি করে কোর্স থাকা আদর্শ (Ideal Target per Slot)
        $idealPerSlot = max(2, (int) ceil($totalCourses / max(1, $totalAvailableSlots)));

        foreach ($courses as $courseId => $courseData) {
            $scheduled = false;

            // সম্ভাব্য স্লটগুলোর তালিকা তৈরি
            $candidateSlots = [];
            foreach ($dates as $date) {
                foreach ($slotIds as $slotId) {
                    $assignedCourses = $routine[$date][$slotId] ?? [];
                    $assignedCount = count($assignedCourses);

                    if ($assignedCount < $maxCoursesPerSlot) {
                        $candidateSlots[] = [
                            'date' => $date,
                            'slot_id' => $slotId,
                            'assigned_count' => $assignedCount,
                            'assigned_courses' => $assignedCourses,
                        ];
                    }
                }
            }

            // 🎯 ২. সিনিয়র-জুনিয়র পেয়ারিং সহ ডাইনামিক স্কোরিং
            usort($candidateSlots, function ($a, $b) use ($courseData, $batchDatesTracker, $slotIndexes, $preventBackToBack, $idealPerSlot, $allBatchIds) {

                $scoreA = $this->calculateCandidateScore($a, $courseData, $batchDatesTracker, $slotIndexes, $preventBackToBack, $idealPerSlot, $allBatchIds);
                $scoreB = $this->calculateCandidateScore($b, $courseData, $batchDatesTracker, $slotIndexes, $preventBackToBack, $idealPerSlot, $allBatchIds);

                if ($scoreA !== $scoreB) {
                    return $scoreA <=> $scoreB; // কম স্কোর = বেশি প্রাধান্য
                }

                return strcmp($a['date'], $b['date']);
            });

            // ৩. কনফ্লিক্ট-ফ্রি স্লটে কোর্স অ্যাসাইন করা
            foreach ($candidateSlots as $candidate) {
                $date = $candidate['date'];
                $slotId = $candidate['slot_id'];

                $hasConflict = false;

                foreach ($courseData['batches'] as $batchId) {
                    // একই দিনে ও স্লটে ওই ব্যাচের পরীক্ষা ব্লক
                    if (isset($batchScheduleTracker[$batchId][$date][$slotId])) {
                        $hasConflict = true;
                        break;
                    }

                    // ব্যাক-টু-ব্যাক স্লট ব্লক
                    if (isset($batchScheduleTracker[$batchId][$date]) && $preventBackToBack) {
                        $currentSlotIndex = $slotIndexes[$slotId];
                        foreach ($batchScheduleTracker[$batchId][$date] as $assignedSlotId => $isAssigned) {
                            $assignedSlotIndex = $slotIndexes[$assignedSlotId];
                            if (abs($currentSlotIndex - $assignedSlotIndex) <= 1) {
                                $hasConflict = true;
                                break;
                            }
                        }
                    }
                }

                // কনফ্লিক্ট না থাকলে বসানো হবে
                if (! $hasConflict) {
                    $routine[$date][$slotId][] = $courseData;

                    foreach ($courseData['batches'] as $batchId) {
                        $batchScheduleTracker[$batchId][$date][$slotId] = true;
                        $batchDatesTracker[$batchId][] = $date;
                        $batchDatesTracker[$batchId] = array_unique($batchDatesTracker[$batchId]);
                    }

                    $scheduled = true;
                    break;
                }
            }

            if (! $scheduled) {
                throw new Exception("কোর্সটি ({$courseData['course_code']} - {$courseData['course_title']}) সিডিউল করা সম্ভব হয়নি! দেওয়া শর্তে পর্যাপ্ত জায়গা নেই।");
            }
        }

        return $routine;
    }

    /**
     * 🧠 স্মার্ট স্কোরিং অ্যালগরিদম (Senior-Junior Pairing Logic)
     */
    protected function calculateCandidateScore(
        array $candidate,
        array $courseData,
        array $batchDatesTracker,
        array $slotIndexes,
        bool $preventBackToBack,
        int $idealPerSlot,
        array $allBatchIds
    ): int {
        $score = 0;
        $date = $candidate['date'];
        $count = $candidate['assigned_count'];

        // 🟢 শর্ত ১: ইকুয়াল ডিস্ট্রিবিউশন পেনাল্টি (স্লট ওভারলোড আটকানো)
        if ($count >= $idealPerSlot) {
            $score += ($count - $idealPerSlot + 1) * 300;
        }

        // 🟢 শর্ত ২: সিনিয়র + জুনিয়র ব্যাচ পেয়ারিং
        if ($count === 1) {
            $score -= 100; // ১টি থাকলে ২য়টির জন্য বেসিক ছাড়/বোনাস

            // স্লটে অলরেডি যে কোর্সটি বসেছে তার ব্যাচ দেখা
            $existingCourse = $candidate['assigned_courses'][0];
            $existingBatchId = $existingCourse['batches'][0] ?? null;
            $currentBatchId = $courseData['batches'][0] ?? null;

            if ($existingBatchId && $currentBatchId && count($allBatchIds) > 1) {
                $minBatchId = min($allBatchIds); // সবচেয়ে সিনিয়র ব্যাচ
                $maxBatchId = max($allBatchIds); // সবচেয়ে জুনিয়র ব্যাচ
                $midBatchId = ($minBatchId + $maxBatchId) / 2;

                // চেক করা: একটি যদি মিডল পয়েন্টের নিচে (Senior) হয় এবং অন্যটি যদি উপরে (Junior) হয়
                $isExistingSenior = $existingBatchId <= $midBatchId;
                $isCurrentJunior = $currentBatchId > $midBatchId;

                $isExistingJunior = $existingBatchId > $midBatchId;
                $isCurrentSenior = $currentBatchId <= $midBatchId;

                // যদি পারফেক্ট Senior + Junior কম্বিনেশন হয় তবে স্পেশাল বোনাস!
                if (($isExistingSenior && $isCurrentJunior) || ($isExistingJunior && $isCurrentSenior)) {
                    $score -= 200; // অতিরিক্ত বোনাস পেয়ে পেয়ারিং নিশ্চিত করবে!
                }
            }

        } elseif ($count === 0) {
            $score += 20; // নতুন ফাঁকা স্লটের চেয়ে পেয়ার করার সুযোগ থাকা স্লট আগে চান্স পাবে
        }

        // 🟢 শর্ত ৩: ১ দিন পর পর গ্যাপের প্রাধান্য
        foreach ($courseData['batches'] as $batchId) {
            if (isset($batchDatesTracker[$batchId])) {
                foreach ($batchDatesTracker[$batchId] as $prevDate) {
                    $diffInDays = abs((strtotime($date) - strtotime($prevDate)) / (60 * 60 * 24));

                    if ($diffInDays == 0) {
                        $score += 800; // একই দিনে ২য় এক্সাম (সর্বশেষ উপায়)
                    } elseif ($diffInDays == 1) {
                        $score += 50;  // পরপর দিনে এক্সাম
                    } elseif ($diffInDays == 2) {
                        $score -= 100; // ১ দিন ছুটির সাথে এক্সাম (বেস্ট অপশন)
                    }
                }
            }
        }

        return $score;
    }

    protected function interleaveStudentsByCourse(array $studentsQueue): array
    {
        $grouped = [];
        foreach ($studentsQueue as $item) {
            $grouped[$item['section_course_assignment_id']][] = $item;
        }

        $result = [];
        while (! empty($grouped)) {
            foreach ($grouped as $assignmentId => &$students) {
                if (! empty($students)) {
                    $result[] = array_shift($students);
                } else {
                    unset($grouped[$assignmentId]);
                }
            }
        }

        return $result;
    }

    protected function allocateSeatsForSlot($examSchedule, $rooms, array $students): int
    {
        $studentIndex = 0;
        $totalStudents = count($students);
        $totalAllocated = 0;

        foreach ($rooms as $room) {
            if ($studentIndex >= $totalStudents) {
                break;
            }

            $rows = $room->total_rows ?? 5;
            $cols = $room->total_columns ?? 4;
            $roomAllocatedCount = 0;
            $seatsToInsert = [];

            for ($r = 1; $r <= $rows; $r++) {
                for ($c = 1; $c <= $cols; $c++) {
                    if ($studentIndex >= $totalStudents) {
                        break 2;
                    }

                    $currentStudent = $students[$studentIndex];
                    $seatsToInsert[] = [
                        'exam_schedule_id' => $examSchedule->id,
                        'room_id' => $room->id,
                        'student_id' => $currentStudent['student_id'],
                        'section_course_assignment_id' => $currentStudent['section_course_assignment_id'],
                        'course_id' => $currentStudent['course_id'],
                        'row_no' => $r,
                        'col_no' => $c,
                        'seat_number' => "R{$r}-C{$c}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $studentIndex++;
                    $roomAllocatedCount++;
                    $totalAllocated++;
                }
            }

            ExamScheduleRoom::create([
                'exam_schedule_id' => $examSchedule->id,
                'room_id' => $room->id,
                'effective_capacity' => $roomAllocatedCount,
            ]);

            if (! empty($seatsToInsert)) {
                SeatAllocation::insert($seatsToInsert);
            }
        }

        return $totalAllocated;
    }
}
