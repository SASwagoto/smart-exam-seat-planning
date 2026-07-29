<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamSeat;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\StudentEnrollmentCourse;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function create(array $data): Exam
    {
        return DB::transaction(function () use ($data) {

            $exam = Exam::create([
                'name' => $data['name'],
                'academic_session_id' => $data['academic_session_id'],
                'department_id' => $data['department_id'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => collect($data['schedules'])->max('date'),
                'algorithm_type' => $data['algorithm_type'],
                'status' => 'draft',
            ]);

            foreach ($data['schedules'] as $schedule) {

                foreach ($schedule['slot_details'] as $slotDetail) {

                    $session = $exam->sessions()->create([
                        'exam_date' => $schedule['date'],
                        'exam_slot_id' => $slotDetail['exam_slot_id'],
                    ]);

                    foreach ($slotDetail['batch_courses'] as $batchCourse) {
                        $totalStudent = StudentEnrollmentCourse::where('academic_session_id', $data['academic_session_id'])
                            ->where('batch_id', $batchCourse['batch_id'])
                            ->where('course_id', $batchCourse['course_id'])->count();

                        $session->courses()->create([
                            'batch_id' => $batchCourse['batch_id'],
                            'course_id' => $batchCourse['course_id'],
                            'total_students' => $totalStudent ?? 0,
                        ]);
                    }
                }
            }

            return $exam->fresh([
                'sessions.courses',
            ]);
        });
    }

    public function update(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {

            $exam->update([
                'name' => $data['name'],
                'academic_session_id' => $data['academic_session_id'],
                'department_id' => $data['department_id'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => collect($data['schedules'])->max('date'),
                'algorithm_type' => $data['algorithm_type'],
            ]);

            // পুরাতন Schedule Remove
            $exam->sessions()->delete();

            // নতুন Schedule Insert
            foreach ($data['schedules'] as $schedule) {

                foreach ($schedule['slot_details'] as $slotDetail) {

                    $session = $exam->sessions()->create([
                        'exam_date' => $schedule['date'],
                        'exam_slot_id' => $slotDetail['exam_slot_id'],
                    ]);

                    foreach ($slotDetail['batch_courses'] as $batchCourse) {

                        $session->courses()->create([
                            'batch_id' => $batchCourse['batch_id'],
                            'course_id' => $batchCourse['course_id'],

                            'section_course_assignment_id' => $batchCourse['section_course_assignment_id'] ?? null,

                            'section_course_assignment_item_id' => $batchCourse['section_course_assignment_item_id'] ?? null,

                            'total_students' => 0,
                        ]);
                    }
                }
            }

            return $exam->fresh([
                'sessions.courses',
            ]);
        });
    }

    public function generateSeatPlan(ExamSession $session, array $roomIds)
    {
        return DB::transaction(function () use ($session, $roomIds) {
            $rooms = Room::whereIn('id', $roomIds)->get();
            $algorithm = $session->exam->algorithm_type;

            $engine = new RoomAllocationEngine;
            $allocations = $engine->allocate($session, $rooms, $algorithm);

            foreach ($allocations as $allocation) {
                // ১. exam_session_rooms এ ডাটা সেভ
                $sessionRoom = $session->sessionRooms()->create([
                    'room_id' => $allocation['room_id'],
                    'columns' => $allocation['columns'],
                    'allocated_students' => $allocation['allocated_students'],
                ]);

                // ২. exam_seats এ স্টুডেন্টদের ডাটা সেভ (Bulk Insert for performance)
                $seats = collect($allocation['seats'])->map(function ($seat) use ($sessionRoom) {
                    $seat['exam_session_room_id'] = $sessionRoom->id;
                    $seat['created_at'] = now();
                    $seat['updated_at'] = now();

                    return $seat;
                })->toArray();

                ExamSeat::insert($seats);
            }
        });
    }
}
