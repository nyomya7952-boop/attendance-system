@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance-staff-list.css') }}">
@endsection

@section('content')
<div class="attendance-staff-list__content">
    <div class="attendance-staff-list__container">
        <div class="attendance-staff-list__controls">
            <img src="{{ asset('images/title_line.png') }}" alt="LINE" class="attendance-staff-list__title-line">
            <h1 class="attendance-staff-list__list-title">{{ $staff->name }}さんの勤怠</h1>
        </div>
        <div class="attendance-staff-list__date-selector">
            <a class="attendance-staff-list__date-arrow attendance-staff-list__date-arrow--prev"
                href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $prevMonth]) }}">
                <img src="{{ asset('images/left_arrow.png') }}" alt="前月" class="attendance-staff-list__left-arrow">
                <span class="attendance-staff-list__date-arrow-text">前月</span>
            </a>
            <div class="attendance-staff-list__date-center">
                <img src="{{ asset('images/calender.png') }}" alt="カレンダー" class="attendance-staff-list__calendar">
                <span class="attendance-staff-list__date-display">{{ $currentMonth ?? now()->format('Y/m') }}</span>
            </div>
            <a class="attendance-staff-list__date-arrow attendance-staff-list__date-arrow--next"
                href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $nextMonth]) }}">
                <img src="{{ asset('images/right_arrow.png') }}" alt="翌月" class="attendance-staff-list__right-arrow">
                <span class="attendance-staff-list__date-arrow-text">翌月</span>
            </a>
        </div>
        <div class="attendance-staff-list__table-container">
            <div class="attendance-staff-list__table-scroll">
                <table class="attendance-staff-list__table">
                <thead class="attendance-staff-list__table-header">
                    <tr>
                        <th class="attendance-staff-list__table-header-cell">名前</th>
                        <th class="attendance-staff-list__table-header-cell">出勤</th>
                        <th class="attendance-staff-list__table-header-cell">退勤</th>
                        <th class="attendance-staff-list__table-header-cell">休憩</th>
                        <th class="attendance-staff-list__table-header-cell">合計</th>
                        <th class="attendance-staff-list__table-header-cell">詳細</th>
                    </tr>
                </thead>
                <tbody class="attendance-staff-list__table-body">
                    @if(isset($attendances) && count($attendances) > 0)
                        @foreach($attendances as $attendance)
                            <tr class="attendance-staff-list__table-row">
                                <td class="attendance-staff-list__table-cell attendance-staff-list__table-cell--name">
                                    {{ $attendance->user->name }}
                                </td>
                                @if($attendance->started_at)
                                    <td class="attendance-staff-list__table-cell">{{ \Illuminate\Support\Carbon::parse($attendance->started_at)->format('H:i') }}</td>
                                    <td class="attendance-staff-list__table-cell">{{ $attendance->ended_at ? \Illuminate\Support\Carbon::parse($attendance->ended_at)->format('H:i') : '-' }}</td>
                                    <td class="attendance-staff-list__table-cell">
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
                                    <td class="attendance-staff-list__table-cell">
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
                                    <td class="attendance-staff-list__table-cell">-</td>
                                    <td class="attendance-staff-list__table-cell">-</td>
                                    <td class="attendance-staff-list__table-cell">-</td>
                                    <td class="attendance-staff-list__table-cell">-</td>
                                @endif
                                <td class="attendance-staff-list__table-cell">
                                    <a href="{{ route('admin.attendance.show', $attendance->id) }}" class="attendance-staff-list__detail-link">詳細</a>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
                </table>
            </div>
        </div>
        <div class="attendance-staff-list__csv-button-container">
            <button type="submit" class="attendance-staff-list__csv-button" onclick="window.location.href='{{ route('admin.staff.attendance.csv', ['id' => $staff->id, 'month' => request('month')]) }}'">CSV出力</button>
        </div>
    </div>
</div>
@endsection

