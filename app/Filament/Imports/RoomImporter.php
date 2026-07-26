<?php

namespace App\Filament\Imports;

use App\Models\Room;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class RoomImporter extends Importer
{
    protected static ?string $model = Room::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('room_number')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('building')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('total_rows')
                ->numeric()
                ->rules(['required', 'integer', 'min:1']),

            ImportColumn::make('total_cols')
                ->numeric()
                ->rules(['required', 'integer', 'min:1']),

            ImportColumn::make('total_seats')
                ->numeric()
                ->rules(['nullable', 'integer']),

            ImportColumn::make('disabled_seats')
                ->castStateUsing(function ($state) {
                    if (blank($state)) {
                        return [];
                    }
                    if (is_string($state)) {
                        return array_map('trim', explode(',', $state));
                    }
                    return $state;
                }),

            ImportColumn::make('is_active')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): Room
    {
        return Room::firstOrNew([
            'room_number' => $this->data['room_number'],
        ]);
    }

    protected function beforeSave(): void
    {
        if (blank($this->record->total_seats) && $this->record->total_rows && $this->record->total_cols) {
            $this->record->total_seats = $this->record->total_rows * $this->record->total_cols;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your room import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
