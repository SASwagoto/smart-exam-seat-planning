<?php

namespace App\Services;

use App\Models\Exam;
// আপনার সিডিউল টেবিল
// আপনার সিটিং প্ল্যান টেবিল
use App\Models\SectionCourseAssignment;
use App\Models\SectionCourseAssignmentItem;
use Carbon\CarbonPeriod;
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

                // কোন ব্যাচ কোন কোর্সের সাথে যুক্ত তা ম্যাপ করে রাখা
                $uniqueCourses[$courseId]['course_info'] = $item->course;
                $uniqueCourses[$courseId]['batches'][] = $batchId;
            }
        }
        
        dd([
            'total_unique_courses_to_schedule' => count($uniqueCourses),
            'unique_courses_master_list' => $uniqueCourses,
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
}
