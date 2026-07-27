<?php

namespace App\Services;

use App\Models\Exam;
// আপনার সিডিউল টেবিল
// আপনার সিটিং প্ল্যান টেবিল
use App\Models\SectionCourseAssignment;
use App\Models\SectionCourseAssignmentItem;
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

    public function handle(): bool
    {

        $exam = Exam::findOrFail($this->data['exam_id']);
        $availableDates = $this->getAvailableDates($exam->start_date, $exam->end_date, $this->data['holidays'] ?? []);
        $coursesGroupedByBatch = $this->getCoursesToSchedule($exam, $this->data['exclude_lab_courses'] ?? true);

        $uniqueCourses = [];
        foreach ($coursesGroupedByBatch as $batchId => $items) {
            foreach ($items as $item) {
                $courseId = $item->course_id;
                if (!isset($uniqueCourses[$courseId])) {
                    $uniqueCourses[$courseId] = [
                        'course_id'   => $courseId,
                        'course_code' => $item->course->course_code,
                        'course_title'=> $item->course->course_title,
                        'batches'     => [],
                    ];
                }
                if (!in_array($batchId, $uniqueCourses[$courseId]['batches'])) {
                    $uniqueCourses[$courseId]['batches'][] = $batchId;
                }
            }
        }

        $routine = $this->generateScheduleMatrix(
            $uniqueCourses,
            $availableDates,
            $this->data['exam_slot_ids'] ?? [],
            (int) ($this->data['max_courses_per_slot'] ?? 3),
            (bool) ($this->data['prevent_back_to_back'] ?? true)
        );

        dd([
            'status' => 'Routine Generated Successfully!',
            'total_courses_scheduled' => count($uniqueCourses),
            'routine_matrix' => $routine,
        ]);

        return true;
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
        // ট্র্যাক করার জন্য ম্যাপ
        $batchScheduleTracker = []; // [batch_id][date][slot_id] = true

        // Slot Index mapping for Back-to-Back Calculation
        $slotIndexes = array_flip(array_values($slotIds));

        foreach ($courses as $courseId => $courseData) {
            $scheduled = false;

            // সমানভাবে ছড়ানোর জন্য তারিখ এবং স্লট ঘুরে দেখা
            foreach ($dates as $date) {
                foreach ($slotIds as $slotId) {

                    // ১. সিডিউলের সর্বোচ্চ কোর্স লিমিট চেক
                    $currentSlotCourses = $routine[$date][$slotId] ?? [];
                    if (count($currentSlotCourses) >= $maxCoursesPerSlot) {
                        continue; 
                    }

                    // ২. ব্যাচ কনফ্লিক্ট ও ব্যাক-টু-ব্যাক স্লট চেক
                    $hasConflict = false;

                    foreach ($courseData['batches'] as $batchId) {
                        // একই দিনে ও স্লটে ওই ব্যাচের পরীক্ষা ইতিমধ্যে আছে কি না
                        if (isset($batchScheduleTracker[$batchId][$date][$slotId])) {
                            $hasConflict = true;
                            break;
                        }

                        // একই দিনে ওই ব্যাচের অন্য কোনো স্লটে পরীক্ষা আছে কি না (Slot Level Checking)
                        if (isset($batchScheduleTracker[$batchId][$date])) {
                            // যদি Prevent Back-to-Back অন থাকে
                            if ($preventBackToBack) {
                                $currentSlotIndex = $slotIndexes[$slotId];

                                foreach ($batchScheduleTracker[$batchId][$date] as $assignedSlotId => $isAssigned) {
                                    $assignedSlotIndex = $slotIndexes[$assignedSlotId];
                                    // পরপর দুটি স্লটের পার্থক্য ১ হলে তা Back-to-Back Conflict
                                    if (abs($currentSlotIndex - $assignedSlotIndex) <= 1) {
                                        $hasConflict = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    // যদি কোনো কনফ্লিক্ট না থাকে, তাহলে স্লটে কোর্সটি বসানো হবে
                    if (!$hasConflict) {
                        // রুটিনে সেভ
                        $routine[$date][$slotId][] = $courseData;

                        // ব্যাচ ট্র্যাকার আপডেট
                        foreach ($courseData['batches'] as $batchId) {
                            $batchScheduleTracker[$batchId][$date][$slotId] = true;
                        }

                        $scheduled = true;
                        break 2; // পরবর্তী কোর্সে যাওয়ার জন্য লুপ ব্রেক
                    }
                }
            }

            if (!$scheduled) {
                throw new Exception("কোর্সটি ({$courseData['course_code']} - {$courseData['course_title']}) সিডিউল করা সম্ভব হয়নি! দেওয়া শর্তে পর্যাপ্ত স্লট বা দিন খালি নেই।");
            }
        }

        return $routine;
    }
}
