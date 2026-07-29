<?php

use App\Http\Controllers\ExamRoutineController;
use App\Http\Controllers\SearchSeatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchSeatController::class, 'homepage']);
Route::get('/get-exams-filtered', [SearchSeatController::class, 'getExams']);
Route::post('/find-seat', [SearchSeatController::class, 'findSeat']);


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('admin/exam-routine/{exam}', [ExamRoutineController::class, 'show'])
        ->name('exam-routine.show');
    Route::get('admin/seat-plan/{exam}', [ExamRoutineController::class, 'printSeatPlan'])->name('exam.seat-plan');
});