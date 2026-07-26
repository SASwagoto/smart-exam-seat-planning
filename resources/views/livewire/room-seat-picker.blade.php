@php
    $rowsCount = (int) ($rows ?? $totalRows ?? 5);
    $colsCount = (int) ($cols ?? $totalCols ?? 4);
    $disabledList = is_array($disabled ?? null) ? $disabled : (is_array($disabledSeats ?? null) ? $disabledSeats : []);
@endphp

<div x-data="{
        disabledSeats: @js($disabledList),
        toggleSeat(seatCode) {
            if (this.disabledSeats.includes(seatCode)) {
                this.disabledSeats = this.disabledSeats.filter(s => s !== seatCode);
            } else {
                this.disabledSeats.push(seatCode);
            }
            // Filament Form State-এর সাথে সিট সিঙ্ক করা
            $wire.$set('data.disabled_seats', this.disabledSeats);
        }
    }"
    style="padding: 1.25rem; background-color: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem;">
    
    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; gap: 0.5rem; flex-wrap: wrap;">
        <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #9ca3af;">
            Interactive Seat Grid (Click to Disable/Enable)
        </span>
        <div style="display: flex; gap: 0.75rem; font-size: 0.75rem;">
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #d1d5db;">
                <span style="width: 12px; height: 12px; background-color: #10b981; border-radius: 3px;"></span> Available
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #d1d5db;">
                <span style="width: 12px; height: 12px; background-color: #f43f5e; border-radius: 3px;"></span> Disabled
            </span>
        </div>
    </div>

    <!-- Board -->
    <div style="width: 100%; background-color: rgba(255, 255, 255, 0.08); text-align: center; padding: 0.5rem 0; border-radius: 0.375rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.1em; margin-bottom: 1.25rem; border: 1px dashed rgba(255,255,255,0.2);">
        Teacher Desk / Board
    </div>

    <!-- Seat Grid -->
    <div style="overflow-x: auto; padding-bottom: 0.5rem;">
        <div style="display: grid; grid-template-columns: repeat({{ $colsCount }}, minmax(48px, 1fr)); gap: 0.6rem; justify-content: center;">
            
            @for ($r = 1; $r <= $rowsCount; $r++)
                @for ($c = 1; $c <= $colsCount; $c++)
                    @php
                        $seatCode = "R{$r}-C{$c}";
                    @endphp

                    <button type="button"
                            x-on:click="toggleSeat('{{ $seatCode }}')"
                            title="Seat: {{ $seatCode }}"
                            :style="disabledSeats.includes('{{ $seatCode }}') 
                                ? 'height: 42px; font-size: 0.8rem; font-weight: 600; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; border: 1px solid #e11d48; background-color: #f43f5e; color: #ffffff;' 
                                : 'height: 42px; font-size: 0.8rem; font-weight: 600; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; border: 1px solid #059669; background-color: #10b981; color: #ffffff;'"
                            onmouseover="this.style.opacity='0.85';"
                            onmouseout="this.style.opacity='1';">
                        {{ $r }}-{{ $c }}
                    </button>
                @endfor
            @endfor

        </div>
    </div>
</div>