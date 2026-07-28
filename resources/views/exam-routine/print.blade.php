<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exam->name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @media print {

            .no-print {
                display: none;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            body {
                margin: 0;
            }
        }
    </style>

</head>

<body class="bg-gray-100">

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow p-8 text-center">

        <h1 class="text-3xl font-bold uppercase">
            {{ $exam->department->name }}
        </h1>

        <h2 class="text-2xl text-blue-700 font-semibold mt-2">
            {{ $exam->name }} - {{ $exam->start_date->format('Y') }}
        </h2>

        <p class="mt-2 text-lg">
            {{ $exam->academicSession->name }}
        </p>

        <p class="text-gray-600 mt-1">
            {{ $exam->start_date->format('d M Y') }}
            -
            {{ $exam->end_date->format('d M Y') }}
        </p>

    </div>


@foreach($routine as $date => $schedules)

    <div class="mb-8">

        <div class="bg-blue-700 text-white p-3 rounded font-bold text-xl">

            {{ \Carbon\Carbon::parse($date)->format('d F Y (l)') }}

        </div>

        <div class="grid grid-cols-2 gap-6 mt-5">

            @foreach($schedules as $schedule)

                <div class="border rounded shadow">

                    <div class="bg-gray-100 p-3 border-b">

                        <div class="font-bold">

                            {{ $schedule->slot_name }}

                        </div>

                        <div class="text-sm text-gray-500">

                            {{ date('h:i A', strtotime($schedule->start_time)) }}

                            -

                            {{ date('h:i A', strtotime($schedule->end_time)) }}

                        </div>

                    </div>

                    <table class="w-full text-sm">

                        <thead class="bg-gray-50">

                        <tr>

                            <th class="border p-2">Course</th>
                            <th class="border p-2">Section</th>
                            <th class="border p-2">Batch</th>
                            <th class="border p-2">Students</th>

                        </tr>

                        </thead>

                        <tbody>

                        @foreach($schedule->courses as $course)

                            <tr>

                                <td class="border p-2">

                                    {{ $course->course_code }}

                                </td>

                                <td class="border p-2 text-center">

                                    {{ $course->section_name }}

                                </td>

                                <td class="border p-2 text-center">

                                    {{ $course->batch_number }}

                                </td>

                                <td class="border p-2 text-center">

                                    {{ $course->student_count }}

                                </td>

                            </tr>

                        @endforeach

                        </tbody>

                    </table>

                    <div class="p-3 border-t">

                        <span class="font-semibold">

                            Rooms :

                        </span>

                        @foreach($schedule->rooms as $room)

                            <span class="inline-block bg-blue-100 rounded px-2 py-1 text-sm">

                                {{ $room->room_number }}

                            </span>

                        @endforeach

                    </div>

                </div>

            @endforeach

        </div>

    </div>

@endforeach

</div>

<div class="fixed bottom-6 right-6 no-print">

    <button
        onclick="window.print()"
        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow">

        🖨️ Print Routine

    </button>

</div>

</body>

</html>