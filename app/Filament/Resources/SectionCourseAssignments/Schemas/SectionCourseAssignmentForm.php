<?php

namespace App\Filament\Resources\SectionCourseAssignments\Schemas;

use App\Models\TeacherCourseAssignment;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SectionCourseAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academic_session_id')
                    ->relationship('academicSession', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                Select::make('batch_id')
                    ->relationship('batch', 'batch_number', function ($query, Get $get) {
                        $deptId = $get('department_id');
                        if (! $deptId) {
                            return $query->whereRaw('1 = 0');
                        }

                        return $query->where('department_id', $deptId);
                    })
                    ->disabled(fn (Get $get) => ! $get('department_id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                Select::make('section_id')
                    ->relationship('section', 'section_name', function ($query, Get $get) {
                        $batchId = $get('batch_id');
                        if (! $batchId) {
                            return $query->whereRaw('1 = 0');
                        }

                        return $query->where('batch_id', $batchId);
                    })
                    ->disabled(fn (Get $get) => ! $get('batch_id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Select::make('course_id')
                            ->relationship('course', 'course_title', function ($query, Get $get) {
                                $deptId = $get('../../department_id');
                                if (! $deptId) {
                                    return $query->whereRaw('1 = 0');
                                }

                                return $query->where('department_id', $deptId);
                            })
                            ->disabled(fn (Get $get) => ! $get('../../department_id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Select::make('teacher_id')
                            ->label('Teacher')
                            ->options(function (Get $get) {
                                $courseId = $get('course_id');

                                if (! $courseId) {
                                    return [];
                                }

                                // ১. parent context থেকে সেশন ও ডিপার্টমেন্ট আইডি নিরাপদে আনা
                                $sessionId = $get('../../academic_session_id');
                                $deptId = $get('../../department_id');

                                // ২. সরাসরি TeacherCourseAssignment থেকে টিচারদের আইডি ও নাম তুলে আনা
                                return TeacherCourseAssignment::query()
                                    ->where('course_id', (int) $courseId) // নিশ্চিত টাইপকাস্ট integer
                                    ->when($sessionId, fn ($q) => $q->where('academic_session_id', (int) $sessionId))
                                    ->when($deptId, fn ($q) => $q->where('department_id', (int) $deptId))
                                    ->join('teachers', 'teacher_course_assignments.teacher_id', '=', 'teachers.id')
                                    ->pluck('teachers.name', 'teachers.id')
                                    ->toArray();
                            })
                            ->disabled(fn (Get $get) => ! $get('course_id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->noOptionsMessage('No teacher assigned to this course in the selected session.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
