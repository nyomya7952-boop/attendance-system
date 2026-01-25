@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="attendance-list__container">
        <div class="attendance-list__controls">
            <h1 class="attendance-list__list-button">｜勤怠一覧</h1>
            <div class="attendance-list__date-selector">
                <button class="attendance-list__date-arrow attendance-list__date-arrow--prev" type="button" onclick="changeMonth(-1)">
                    <svg width="35" height="19" viewBox="0 0 35 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M34 1L17.5 17.5L1 1" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <span class="attendance-list__date-display">{{ $currentMonth ?? now()->format('Y/m') }}</span>
                <button class="attendance-list__date-arrow attendance-list__date-arrow--next" type="button" onclick="changeMonth(1)">
                    <svg width="20" height="15" viewBox="0 0 20 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 14L10 1L19 14" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="attendance-list__table-container">
            <table class="attendance-list__table">
                <thead class="attendance-list__table-header">
                    <tr>
                        <th class="attendance-list__table-header-cell">日付</th>
                        <th class="attendance-list__table-header-cell">出勤</th>
                        <th class="attendance-list__table-header-cell">退勤</th>
                        <th class="attendance-list__table-header-cell">休憩</th>
                        <th class="attendance-list__table-header-cell">合計</th>
                        <th class="attendance-list__table-header-cell">詳細</th>
                    </tr>
                </thead>
                <tbody class="attendance-list__table-body">
                    @if(isset($attendances) && count($attendances) > 0)
                        @foreach($attendances as $attendance)
                            <tr class="attendance-list__table-row">
                                <td class="attendance-list__table-cell attendance-list__table-cell--date">
                                    {{ $attendance->formatted_date }}
                                </td>
                                @if($attendance->started_at)
                                    <td class="attendance-list__table-cell">{{ $attendance->started_at->format('H:i') }}</td>
                                    <td class="attendance-list__table-cell">{{ $attendance->ended_at ? $attendance->ended_at->format('H:i') : '-' }}</td>
                                    <td class="attendance-list__table-cell">
                                        @if($attendance->total_break_minutes)
                                            @php
                                                $hours = floor($attendance->total_break_minutes / 60);
                                                $minutes = $attendance->total_break_minutes % 60;
                                            @endphp
                                            {{ $hours }}:{{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="attendance-list__table-cell">
                                        @if($attendance->total_work_minutes)
                                            @php
                                                $hours = floor($attendance->total_work_minutes / 60);
                                                $minutes = $attendance->total_work_minutes % 60;
                                            @endphp
                                            {{ $hours }}:{{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @else
                                    <td class="attendance-list__table-cell">-</td>
                                    <td class="attendance-list__table-cell">-</td>
                                    <td class="attendance-list__table-cell">-</td>
                                    <td class="attendance-list__table-cell">-</td>
                                @endif
                                <td class="attendance-list__table-cell">
                                    <a href="{{ route('attendance.detail', $attendance->id) }}" class="attendance-list__detail-link">詳細</a>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@section('js')
<script>
function changeMonth(direction) {
    const currentMonth = '{{ $currentMonth ?? now()->format("Y/m") }}';
    const [year, month] = currentMonth.split('/').map(Number);
    const date = new Date(year, month - 1 + direction, 1);
    window.location.href = '{{ route("attendance.list") }}?month=' + date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
}
</script>
@endsection

