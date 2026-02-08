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
    <div class="attendance__time" id="js-attendance-time">
        {{ $attendance && $attendance->ended_at ? \Illuminate\Support\Carbon::parse($attendance->ended_at)->format('H:i') : now()->format('H:i') }}
    </div>
    <form action="{{ route('attendance.store') }}" method="post">
        @csrf
        @if ($attendanceStatus == \App\Enums\AttendanceStatus::OUTSIDE_WORK)
            <button type="submit" name="action" value="clock_in" class="attendance__button">
                出勤
            </button>
        @endif
        @if ($attendanceStatus == \App\Enums\AttendanceStatus::CLOCKED_IN)
            <button type="submit" name="action" value="clock_out" class="attendance__button">
                退勤
            </button>
            <button type="submit" name="action" value="break_start" class="attendance__button--white">
                休憩入
            </button>
        @endif
        @if ($attendanceStatus == \App\Enums\AttendanceStatus::ON_BREAK)
            <button type="submit" name="action" value="break_end" class="attendance__button--white">
                休憩戻
            </button>
        @endif
        @if ($attendanceStatus == \App\Enums\AttendanceStatus::CLOCKED_OUT)
            <div class="attendance__message">
                <span class="attendance__message-text">お疲れ様でした。</span>
            </div>
        @endif
    </form>
</div>
@endsection

@section('js')
<script>
    (function () {
        const timeEl = document.getElementById('js-attendance-time');
        if (!timeEl) {
            return;
        }

        const pad = (value) => String(value).padStart(2, '0');
        const updateTime = () => {
            const now = new Date();
            timeEl.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
        };

        updateTime();
        setInterval(updateTime, 1000);
    })();
</script>
@endsection
