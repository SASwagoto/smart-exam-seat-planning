<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seat Plan - {{ $exam->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            @page { size: A4 portrait; margin: 8mm; }
            .page-break { page-break-after: always; }
        }
        body { font-family: 'Inter', sans-serif; }
        .seat-card { height: 42px; transition: all 0.2s; }
        .id-font { font-size: 10px; font-weight: 800; }
        .course-font { font-size: 8px; font-weight: 600; color: #4b5563; }
        .row-num { font-size: 7px; color: #9ca3af; position: absolute; top: 1px; left: 2px; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="fixed bottom-6 right-6 no-print">
        <button onclick="window.print()" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-3 rounded-full shadow-2xl font-bold flex items-center gap-2">
            <span>🖨️</span> Print Seat Plan
        </button>
    </div>

    @foreach($sessions as $session)
        @foreach($session->rooms as $sessionRoom)
            <div class="page-break bg-white mx-auto mb-6 shadow-lg border-t-4 border-blue-700" style="width: 190mm; min-height: 270mm; padding: 10mm;">
                
                {{-- Header - Colorful & Professional --}}
                <div class="flex justify-between items-start border-b-2 border-gray-100 pb-3 mb-4">
                    <div>
                        <h1 class="text-xl font-black text-gray-800 uppercase tracking-tight">{{ $exam->department->name }}</h1>
                        <p class="text-blue-700 font-bold text-sm">{{ $exam->name }} - {{ $exam->academicSession->name }}</p>
                        <div class="flex gap-4 mt-1 text-[10px] font-semibold text-gray-500">
                            <span>📅 {{ $session->exam_date->format('d M, Y') }}</span>
                            <span>⏰ {{ date('h:i A', strtotime($session->examSlot->start_time)) }} - {{ date('h:i A', strtotime($session->examSlot->end_time)) }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="bg-blue-700 text-white px-4 py-1 rounded-lg">
                            <span class="text-[10px] block uppercase font-bold opacity-80">Room Number</span>
                            <span class="text-xl font-black leading-none">{{ $sessionRoom->room->room_number }}</span>
                        </div>
                        <p class="text-[10px] mt-1 font-bold text-gray-500">Total: {{ $sessionRoom->allocated_students }} Students</p>
                    </div>
                </div>

                {{-- Subject Summary Tags --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    @php $roomCourses = $sessionRoom->seats->groupBy('exam_session_course_id'); @endphp
                    @foreach($roomCourses as $courseId => $seats)
                        <div class="flex items-center border border-blue-100 bg-blue-50 rounded px-2 py-0.5">
                            <span class="text-[9px] font-bold text-blue-800">
                                {{ $seats->first()->sessionCourse->course->course_code }} ({{ $seats->first()->sessionCourse->batch->batch_number }}): 
                                <span class="text-blue-600">{{ $seats->count() }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Compact Grid Layout --}}
                <div class="flex gap-1.5 justify-between">
                    @for($c = 1; $c <= $sessionRoom->room->total_cols; $c++)
                        <div class="flex-1 flex flex-col gap-1.5">
                            {{-- Column Label --}}
                            <div class="bg-gray-800 text-white text-[9px] font-bold text-center py-1 rounded-sm uppercase tracking-widest">
                                Col {{ $c }}
                            </div>

                            {{-- Row Seats --}}
                            @for($r = 1; $r <= $sessionRoom->room->total_rows; $r++)
                                @php
                                    $seatLabel = "R{$r}-C{$c}";
                                    $seat = $sessionRoom->seats->where('seat_label', $seatLabel)->first();
                                    $isDisabled = in_array($seatLabel, $sessionRoom->room->disabled_seats ?? []);
                                @endphp

                                <div class="seat-card relative border flex flex-col items-center justify-center rounded {{ $isDisabled ? 'bg-gray-100 border-gray-200' : ($seat ? 'bg-white border-blue-200 shadow-sm' : 'border-dashed border-gray-200') }}">
                                    
                                    <span class="row-num">{{ $r }}</span>

                                    @if($isDisabled)
                                        <span class="text-gray-300 text-[10px] font-bold">N/A</span>
                                    @elseif($seat)
                                        <div class="id-font text-blue-900">{{ $seat->student->student_id }}</div>
                                        <div class="course-font uppercase">{{ $seat->sessionCourse->course->course_code }}</div>
                                        {{-- Optional: Batch small badge --}}
                                        <div class="absolute bottom-0.5 right-1 text-[6px] text-blue-400 font-bold">
                                            B{{ $seat->sessionCourse->batch->batch_number }}
                                        </div>
                                    @else
                                        <div class="w-1 h-1 bg-gray-100 rounded-full"></div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    @endfor
                </div>

                {{-- Footer --}}
                <div class="mt-auto pt-4 flex justify-between items-center text-[9px] text-gray-400 font-medium">
                    <p>Printed on: {{ now()->format('d M Y, h:i A') }}</p>
                    <p class="bg-gray-100 px-2 py-0.5 rounded">Exam Management System</p>
                </div>

            </div>
        @endforeach
    @endforeach

</body>
</html>