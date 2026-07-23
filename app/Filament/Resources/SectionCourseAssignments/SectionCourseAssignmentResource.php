<?php

namespace App\Filament\Resources\SectionCourseAssignments;

use App\Filament\Resources\SectionCourseAssignments\Pages\CreateSectionCourseAssignment;
use App\Filament\Resources\SectionCourseAssignments\Pages\EditSectionCourseAssignment;
use App\Filament\Resources\SectionCourseAssignments\Pages\ListSectionCourseAssignments;
use App\Filament\Resources\SectionCourseAssignments\Schemas\SectionCourseAssignmentForm;
use App\Filament\Resources\SectionCourseAssignments\Tables\SectionCourseAssignmentsTable;
use App\Models\SectionCourseAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SectionCourseAssignmentResource extends Resource
{
    protected static ?string $model = SectionCourseAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SectionCourseAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectionCourseAssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSectionCourseAssignments::route('/'),
            'create' => CreateSectionCourseAssignment::route('/create'),
            'edit' => EditSectionCourseAssignment::route('/{record}/edit'),
        ];
    }
}
