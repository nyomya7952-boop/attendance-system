@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    <div class="attendance__status">
        <span class="attendance__status-text">{{ $attendanceStatus->value }}</span>
    </div>
    <div class="attendance__date">
        {{ $date }}
    </div>
    <div class="attendance__time">
        {{ now()->format('H:i') }}
    </div>
    <form action="{{ route('attendance.store') }}" method="post">
        @csrf
        @if ($attendanceStatus == AttendanceStatus::OUTSIDE_WORK)
            <button type="submit" name="action" value="clock_in" class="attendance__button">
                出勤
            </button>
        @endif
        @if ($attendanceStatus == AttendanceStatus::CLOCKED_IN)
            <button type="submit" name="action" value="clock_out" class="attendance__button">
                退勤
            </button>
            <button type="submit" name="action" value="break_start" class="attendance__button">
                休憩入
            </button>
        @endif
        @if ($attendanceStatus == AttendanceStatus::ON_BREAK)
            <button type="submit" name="action" value="break_end" class="attendance__button">
                休憩戻
            </button>
        @endif
        @if ($attendanceStatus == AttendanceStatus::CLOCKED_OUT)
            <div class="attendance__message">
                <span class="attendance__message-text">お疲れ様でした。</span>
            </div>
        @endif
    </form>
</div>
@endsection
