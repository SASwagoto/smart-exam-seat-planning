<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Support\Facades\DB;

class ExamRoutineController extends Controller
{
    public function show(Exam $exam)
    {
        $routine = DB::table('exam_schedules as es')
            ->join('exam_slots as slot', 'slot.id', '=', 'es.exam_slot_id')
            ->leftJoin('exam_schedule_courses as esc', 'esc.exam_schedule_id', '=', 'es.id')
            ->leftJoin('section_course_assignments as sca', 'sca.id', '=', 'esc.section_course_assignment_id')
            ->leftJoin('section_course_assignment_items as scai', 'scai.section_course_assignment_id', '=', 'sca.id')
            ->leftJoin('courses as c', 'c.id', '=', 'scai.course_id')
            ->leftJoin('sections as s', 's.id', '=', 'sca.section_id')
            ->leftJoin('batches as b', 'b.id', '=', 'esc.batch_id')
            ->where('es.exam_id', $exam->id)
            ->select(
                'es.id as schedule_id',
                'es.date',

                'slot.name as slot_name',
                'slot.start_time',
                'slot.end_time',

                'c.course_code as course_code',
                'c.course_title  as course_name',

                'b.batch_number',
                's.section_name',

                'esc.student_count'
            )
            ->orderBy('es.date')
            ->orderBy('slot.start_time')
            ->get();

        $rooms = DB::table('exam_schedule_rooms as esr')
            ->join('rooms as r', 'r.id', '=', 'esr.room_id')

            ->select(
                'esr.exam_schedule_id',
                'r.room_number'
            )
            ->get()
            ->groupBy('exam_schedule_id');

        $routine = $routine
            ->groupBy('date')
            ->map(function ($day) use ($rooms) {

                return $day->groupBy('schedule_id')
                    ->map(function ($schedule) use ($rooms) {

                        $first = $schedule->first();

                        return (object) [
                            'schedule_id' => $first->schedule_id,

                            'slot_name' => $first->slot_name,
                            'start_time' => $first->start_time,
                            'end_time' => $first->end_time,

                            'courses' => $schedule,

                            'rooms' => $rooms[$first->schedule_id] ?? collect(),
                        ];
                    });
            });

        return view('exam-routine.print', compact(
            'exam',
            'routine'
        ));

    }
}
