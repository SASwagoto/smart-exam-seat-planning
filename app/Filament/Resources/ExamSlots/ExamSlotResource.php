<?php

namespace App\Filament\Resources\ExamSlots;

use App\Filament\Resources\ExamSlots\Pages\ManageExamSlots;
use App\Models\ExamSlot;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ExamSlotResource extends Resource
{
    protected static ?string $model = ExamSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        // টাইমের পার্থক্য মিনিট হিসেব করার ফাংশন
        $calculateDuration = function (Get $get, Set $set) {
            $startTime = $get('start_time');
            $endTime = $get('end_time');

            if ($startTime && $endTime) {
                $start = Carbon::parse($startTime);
                $end = Carbon::parse($endTime);

                if ($end->lessThan($start)) {
                    $end->addDay();
                }

                $set('duration_minutes', $start->diffInMinutes($end));
            }
        };

        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Slot Name')
                    ->placeholder('e.g. Morning Shift')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TimePicker::make('start_time')
                    ->label('Start Time')
                    ->required()
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated($calculateDuration),

                Forms\Components\TimePicker::make('end_time')
                    ->label('End Time')
                    ->required()
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated($calculateDuration),

                Forms\Components\TextInput::make('duration_minutes')
                    ->label('Duration (Minutes)')
                    ->numeric()
                    ->disabled() // ইউজার এডিট করতে পারবে না
                    ->dehydrated() // ডাটাবেজে ডাটা সেভ হতে সাহায্য করবে
                    ->placeholder('Auto calculated')
                    ->suffix('mins')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Is Active?')
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Slot Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start')
                    ->time('h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('End')
                    ->time('h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' mins')
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
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
            'index' => ManageExamSlots::route('/'),
        ];
    }
}