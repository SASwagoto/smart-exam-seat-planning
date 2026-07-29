<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Routine - {{ $exam->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none; }
            @page { size: A4 landscape; margin: 8mm; }
            body { background-color: white; padding: 0; margin: 0; }
            .page-break { page-break-after: always; }
            .card { border: 1px solid #e5e7eb; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 pb-20">

    <div class="max-w-[1100px] mx-auto py-8 px-4">

        {{-- Main Header --}}
        <div class="bg-white border-t-4 border-blue-700 rounded-xl shadow-sm p-6 mb-8 text-center relative">
            <div class="absolute top-4 left-4 opacity-10 no-print">
                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.937 7.937 0 0112 4c.483 0 .947.055 1.391.159l-4.391 4.391V4.804zM2 18a.5.5 0 01.5-.5h2a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-2a.5.5 0 01-.5-.5v-1zM5 13a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1H6a1 1 0 01-1-1v-2z"></path></svg>
            </div>
            <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight">{{ $exam->department->name }}</h1>
            <h2 class="text-xl font-bold text-blue-700 mt-1">{{ $exam->name }}</h2>
            <div class="flex justify-center gap-6 mt-3 text-sm font-semibold text-gray-600 italic">
                <span>Academic Session: {{ $exam->academicSession->name }}</span>
                <span>•</span>
                <span>Duration: {{ $exam->start_date->format('M d') }} - {{ $exam->end_date->format('M d, Y') }}</span>
            </div>
        </div>

        @foreach($routine as $date => $sessions)
            <div class="page-break mb-10">
                {{-- Date Header --}}
                <div class="flex items-center gap-4 mb-4">
                    <div class="bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md font-bold text-lg">
                        {{ \Carbon\Carbon::parse($date)->format('d F, Y') }}
                    </div>
                    <div class="flex-1 h-px bg-blue-200"></div>
                    <div class="text-blue-800 font-bold uppercase tracking-widest text-sm">
                        {{ \Carbon\Carbon::parse($date)->format('l') }}
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    @foreach($sessions as $session)
                        <div class="bg-white border rounded-xl overflow-hidden shadow-sm card">
                            {{-- Slot Info --}}
                            <div class="bg-gray-800 text-white px-4 py-3 flex justify-between items-center">
                                <div>
                                    <span class="text-yellow-400 font-bold uppercase text-xs tracking-widest">Shift / Slot</span>
                                    <h3 class="text-lg font-bold">{{ $session->examSlot->name }}</h3>
                                </div>
                                <div class="text-right">
                                    <span class="text-gray-400 font-bold uppercase text-xs tracking-widest">Time Duration</span>
                                    <p class="text-lg font-bold">
                                        {{ date('h:i A', strtotime($session->examSlot->start_time)) }} - {{ date('h:i A', strtotime($session->examSlot->end_time)) }}
                                    </p>
                                </div>
                            </div>

                            <div class="p-0">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-blue-50 text-blue-900 uppercase text-[11px] font-bold">
                                            <th class="px-4 py-3 border-b border-blue-100">Room Info</th>
                                            <th class="px-4 py-3 border-b border-blue-100">Course & Code</th>
                                            <th class="px-4 py-3 border-b border-blue-100 text-center">Batch</th>
                                            <th class="px-4 py-3 border-b border-blue-100 text-center">Sec</th>
                                            <th class="px-4 py-3 border-b border-blue-100 text-center">Students</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm">
                                        @foreach($session->rooms as $sessionRoom)
                                            @php
                                                // এক রুমে একাধিক কোর্স থাকতে পারে, তাই গ্রুপিং
                                                $roomSeats = $sessionRoom->seats->groupBy('exam_session_course_id');
                                                $rowCount = $roomSeats->count();
                                            @endphp

                                            @foreach($roomSeats as $sessionCourseId => $seats)
                                                @php $firstSeat = $seats->first(); @endphp
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    @if($loop->first)
                                                        <td class="px-4 py-3 border-b font-black text-gray-700 bg-gray-50/50" rowspan="{{ $rowCount }}">
                                                            <div class="text-blue-700 text-base">{{ $sessionRoom->room->room_number }}</div>
                                                            <div class="text-[10px] text-gray-500 uppercase">{{ $sessionRoom->room->building }}</div>
                                                            <div class="mt-1 inline-block bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px]">
                                                                Total: {{ $sessionRoom->allocated_students }}
                                                            </div>
                                                        </td>
                                                    @endif
                                                    <td class="px-4 py-3 border-b">
                                                        <div class="font-bold text-gray-800">{{ $firstSeat->sessionCourse->course->course_title }}</div>
                                                        <div class="text-xs text-blue-600 font-mono">{{ $firstSeat->sessionCourse->course->course_code }}</div>
                                                    </td>
                                                    <td class="px-4 py-3 border-b text-center font-semibold text-gray-700">
                                                        {{ $firstSeat->sessionCourse->batch->batch_number }}
                                                    </td>
                                                    <td class="px-4 py-3 border-b text-center">
                                                        <span class="bg-gray-100 px-2 py-1 rounded text-xs font-bold text-gray-600">
                                                            {{ $firstSeat->sessionCourse->sectionCourseAssignment->section->name ?? 'A' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 border-b text-center font-bold">
                                                        {{ $seats->count() }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Footer for Print --}}
        <div class="mt-12 flex justify-between items-end border-t-2 border-gray-200 pt-8">
            <div class="text-center">
                <div class="w-32 h-px bg-black mb-2 mx-auto"></div>
                <p class="text-xs font-bold uppercase">Prepared By</p>
            </div>
            <div class="text-center">
                <p class="text-[10px] text-gray-400 mb-10">System Generated Routine: {{ now()->format('d M Y, h:i A') }}</p>
                <div class="w-48 h-px bg-black mb-2 mx-auto"></div>
                <p class="text-xs font-bold uppercase">Controller of Examinations</p>
            </div>
        </div>
    </div>

    {{-- Print Button --}}
    <div class="fixed bottom-6 right-6 no-print">
        <button onclick="window.print()" class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-4 rounded-full shadow-2xl font-bold flex items-center gap-3 transition-transform hover:scale-105">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Official Routine
        </button>
    </div>

</body>
</html>