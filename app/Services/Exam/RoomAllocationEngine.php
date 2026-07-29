<?php

namespace App\Services\Exam;

use Illuminate\Support\Collection;

class RoomAllocationEngine
{
    public function allocate(Collection $sessionCourses, Collection $rooms, string $algorithm): array
    {
        $roomAllocations = [];

        // ১. সাবজেক্ট অনুযায়ী ব্যাচগুলোকে আলাদা করা
        $groupedBySubject = [];
        foreach ($sessionCourses as $sc) {
            $groupedBySubject[$sc->course_id][] = [
                'session_course_id' => $sc->id,
                'students' => $sc->student_list // এটি স্টুডেন্ট আইডি-র সিম্পল অ্যারে
            ];
        }

        // ২. সাবজেক্টগুলোকে গ্রুপ A এবং B তে ভাগ করা (রেফারেন্স বাগ এড়াতে ভেরিয়েবল আলাদা করা)
        $groupA = [];
        $groupB = [];
        $subjectIndex = 0;
        
        foreach ($groupedBySubject as $courseId => $batches) {
            foreach ($batches as $batch) {
                if ($subjectIndex % 2 === 0) {
                    $groupA[] = $batch;
                } else {
                    $groupB[] = $batch;
                }
            }
            $subjectIndex++;
        }

        foreach ($rooms as $room) {
            $seatsInThisRoom = [];
            $disabled = is_array($room->disabled_seats) ? $room->disabled_seats : json_decode($room->disabled_seats, true) ?? [];

            // কলাম ভিত্তিক লুপ
            for ($c = 1; $c <= $room->total_cols; $c++) {
                for ($r = 1; $r <= $room->total_rows; $r++) {
                    
                    $seatLabel = "R{$r}-C{$c}";
                    if (in_array($seatLabel, $disabled)) continue;

                    // ৩. অ্যালগরিদম অনুযায়ী গ্রুপ ঠিক করা
                    $pickFromA = ($algorithm === 'zig_zag_mixing') 
                                ? ($r + $c) % 2 === 0 
                                : $c % 2 !== 0;

                    // ৪. রেফারেন্স সরাসরি এখানে লুপের মাধ্যমে হ্যান্ডেল করা
                    if ($pickFromA) {
                        $this->assignStudent($groupA, $seatsInThisRoom, $r, $c, $seatLabel);
                    } else {
                        $this->assignStudent($groupB, $seatsInThisRoom, $r, $c, $seatLabel);
                    }
                }
            }

            if (!empty($seatsInThisRoom)) {
                $roomAllocations[] = [
                    'room_id' => $room->id,
                    'seats' => $seatsInThisRoom
                ];
            }

            if ($this->isAllPlaced($groupA, $groupB)) break;
        }

        return $roomAllocations;
    }

    private function assignStudent(&$targetGroup, &$seatsInThisRoom, $r, $c, $seatLabel)
    {
        foreach ($targetGroup as $key => $batchQueue) {
            if (!empty($batchQueue['students'])) {
                // array_shift ব্যবহার করে প্রথম স্টুডেন্ট বের করা
                $studentId = array_shift($targetGroup[$key]['students']);
                
                $seatsInThisRoom[] = [
                    'exam_session_course_id' => $batchQueue['session_course_id'],
                    'student_id' => $studentId,
                    'row_label' => "R{$r}",
                    'column_label' => "C{$c}",
                    'seat_label' => $seatLabel,
                ];
                return; // স্টুডেন্ট পাওয়া গেলে এই সিটের কাজ শেষ
            }
        }
    }

    private function isAllPlaced($groupA, $groupB): bool
    {
        foreach ($groupA as $b) if (!empty($b['students'])) return false;
        foreach ($groupB as $b) if (!empty($b['students'])) return false;
        return true;
    }
}