<?php

namespace App\Filament\Resources\Rooms\Tables;

use App\Models\Room;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room_number')
                    ->label('Room Name/No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('building')
                    ->label('Building')
                    ->searchable()
                    ->placeholder('N/A'),

                TextColumn::make('total_rows')
                    ->label('Rows')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_cols')
                    ->label('Cols')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_seats')
                    ->label('Total Capacity')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('effective_capacity')
                    ->label('Effective Seats')
                    ->badge()
                    ->color('success')
                    ->description(function (Room $record): string {
                        $disabledCount = is_array($record->disabled_seats) ? count($record->disabled_seats) : 0;
                        return "Disabled: {$disabledCount}";
                    }),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordUrl(null)
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
}