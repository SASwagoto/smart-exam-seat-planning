<?php

namespace App\Filament\Resources\Exams\Tables;

use App\Models\Exam;
use App\Services\Exam\SeatPlanService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('academicSession.name')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
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
                ActionGroup::make([
                    // 🟢 ১. 'draft' স্ট্যাটাস থাকলে সিট প্ল্যান জেনারেট বাটন দেখাবে
                    Action::make('generate_seat_plan')
                        ->label('Generate Seat Plan')
                        ->icon('heroicon-o-cpu-chip')
                        ->color('success')
                        ->visible(fn (Exam $record): bool => $record->status === 'draft')
                        ->requiresConfirmation()
                        ->action(function (Exam $record) {
                            try {
                                app(\App\Services\Exam\SeatPlanService::class)->generate($record);

                                Notification::make()
                                    ->title('Seat Plan Generated Successfully!')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Generation Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    // 🟢 ২. 'scheduled' স্ট্যাটাস হলে রুটিন দেখার বাটন
                    Action::make('view_routine')
                        ->label('View Routine')
                        ->icon('heroicon-o-calendar')
                        ->color('info')
                        ->visible(fn (Exam $record): bool => $record->status === 'scheduled')
                        ->url(fn (Exam $record): string => route('exam-routine.show', $record))
                        ->openUrlInNewTab(),

                    // 🟢 ৩. 'scheduled' স্ট্যাটাস হলে সিট প্ল্যান দেখার বাটন
                    Action::make('view_seat_plan')
                        ->label('View Seat Plan')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->visible(fn (Exam $record): bool => $record->status === 'scheduled')
                        ->url(fn (Exam $record): string => route('exam.seat-plan', $record))
                        ->openUrlInNewTab(),

                    // 🟢 ৪. ডিলিট বাটন (শুধুমাত্র এটিই স্থায়ীভাবে থাকবে)
                    DeleteAction::make(),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('primary'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
