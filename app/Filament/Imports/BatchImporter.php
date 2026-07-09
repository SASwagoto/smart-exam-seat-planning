<?php

namespace App\Filament\Imports;

use App\Models\Batch;
use App\Models\Department;
use App\Models\Section;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class BatchImporter extends Importer
{
    protected static ?string $model = Batch::class;

    // সেকশনের ডাটাগুলো সাময়িকভাবে ধরে রাখার জন্য দুটি ভেরিয়েবল
    protected array $importedSections = [];
    protected array $importedCapacities = [];

    public static function getColumns(): array
    {
        return [
            // ১. ডিপার্টমেন্ট
            ImportColumn::make('department')
                ->requiredMapping()
                ->relationship(resolveUsing: function (string $state): ?Department {
                    return Department::where('code', '=', $state)
                        ->orWhere('name', 'like', "%{$state}%")
                        ->first();
                })
                ->rules(['required']),

            // ২. ব্যাচ নম্বর
            ImportColumn::make('batch_number')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            // ৩. সেকশন নেম রিড করে ভেরিয়েবলে রাখা
            ImportColumn::make('section_names')
                ->fillRecordUsing(function (BatchImporter $importer, $state) {
                    $importer->importedSections = explode(',', trim($state, " '\"\t\n\r\0\x0B"));
                }),

            // ৪. সেকশন ক্যাপাসিটি রিড করে ভেরিয়েবলে রাখা
            ImportColumn::make('section_capacities')
                ->fillRecordUsing(function (BatchImporter $importer, $state) {
                    $importer->importedCapacities = explode(',', trim($state, " '\"\t\n\r\0\x0B"));
                }),
        ];
    }

    public function resolveRecord(): Batch
    {
        return Batch::firstOrNew([
            'batch_number' => $this->data['batch_number'],
            'department_id' => $this->getRecord()->department_id ?? null,
        ]);
    }

    // 🔥 ফিলামেন্টের অফিশিয়াল সেভ হুক
    protected function afterSave(): void
    {
        $batch = $this->getRecord();

        // ভেরিয়েবল থেকে ডাটা না পেলে সরাসরি `data` অ্যারে থেকে ট্রাই করবে (ডাবল প্রোটেকশন)
        $names = !empty($this->importedSections) ? $this->importedSections : 
                 (isset($this->data['section_names']) ? explode(',', trim($this->data['section_names'], " '\"\t\n\r\0\x0B")) : ['A']);

        $capacities = !empty($this->importedCapacities) ? $this->importedCapacities : 
                      (isset($this->data['section_capacities']) ? explode(',', trim($this->data['section_capacities'], " '\"\t\n\r\0\x0B")) : [40]);

        foreach ($names as $index => $name) {
            $name = trim($name, " '\"\t\n\r\0\x0B");
            if (empty($name)) continue;

            $capacityRaw = isset($capacities[$index]) ? trim($capacities[$index], " '\"\t\n\r\0\x0B") : '40';
            $capacity = (int) $capacityRaw;

            // সরাসরি ডাটাবেসে ফোর্স ইনসার্ট/আপডেট
            Section::updateOrCreate(
                [
                    'batch_id' => $batch->id,
                    'section_name' => $name
                ],
                [
                    'capacity' => $capacity > 0 ? $capacity : 40
                ]
            );
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your batch import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}