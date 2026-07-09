<?php

namespace App\Filament\Resources\StudentEnrollments;

use App\Filament\Resources\StudentEnrollments\Pages\ManageStudentEnrollments;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Section;
use App\Models\StudentEnrollment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StudentEnrollmentResource extends Resource
{
    protected static ?string $model = StudentEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('academic_session_id')
                ->relationship('academicSession', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Select::make('department_id')
                ->relationship('department', 'name')
                ->live()
                ->searchable()
                ->preload()
                ->required()
                ->afterStateUpdated(fn ($set) => [
                    $set('batch_id', null),
                    $set('section_id', null),
                    $set('student_ids', []),
                ]),

            Select::make('batch_id')
                ->options(fn ($get) => Batch::where('department_id', $get('department_id'))
                    ->pluck('batch_number', 'id'))
                ->live()
                ->searchable()
                ->required()
                ->afterStateUpdated(fn ($set) => [
                    $set('section_id', null),
                    $set('student_ids', []),
                ]),

            Select::make('section_id')
                ->options(fn ($get) => Section::where('batch_id', $get('batch_id'))
                    ->pluck('section_name', 'id'))
                ->live()
                ->searchable()
                ->required(),
            CheckboxList::make('course_ids')
                ->label('Courses')
                ->options(fn ($get) => Course::query()
                    ->where('department_id', $get('department_id'))
                    ->orderBy('course_code')
                    ->get()
                    ->mapWithKeys(fn ($course) => [
                        $course->id => "{$course->course_code} - {$course->course_name}",
                    ]))
                ->columns(2)
                ->searchable()
                ->bulkToggleable()
                ->live()
                ->required(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ManageStudentEnrollments::route('/'),
        ];
    }
}
