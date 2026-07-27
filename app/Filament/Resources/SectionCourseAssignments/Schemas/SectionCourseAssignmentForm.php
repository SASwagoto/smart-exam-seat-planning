<?php

namespace App\Filament\Resources\SectionCourseAssignments\Schemas;

use App\Models\Course;
use App\Models\Section;
use App\Models\SectionCourseAssignment;
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
                    ->label('Section')
                    ->options(function (Get $get, ?SectionCourseAssignment $record) {
                        $batchId = $get('batch_id');

                        if (! $batchId) {
                            return [];
                        }

                        $sections = Section::where('batch_id', $batchId)->get();

                        return $sections->mapWithKeys(function ($section) use ($get, $record) {

                            $query = SectionCourseAssignment::query()
                                ->where('academic_session_id', $get('academic_session_id'))
                                ->where('department_id', $get('department_id'))
                                ->where('batch_id', $get('batch_id'))
                                ->where('section_id', $section->id);

                            if ($record) {
                                $query->whereKeyNot($record->id);
                            }

                            $label = $section->section_name;

                            if ($query->exists()) {
                                $label .= ' 🔒 (Already Assigned)';
                            }

                            return [
                                $section->id => $label,
                            ];
                        })->toArray();
                    })
                    ->disableOptionWhen(function ($value, Get $get, ?SectionCourseAssignment $record) {

                        $query = SectionCourseAssignment::query()
                            ->where('academic_session_id', $get('academic_session_id'))
                            ->where('department_id', $get('department_id'))
                            ->where('batch_id', $get('batch_id'))
                            ->where('section_id', $value);

                        // Edit page হলে নিজের record বাদ দিবে
                        if ($record) {
                            $query->whereKeyNot($record->id);
                        }

                        return $query->exists();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'course_title', function ($query, Get $get) {
                                $deptId = $get('../../department_id');
                                if (! $deptId) {
                                    return $query->whereRaw('1 = 0');
                                }

                                // 🎯 ১. রিপিটারে ইতোমধ্যে যেসব কোর্স সিলেক্ট করা হয়েছে সেগুলোর আইডি বের করা
                                $selectedCourses = collect($get('../../items'))
                                    ->pluck('course_id')
                                    ->filter()
                                    ->toArray();

                                // 🎯 ২. বর্তমান রো-এর সিলেক্ট করা কোর্স আইডি বের করা (যাতে বর্তমান রো-তে সেটা দেখায়)
                                $currentCourseId = $get('course_id');

                                return $query->where('department_id', $deptId)
                                    ->where(function ($q) use ($selectedCourses, $currentCourseId) {
                                        $q->whereNotIn('id', $selectedCourses);

                                        // বর্তমান রো-এর নির্বাচিত কোর্সটি যেন লিস্টে দৃশ্যমান থাকে
                                        if ($currentCourseId) {
                                            $q->orWhere('id', $currentCourseId);
                                        }
                                    });
                            })
                            ->getOptionLabelFromRecordUsing(fn (Course $record) => "{$record->course_code} | {$record->course_title} ({$record->credit})")
                            ->searchable(['course_code', 'course_title'])
                            ->disabled(fn (Get $get) => ! $get('../../department_id'))
                            ->preload()
                            ->live()
                            ->distinct() // 🎯 ৩. সার্ভার লেভেল ডুপ্লিকেট ভ্যালিডেশন আটকাবে
                            ->required(),

                        Select::make('teacher_id')
                            ->label('Teacher')
                            ->options(function (Get $get) {
                                $courseId = $get('course_id');

                                if (! $courseId) {
                                    return [];
                                }

                                $sessionId = $get('../../academic_session_id');
                                $deptId = $get('../../department_id');

                                return TeacherCourseAssignment::query()
                                    ->where('course_id', (int) $courseId)
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
