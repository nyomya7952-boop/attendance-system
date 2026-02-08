@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="attendance-list__container">
        <div class="attendance-list__controls">
            <img src="{{ asset('images/title_line.png') }}" alt="LINE" class="attendance-list__title-line">
            <h1 class="attendance-list__list-title">{{ $currentDate }}の勤怠</h1>
        </div>
        <div class="attendance-list__date-selector">
            <a class="attendance-list__date-arrow attendance-list__date-arrow--prev" href="{{ route('admin.attendance.list', ['date' => $prevDay]) }}">
                <img src="{{ asset('images/left_arrow.png') }}" alt="前日" class="attendance-list__left-arrow">
                <span class="attendance-list__date-arrow-text">前日</span>
            </a>
            <div class="attendance-list__date-center">
                <img src="{{ asset('images/calender.png') }}" alt="カレンダー" class="attendance-list__calendar">
                <span class="attendance-list__date-display">{{ $currentDateShort }}</span>
            </div>
            <a class="attendance-list__date-arrow attendance-list__date-arrow--next" href="{{ route('admin.attendance.list', ['date' => $nextDay]) }}">
                <img src="{{ asset('images/right_arrow.png') }}" alt="翌日" class="attendance-list__right-arrow">
                <span class="attendance-list__date-arrow-text">翌日</span>
            </a>
        </div>
        <div class="attendance-list__table-container">
            <div class="attendance-list__table-scroll">
                <table class="attendance-list__table">
                <thead class="attendance-list__table-header">
                    <tr>
                        <th class="attendance-list__table-header-cell">名前</th>
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
                                <td class="attendance-list__table-cell attendance-list__table-cell--name">
                                    {{ $attendance->user->name }}
                                </td>
                                @if($attendance->started_at)
                                    <td class="attendance-list__table-cell">{{ \Illuminate\Support\Carbon::parse($attendance->started_at)->format('H:i') }}</td>
                                    <td class="attendance-list__table-cell">{{ $attendance->ended_at ? \Illuminate\Support\Carbon::parse($attendance->ended_at)->format('H:i') : '-' }}</td>
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
                                    <a href="{{ route('admin.attendance.show', $attendance->id) }}" class="attendance-list__detail-link">詳細</a>
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

