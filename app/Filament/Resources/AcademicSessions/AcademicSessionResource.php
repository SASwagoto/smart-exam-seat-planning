<?php

namespace App\Filament\Resources\AcademicSessions;

use App\Filament\Resources\AcademicSessions\Pages\ManageAcademicSessions;
use App\Models\AcademicSession;
use App\Models\Semester;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AcademicSessionResource extends Resource
{
    protected static ?string $model = AcademicSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Academic Sessions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('semester_id')
                ->label('Semester')
                ->relationship('semester', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {

                    $semester = Semester::find($state);

                    if ($semester && $get('year')) {
                        $set('name', $semester->name . ' ' . $get('year'));
                    }

                }),

            TextInput::make('year')
                ->numeric()
                ->minValue(2020)
                ->maxValue(2100)
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {

                    $semester = Semester::find($get('semester_id'));

                    if ($semester) {
                        $set('name', $semester->name . ' ' . $state);
                    }

                }),

            TextInput::make('name')
                ->disabled()
                ->dehydrated()
                ->required(),

            DatePicker::make('start_date')
                ->required(),

            DatePicker::make('end_date')
                ->required()
                ->after('start_date'),

            Select::make('status')
                ->options([
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                ])
                ->default('Active')
                ->required(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('semester.name')
                    ->badge(),

                TextColumn::make('year')
                    ->sortable(),

                TextColumn::make('start_date')
                    ->date(),

                TextColumn::make('end_date')
                    ->date(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        default => 'gray',
                    }),

            ])
            ->filters([
                //
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
            'index' => ManageAcademicSessions::route('/'),
        ];
    }
}