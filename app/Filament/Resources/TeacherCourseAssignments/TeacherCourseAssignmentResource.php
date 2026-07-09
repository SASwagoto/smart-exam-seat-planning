<?php

namespace App\Filament\Resources\TeacherCourseAssignments;

use App\Filament\Resources\TeacherCourseAssignments\Pages\ManageTeacherCourseAssignments;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Section;
use App\Models\TeacherCourseAssignment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeacherCourseAssignmentResource extends Resource
{
    protected static ?string $model = TeacherCourseAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('academic_session_id')
                ->label('Academic Session')
                ->relationship('academicSession', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Select::make('department_id')
                ->label('Department')
                ->relationship('department', 'name')
                ->live()
                ->searchable()
                ->preload()
                ->required()
                ->afterStateUpdated(function (callable $set) {
                    $set('batch_id', null);
                    $set('section_id', null);
                }),

            Select::make('batch_id')
                ->label('Batch')
                ->options(fn ($get) => Batch::query()
                    ->where('department_id', $get('department_id'))
                    ->pluck('batch_number', 'id'))
                ->live()
                ->searchable()
                ->required()
                ->afterStateUpdated(function (callable $set) {
                    $set('section_id', null);
                }),

            Select::make('section_id')
                ->label('Section')
                ->options(fn ($get) => Section::query()
                    ->where('batch_id', $get('batch_id'))
                    ->pluck('section_name', 'id'))
                ->searchable()
                ->required(),

            Select::make('course_id')
                ->label('Course')
                ->options(fn (callable $get) => Course::query()
                    ->where('department_id', $get('department_id'))
                    ->orderBy('course_title')
                    ->pluck('course_title', 'id'))
                ->searchable()
                ->preload()
                ->required(),

            Select::make('teacher_id')
                ->relationship(
                    'teacher',
                    'name',
                    fn ($query) => $query->orderBy('name')
                )
                ->getOptionLabelFromRecordUsing(
                    fn ($record) => "{$record->teacher_id} | {$record->name}"
                )
                ->searchable(['teacher_id', 'name'])
                ->preload()
                ->required(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('academicSession.name')
                    ->label('Session')
                    ->searchable(),

                TextColumn::make('department.code')
                    ->label('Department')
                    ->badge(),

                TextColumn::make('batch.batch_number')
                    ->label('Batch')
                    ->badge(),

                TextColumn::make('section.section_name')
                    ->label('Section')
                    ->badge(),

                TextColumn::make('course.course_name')
                    ->label('Course')
                    ->searchable(),

                TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable(),

            ])
            ->recordAction(null)
            ->actions([
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
            'index' => ManageTeacherCourseAssignments::route('/'),
        ];
    }
}
