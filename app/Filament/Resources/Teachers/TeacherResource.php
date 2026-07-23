<?php

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages\ManageTeachers;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\TeacherCourseAssignment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('teacher_id')
                ->label('Teacher ID')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),

            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('phone')
                ->tel()
                ->maxLength(20),

            TextInput::make('designation')
                ->maxLength(100),

            Select::make('status')
                ->options([
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                ])
                ->default('Active')
                ->required(),

            // 🔥 টিচার ক্রিয়েটের সময় ডিপার্টমেন্ট ফিল্টার করে কোর্স সিলেক্টের অপশন
            Select::make('department_id')
                ->label('Department (For Course Assignment)')
                ->options(Department::pluck('name', 'id'))
                ->live()
                ->searchable()
                ->dehydrated(false), // এটি Teacher টেবিলে সেভ হবে না

            Select::make('academic_session_id')
                ->label('Academic Session')
                ->options(AcademicSession::pluck('name', 'id'))
                ->live()
                ->searchable()
                ->dehydrated(false),

            CheckboxList::make('course_ids')
                ->label('Assign Courses')
                ->options(fn ($get) => Course::query()
                    ->when($get('department_id'), fn ($q, $deptId) => $q->where('department_id', $deptId))
                    ->get()
                    ->pluck('course_title', 'id'))
                ->columns(1)
                ->searchable()
                ->bulkToggleable()
                ->dehydrated(false)
                ->visible(fn ($get) => filled($get('department_id')) && filled($get('academic_session_id'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher_id')
                    ->label('Teacher ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('phone'),

                TextColumn::make('designation')
                    ->searchable(),

                // 🔥 অ্যাসাইন করা কোর্সগুলো টেবিলে ব্যাজ আকারে দেখানোর ব্যবস্থা
                TextColumn::make('courses.course_code')
                    ->label('Assigned Courses')
                    ->badge()
                    ->separator(','),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                    ]),
            ])
            ->recordAction(null)
            ->actions([
                // 🔥 ড্রপডাউন অ্যাকশন গ্রূপ (Edit, Assign Course, Delete)
                ActionGroup::make([
                    EditAction::make(),

                    // 🔥 অ্যাসাইন কোর্স ড্রপডাউন মোডাল
                    Action::make('assignCourses')
                        ->label('Assign Course')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->form([
                            Select::make('academic_session_id')
                                ->label('Academic Session')
                                ->options(AcademicSession::pluck('name', 'id'))
                                ->searchable()
                                ->required(),

                            Select::make('department_id')
                                ->label('Department')
                                ->options(Department::pluck('name', 'id'))
                                ->live()
                                ->searchable()
                                ->required(),

                            CheckboxList::make('course_ids')
                                ->label('Select Courses')
                                ->options(fn ($get) => Course::query()
                                    ->where('department_id', $get('department_id'))
                                    ->get()
                                    ->pluck('course_title', 'id'))
                                ->columns(1)
                                ->searchable()
                                ->bulkToggleable()
                                ->required(),
                        ])
                        // মোডাল ওপেন হলে কারেন্টলি অ্যাসাইন করা ডাটা প্রিপপ্যুলেট করা
                        ->fillForm(function (Teacher $record): array {
                            return [
                                'course_ids' => $record->courses->pluck('id')->toArray(),
                            ];
                        })
                        ->action(function (Teacher $record, array $data): void {
                            // নতুন সিলেক্ট করা কোর্সগুলো সিঙ্ক করা (আগেরগুলো বাদ দিতে চাইলে টিক তুলে দিলেই বাদ হয়ে যাবে)
                            $courseIds = $data['course_ids'] ?? [];
                            
                            // ডিলিট ও সিঙ্ক একসাথে করার জন্য
                            TeacherCourseAssignment::where('teacher_id', $record->id)
                                ->where('academic_session_id', $data['academic_session_id'])
                                ->delete();

                            foreach ($courseIds as $courseId) {
                                TeacherCourseAssignment::create([
                                    'academic_session_id' => $data['academic_session_id'],
                                    'department_id'       => $data['department_id'],
                                    'teacher_id'          => $record->id,
                                    'course_id'           => $courseId,
                                ]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Courses updated for this teacher successfully!')
                                ->send();
                        }),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTeachers::route('/'),
        ];
    }
}