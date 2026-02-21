@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="attendance-list__container">
        <div class="attendance-list__controls">
            <img src="{{ asset('images/title_line.png') }}" alt="LINE" class="attendance-list__title-line">
            <h1 class="attendance-list__list-title">勤怠一覧</h1>
        </div>
        <div class="attendance-list__date-selector">
            <a class="attendance-list__date-arrow attendance-list__date-arrow--prev" href="{{ route('attendance.list', ['month' => $prevMonth]) }}">
                <img src="{{ asset('images/left_arrow.png') }}" alt="前月" class="attendance-list__left-arrow">
                <span class="attendance-list__date-arrow-text">前月</span>
            </a>
            <div class="attendance-list__date-center">
                <img src="{{ asset('images/calender.png') }}" alt="カレンダー" class="attendance-list__calendar">
                <span class="attendance-list__date-display">{{ $currentMonth ?? now()->format('Y/m') }}</span>
            </div>
            <a class="attendance-list__date-arrow attendance-list__date-arrow--next" href="{{ route('attendance.list', ['month' => $nextMonth]) }}">
                <img src="{{ asset('images/right_arrow.png') }}" alt="翌月" class="attendance-list__right-arrow">
                <span class="attendance-list__date-arrow-text">翌月</span>
            </a>
        </div>
        <div class="attendance-list__table-container">
            <div class="attendance-list__table-scroll">
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
                    @if(isset($attendanceRows) && count($attendanceRows) > 0)
                        @foreach($attendanceRows as $attendance)
                            <tr class="attendance-list__table-row">
                                <td class="attendance-list__table-cell attendance-list__table-cell--date">
                                    {{ $attendance->formatted_date }}
                                </td>
                                @if($attendance->started_at)
                                    <td class="attendance-list__table-cell">{{ \Illuminate\Support\Carbon::parse($attendance->started_at)->format('H:i') }}</td>
                                    <td class="attendance-list__table-cell">{{ $attendance->ended_at ? \Illuminate\Support\Carbon::parse($attendance->ended_at)->format('H:i') : '' }}</td>
                                    <td class="attendance-list__table-cell">
                                        @if($attendance->total_break_minutes !== null)
                                            @php
                                                $hours = floor($attendance->total_break_minutes / 60);
                                                $minutes = $attendance->total_break_minutes % 60;
                                            @endphp
                                            {{ $hours }}:{{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}
                                        @endif
                                    </td>
                                    <td class="attendance-list__table-cell">
                                        @if($attendance->total_work_minutes !== null)
                                            @php
                                                $hours = floor($attendance->total_work_minutes / 60);
                                                $minutes = $attendance->total_work_minutes % 60;
                                            @endphp
                                            {{ $hours }}:{{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}
                                        @endif
                                    </td>
                                @else
                                    <td class="attendance-list__table-cell"></td>
                                    <td class="attendance-list__table-cell"></td>
                                    <td class="attendance-list__table-cell"></td>
                                    <td class="attendance-list__table-cell"></td>
                                @endif
                                <td class="attendance-list__table-cell">
                                    @if($attendance->id)
                                        <a href="{{ route('attendance.detail', $attendance->id) }}" class="attendance-list__detail-link">詳細</a>
                                    @else
                                        <a href="{{ route('attendance.detail.byDate', ['date' => $attendance->work_date]) }}" class="attendance-list__detail-link">詳細</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

