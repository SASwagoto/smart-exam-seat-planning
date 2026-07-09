<?php

namespace App\Filament\Resources\Batches;

use App\Filament\Resources\Batches\Pages\ManageBatches;
use App\Models\Batch;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; 
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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

                TextInput::make('batch_number')
                    ->label('Batch Number')
                    ->placeholder('e.g., 51st')
                    ->required(),

                Repeater::make('sections')
                    ->relationship('sections') 
                    ->schema([
                        TextInput::make('section_name')
                            ->label('Section Name')
                            ->placeholder('A')
                            ->required(),

                        TextInput::make('capacity')
                            ->label('Capacity')
                            ->numeric()
                            ->default(40)
                            ->required(),
                    ])
                    ->columns(2)
                    ->default([
                        ['section_name' => 'A', 'capacity' => 40]
                    ])
                    ->createItemButtonLabel('Add More Section')
                    ->columnSpanFull(),
            ]);
    }

    // ২. টেবিল কনফিগারেশন
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('batch_number')
                    ->label('Batch')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('sections.section_name')
                    ->label('Sections')
                    ->badge()
                    ->color('info')
                    ->separator(','),
            ])
            ->recordAction(null)
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Filter by Department'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBatches::route('/'),
        ];
    }
}