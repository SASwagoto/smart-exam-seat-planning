<?php

namespace App\Http\Controllers;

use App\Models\Exam;

class ExamRoutineController extends Controller
{
    // public function show(Exam $exam)
    // {
    //     // ১. সব প্রয়োজনীয় রিলেশন একসাথে লোড করা (Eager Loading)
    //     $exam->load([
    //         'department',
    //         'academicSession',
    //         'sessions' => function ($query) {
    //             $query->orderBy('exam_date', 'asc');
    //         },
    //         'sessions.examSlot',
    //         'sessions.courses.course',
    //         'sessions.courses.batch',
    //         'sessions.courses.sectionCourseAssignment.section', // সেকশন নাম পাওয়ার জন্য
    //         'sessions.rooms.room',
    //     ]);

    //     // ২. সেশনগুলোকে ডেট অনুযায়ী গ্রুপ করা এবং ব্লেডের ফরম্যাটে ডাটা সাজানো
    //     $routine = $exam->sessions->groupBy(function ($session) {
    //         return $session->exam_date->format('Y-m-d');
    //     })->map(function ($daySessions) {
    //         // প্রতিটি দিনের ভেতরে স্লট অনুযায়ী সাজানো
    //         return $daySessions->map(function ($session) {
    //             return (object) [
    //                 'slot_name' => $session->examSlot->name,
    //                 'start_time' => $session->examSlot->start_time,
    //                 'end_time' => $session->examSlot->end_time,
    //                 'courses' => $session->courses->map(function ($sc) {
    //                     return (object) [
    //                         'course_code' => $sc->course->course_code ?? $sc->course->code,
    //                         'section_name' => $sc->sectionCourseAssignment->section->name ?? 'N/A',
    //                         'batch_number' => $sc->batch->batch_number ?? $sc->batch->name,
    //                         'student_count' => $sc->total_students,
    //                     ];
    //                 }),
    //                 'rooms' => $session->rooms->map(function ($sr) {
    //                     return (object) [
    //                         'room_number' => $sr->room->room_number,
    //                     ];
    //                 }),
    //             ];
    //         });
    //     });

    //     return view('exam-routine.print', compact(
    //         'exam',
    //         'routine'
    //     ));

    // }
    
//     public function show(Exam $exam)
// {
//     $exam->load([
//         'department',
//         'academicSession',
//         'sessions' => fn($q) => $q->orderBy('exam_date'),
//         'sessions.examSlot',
//         'sessions.rooms.room',
//         'sessions.rooms.seats.sessionCourse.course',
//         'sessions.rooms.seats.sessionCourse.batch',
//         'sessions.rooms.seats.sessionCourse.sectionCourseAssignment.section',
//     ]);

//     // তারিখ অনুযায়ী গ্রুপ করা
//     $routine = $exam->sessions->groupBy(fn($s) => $s->exam_date->format('Y-m-d'));

//     return view('exam-routine.routine', compact('exam', 'routine'));
// }

public function show(Exam $exam)
{
    $exam->load([
        'department',
        'academicSession',
        'sessions' => fn($q) => $q->orderBy('exam_date'),
        'sessions.examSlot',
        'sessions.courses.course',
        'sessions.courses.batch',
        'sessions.courses.seats.room.room' 
    ]);

    
    $routine = $exam->sessions->groupBy(fn($s) => $s->exam_date->format('Y-m-d'));

    return view('exam-routine.print', compact('exam', 'routine'));
}

    public function printSeatPlan(Exam $exam)
    {
        $exam->load([
            'department',
            'academicSession',
            'sessions' => function ($query) {
                $query->orderBy('exam_date', 'asc');
            },
            'sessions.examSlot',
            'sessions.rooms.room',
            'sessions.rooms.seats.student',
            'sessions.rooms.seats.sessionCourse.course',
            'sessions.rooms.seats.sessionCourse.batch',
        ]);

        // সেশন অনুযায়ী রুমগুলোকে সাজানো
        $sessions = $exam->sessions;

        return view('exam-routine.seat-plan', compact('exam', 'sessions'));
    }
}
