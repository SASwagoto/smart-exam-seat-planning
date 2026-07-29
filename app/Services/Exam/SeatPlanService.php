<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\Room;
use App\Models\ExamSeat;
use App\Models\ExamSessionRoom;
use App\Models\StudentEnrollmentCourse;
use Illuminate\Support\Facades\DB;

class SeatPlanService
{
    protected $engine;

    public function __construct(RoomAllocationEngine $engine)
    {
        $this->engine = $engine;
    }

    public function generate(Exam $exam)
    {
        return DB::transaction(function () use ($exam) {
            $sessionIds = $exam->sessions->pluck('id');
            
            // পুরাতন ডেটা ক্লিন করা
            ExamSessionRoom::whereIn('exam_session_id', $sessionIds)->delete();

            $rooms = Room::where('is_active', true)->orderBy('room_number')->get();

            foreach ($exam->sessions as $session) {
                // ২. স্টুডেন্ট আইডি লিস্ট লোড করা
                $sessionCourses = $session->courses->map(function ($sc) use ($exam) {
                    $sc->student_list = StudentEnrollmentCourse::where('course_id', $sc->course_id)
                        ->where('academic_session_id', $exam->academic_session_id)
                        ->where('batch_id', $sc->batch_id)
                        ->whereNotNull('student_id')
                        ->distinct()
                        ->pluck('student_id')
                        ->toArray();
                    return $sc;
                });

                // ৩. ইঞ্জিন কল করা
                $allocations = $this->engine->allocate($sessionCourses, $rooms, $exam->algorithm_type);

                // ৪. ডেটাবেজে সেভ করা
                foreach ($allocations as $allocation) {
                    $sessionRoom = ExamSessionRoom::create([
                        'exam_session_id' => $session->id,
                        'room_id' => $allocation['room_id'],
                        'allocated_students' => count($allocation['seats']),
                    ]);

                    $bulkSeats = [];
                    foreach ($allocation['seats'] as $seat) {
                        $bulkSeats[] = [
                            'exam_session_room_id'   => $sessionRoom->id,
                            'exam_session_course_id' => $seat['exam_session_course_id'],
                            'student_id'             => $seat['student_id'],
                            'row_label'              => $seat['row_label'],
                            'column_label'           => $seat['column_label'],
                            'seat_label'             => $seat['seat_label'],
                            'created_at'             => now(),
                            'updated_at'             => now(),
                        ];
                    }

                    if (!empty($bulkSeats)) {
                        ExamSeat::insert($bulkSeats);
                    }
                }
            }

            $exam->update(['status' => 'scheduled']);
            return true;
        });
    }
}