<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12) 
            ->components([
                Section::make('Room Details')
                    ->columnSpan([
                        'default' => 12,
                        'lg' => 4, 
                    ])
                    ->schema([
                        TextInput::make('room_number')
                            ->label('Room Number / Name')
                            ->placeholder('e.g. Room-101')
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('building')
                            ->label('Building Name')
                            ->placeholder('e.g. Science Building'),

                        Grid::make(2)->schema([
                            TextInput::make('total_rows')
                                ->label('Total Rows')
                                ->numeric()
                                ->default(5)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($get, $set) {
                                    $rows = (int) $get('total_rows');
                                    $cols = (int) $get('total_cols');
                                    $set('total_seats', $rows * $cols);
                                }),

                            TextInput::make('total_cols')
                                ->label('Total Cols')
                                ->numeric()
                                ->default(4)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($get, $set) {
                                    $rows = (int) $get('total_rows');
                                    $cols = (int) $get('total_cols');
                                    $set('total_seats', $rows * $cols);
                                }),
                        ]),

                        TextInput::make('total_seats')
                            ->label('Total Seats (Capacity)')
                            ->numeric()
                            ->placeholder('Auto calculated')
                            ->helperText('Rows x Cols (Auto Calculated)'),

                        TagsInput::make('disabled_seats')
                            ->label('Disabled Seats')
                            ->placeholder('e.g. R1-C2')
                            ->helperText('Click a seat on the right to assign it, or assign it manually.'),

                        Toggle::make('is_active')
                            ->label('Is Active?')
                            ->default(true),
                    ]),

               
                Section::make('Seat Plan Preview')
                    ->columnSpan([
                        'default' => 12,
                        'lg' => 8,
                    ])
                    ->schema([
                        ViewField::make('seat_interactive_layout')
                            ->view('livewire.room-seat-picker')
                            ->registerActions([

                            ])
                            ->viewData(function ($get) {
                                return [
                                    'rows' => (int) ($get('total_rows') ?? 5),
                                    'cols' => (int) ($get('total_cols') ?? 4),
                                    'disabled' => $get('disabled_seats') ?? [],
                                ];
                            }),
                    ]),

            ]);
    }
}
