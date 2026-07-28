<?php

namespace App\Livewire;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamScheduleCourse;
use App\Models\ExamScheduleRoom;
use App\Models\ExamSlot;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateExamSchedule extends Component
{
    // Left Column Fields
    public $title;
    public $academic_session_id;
    public $department_id;
    public $start_date;
    public $end_date;

    // Right Column (Dynamic Repeater State)
    public $schedules = [];

    public function mount()
    {
        // ডিফল্টভাবে একটা স্লট দিয়ে শুরু হবে
        $this->addSchedule();
    }

    // ➕ নতুন স্কেজিউল স্লট যুক্ত করা
    public function addSchedule()
    {
        $this->schedules[] = [
            'date' => '',
            'exam_slot_id' => '',
            'selected_rooms' => [],
            'courses' => [
                ['batch_id' => '', 'course_id' => '']
            ]
        ];
    }

    
    public function removeSchedule($index)
    {
        unset($this->schedules[$index]);
        $this->schedules = array_values($this->schedules);
    }

    
    public function addCourse($scheduleIndex)
    {
        $this->schedules[$scheduleIndex]['courses'][] = [
            'batch_id' => '',
            'course_id' => ''
        ];
    }

    public function removeCourse($scheduleIndex, $courseIndex)
    {
        unset($this->schedules[$scheduleIndex]['courses'][$courseIndex]);
        $this->schedules[$scheduleIndex]['courses'] = array_values($this->schedules[$scheduleIndex]['courses']);
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'academic_session_id' => 'required',
            'department_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'schedules.*.date' => 'required|date',
            'schedules.*.exam_slot_id' => 'required',
            'schedules.*.courses.*.batch_id' => 'required',
            'schedules.*.courses.*.course_id' => 'required',
        ]);

        DB::transaction(function () {
            $exam = Exam::create([
                'title' => $this->title,
                'academic_session_id' => $this->academic_session_id,
                'department_id' => $this->department_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'status' => 'draft',
            ]);

            foreach ($this->schedules as $schedData) {
                $schedule = ExamSchedule::create([
                    'exam_id' => $exam->id,
                    'exam_slot_id' => $schedData['exam_slot_id'],
                    'date' => $schedData['date'],
                    'status' => 'active',
                ]);

                if (!empty($schedData['selected_rooms'])) {
                    foreach ($schedData['selected_rooms'] as $roomId) {
                        ExamScheduleRoom::create([
                            'exam_schedule_id' => $schedule->id,
                            'room_id' => $roomId,
                        ]);
                    }
                }

                foreach ($schedData['courses'] as $courseData) {
                    ExamScheduleCourse::create([
                        'exam_schedule_id' => $schedule->id,
                        'batch_id' => $courseData['batch_id'],
                        'course_id' => $courseData['course_id'],
                    ]);
                }
            }
        });

        session()->flash('message', 'Exam Schedule Created Successfully!');
        return redirect()->to('/exams');
    }

    public function render()
    {
        return view('livewire.create-exam-schedule', [
            'sessions' => AcademicSession::all(),
            'departments' => Department::all(),
            'slots' => ExamSlot::all(),
            'rooms' => Room::all(),
            'batches' => Batch::all(),
            'courses' => Course::all(),
        ]);
    }
}