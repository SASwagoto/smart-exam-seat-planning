<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamSeat;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchSeatController extends Controller
{
    public function homepage()
    {
        $departments = Department::all();
        $semesters = AcademicSession::all(); // Academic Session কে সেমিস্টার হিসেবে দেখানো হচ্ছে
        return view('homepage', compact('departments', 'semesters'));
    }

    public function getExams(Request $request)
    {
        // ডিপার্টমেন্ট এবং সেমিস্টার (Academic Session) ফিল্টার করে এক্সাম গেট করা
        $exams = Exam::where('department_id', $request->dept_id)
            ->where('academic_session_id', $request->semester_id)
            ->get(['id', 'name']);
            
        return response()->json($exams);
    }

    public function findSeat(Request $request)
    {
        $request->validate([
            'exam_id' => 'required',
            'student_id' => 'required',
        ]);

        $student = Student::where('student_id', $request->student_id)->first();

        if (!$student) {
            return response()->json(['error' => 'Student ID not found!'], 404);
        }

        $seats = ExamSeat::where('student_id', $student->id)
            ->whereHas('sessionCourse.session', function($q) use ($request) {
                $q->where('exam_id', $request->exam_id);
            })
            ->with(['sessionCourse.course', 'room.room', 'sessionCourse.session.examSlot', 'sessionCourse.session'])
            ->get();

        if ($seats->isEmpty()) {
            return response()->json(['error' => 'No seat allocation found for this exam!'], 404);
        }

        return response()->json([
            'student_name' => $student->name,
            'student_id' => $student->student_id,
            'seats' => $seats
        ]);
    }
}