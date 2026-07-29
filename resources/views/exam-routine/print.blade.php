<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $exam->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            @page { size: A4 landscape; margin: 10mm; }
            body { background-color: white; }
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow p-8 text-center mb-8 border-t-4 border-blue-700">
        <h1 class="text-3xl font-bold uppercase text-gray-800">{{ $exam->department->name }}</h1>
        <h2 class="text-2xl text-blue-700 font-semibold mt-1">{{ $exam->name }}</h2>
        <p class="mt-1 text-lg text-gray-600">{{ $exam->academicSession->name }}</p>
        <p class="text-gray-500 font-medium">
            Exam Period: {{ $exam->start_date->format('d M Y') }} - {{ $exam->end_date->format('d M Y') }}
        </p>
    </div>

    @foreach($routine as $date => $sessions)
        <div class="mb-10">
            {{-- Date Header --}}
            <div class="bg-blue-700 text-white p-2.5 rounded shadow font-bold text-lg">
                {{ \Carbon\Carbon::parse($date)->format('d F Y (l)') }}
            </div>

            <div class="grid grid-cols-2 gap-6 mt-5">
                @foreach($sessions as $session)
                    <div class="bg-white border rounded shadow-sm overflow-hidden">
                        {{-- Slot Header --}}
                        <div class="bg-gray-800 text-white p-3 flex justify-between items-center">
                            <div class="font-bold uppercase tracking-wider">{{ $session->examSlot->name }}</div>
                            <div class="text-sm font-semibold text-gray-300">
                                {{ date('h:i A', strtotime($session->examSlot->start_time)) }} - {{ date('h:i A', strtotime($session->examSlot->end_time)) }}
                            </div>
                        </div>

                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-700 font-bold uppercase text-[11px]">
                                <tr>
                                    <th class="border p-2 text-left">Course & Code</th>
                                    <th class="border p-2">Batches</th>
                                    <th class="border p-2 text-center">Total Std.</th>
                                    <th class="border p-2">Rooms</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- কোর্স আইডি অনুযায়ী গ্রুপ করা --}}
                                @foreach($session->courses->groupBy('course_id') as $courseId => $courseBatches)
                                    @php
                                        $firstEntry = $courseBatches->first();
                                        // সব ব্যাচের নাম একসাথে করা
                                        $batchNames = $courseBatches->pluck('batch.batch_number')->unique()->sort()->join(', ');
                                        // সম্মিলিত স্টুডেন্ট সংখ্যা
                                        $totalStudents = $courseBatches->sum('total_students');
                                        // এই কোর্সের সবগুলো ব্যাচের জন্য বরাদ্দকৃত ইউনিক রুম লিস্ট
                                        $allRooms = $courseBatches->flatMap(function($cb) {
                                            return $cb->seats->map(fn($seat) => $seat->room->room->room_number);
                                        })->unique()->sort();
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2">
                                            <div class="font-bold text-gray-800">{{ $firstEntry->course->course_code }}</div>
                                            <div class="text-[10px] text-gray-500">{{ $firstEntry->course->course_title }}</div>
                                        </td>
                                        <td class="border p-2 text-center font-medium text-gray-600">
                                            {{ $batchNames }}
                                        </td>
                                        <td class="border p-2 text-center font-bold">
                                            {{ $totalStudents }}
                                        </td>
                                        <td class="border p-2 text-center">
                                            @forelse($allRooms as $roomNumber)
                                                <span class="inline-block bg-blue-50 text-blue-800 border border-blue-200 rounded px-1.5 py-0.5 text-[11px] font-bold m-0.5">
                                                    {{ $roomNumber }}
                                                </span>
                                            @empty
                                                <span class="text-red-400 text-xs italic">N/A</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<div class="fixed bottom-6 right-6 no-print">
    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-lg font-bold flex items-center gap-2">
        <span>🖨️</span> Print Official Routine
    </button>
</div>

</body>
</html>