<?php

use App\Http\Controllers\ExamRoutineController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('admin/exam-routine/{exam}', [ExamRoutineController::class, 'show'])
        ->name('exam-routine.show');
    Route::get('admin/seat-plan/{exam}', [ExamRoutineController::class, 'printSeatPlan'])->name('exam.seat-plan');
});