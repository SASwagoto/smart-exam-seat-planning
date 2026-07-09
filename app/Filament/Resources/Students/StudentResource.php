<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\ManageStudents;
use App\Models\Student;
use App\Models\Batch;
use App\Models\Section;
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

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    
    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department')
                    ->searchable()
                    ->preload()
                    ->live() 
                    ->afterStateUpdated(fn (callable $set) => $set('batch_id', null)) 
                    ->required(),


                Select::make('batch_id')
                    ->label('Batch')
                    ->options(fn (callable $get) => 
                        Batch::where('department_id', $get('department_id'))
                            ->pluck('batch_number', 'id')
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('section_id', null)) 
                    ->required()
                    ->disabled(fn (callable $get) => !$get('department_id')),

                
                Select::make('section_id')
                    ->label('Section (Optional)')
                    ->options(fn (callable $get) => 
                        Section::where('batch_id', $get('batch_id'))
                            ->pluck('section_name', 'id')
                    )
                    ->searchable()
                    ->placeholder('Auto Assign')
                    ->disabled(fn (callable $get) => !$get('batch_id')),

                
                TextInput::make('student_id')
                    ->label('Student ID / Roll')
                    ->placeholder('e.g., 2021160001')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(50),

                
                TextInput::make('name')
                    ->label('Student Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),


                TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->maxLength(20),


                Select::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Suspended' => 'Suspended',
                        'Dropped' => 'Dropped',
                    ])
                    ->default('Active')
                    ->required(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_id')
                    ->label('ID')
                    ->fontFamily('mono')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('department.code')
                    ->label('Dept')
                    ->sortable(),

                TextColumn::make('batch.batch_number')
                    ->label('Batch')
                    ->sortable(),

                TextColumn::make('section.section_name')
                    ->label('Sec')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->default('-'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Suspended' => 'warning',
                        'Dropped' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Filter by Dept'),

                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Suspended' => 'Suspended',
                        'Dropped' => 'Dropped',
                    ]),
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
            'index' => ManageStudents::route('/'),
        ];
    }
}