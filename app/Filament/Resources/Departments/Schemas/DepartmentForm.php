<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ১. ডিপার্টমেন্ট এর নাম ইনপুট ফিল্ড
                TextInput::make('name')
                    ->label('Department Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Computer Science & Engineering'),

                // ২. ডিপার্টমেন্ট এর শর্ট কোড ইনপুট ফিল্ড
                TextInput::make('code')
                    ->label('Department Code')
                    ->required()
                    ->unique(table: 'departments', ignoreRecord: true) // ডুপ্লিকেট এন্ট্রি এবং এডিটের সময় নিজের কোড ইগনোর করবে
                    ->maxLength(10)
                    ->placeholder('e.g., CSE'),
            ])
            ->columns(1);
    }
}
