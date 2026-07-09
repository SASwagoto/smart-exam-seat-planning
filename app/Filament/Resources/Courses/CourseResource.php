<?php

namespace App\Filament\Resources\Courses;

use App\Filament\Resources\Courses\Pages\ManageCourses;
use App\Models\Course;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

  
    protected static ?string $recordTitleAttribute = 'course_title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department')
                    ->searchable()
                    ->preload()
                    ->required(),


                TextInput::make('course_code')
                    ->label('Course Code')
                    ->placeholder('e.g., CSE-111')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(50),


                TextInput::make('course_title')
                    ->label('Course Title')
                    ->placeholder('e.g., Structured Programming')
                    ->required()
                    ->maxLength(255),


                TextInput::make('credit')
                    ->label('Course Credit')
                    ->numeric()
                    ->default(3.00)
                    ->minValue(0)
                    ->maxValue(6)
                    ->required(),

                
                Select::make('type')
                    ->label('Course Type')
                    ->options([
                        'Theory' => 'Theory',
                        'Lab' => 'Lab / Sessional',
                    ])
                    ->default('Theory')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course_code')
                    ->label('Code')
                    ->fontFamily('mono') 
                    ->sortable()
                    ->searchable(),

                TextColumn::make('course_title')
                    ->label('Title')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('department.code')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('credit')
                    ->label('Credit')
                    ->alignCenter(),


                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Theory' => 'warning',
                        'Lab' => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),
            ])
            ->filters([

                \Filament\Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Filter by Department'),
                

                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'Theory' => 'Theory',
                        'Lab' => 'Lab',
                    ])
                    ->label('Filter by Type'),
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
            'index' => ManageCourses::route('/'),
        ];
    }
}