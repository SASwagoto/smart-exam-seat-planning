<?php

namespace App\Livewire;

use Livewire\Component;

class RoomSeatPicker extends Component
{
    public int $totalRows = 5;
    public int $totalCols = 4;
    public array $disabledSeats = [];

    public function mount($rows = 5, $cols = 4, $disabled = [])
    {
        $this->totalRows = $rows > 0 ? (int)$rows : 5;
        $this->totalCols = $cols > 0 ? (int)$cols : 4;
        $this->disabledSeats = is_array($disabled) ? $disabled : [];
    }

    public function toggleSeat(string $seatCode)
    {
        if (in_array($seatCode, $this->disabledSeats)) {
            $this->disabledSeats = array_values(array_diff($this->disabledSeats, [$seatCode]));
        } else {
            $this->disabledSeats[] = $seatCode;
        }

        $this->dispatch('disabled-seats-updated', seats: $this->disabledSeats);
    }

    public function render()
    {
        return view('livewire.room-seat-picker');
    }
}