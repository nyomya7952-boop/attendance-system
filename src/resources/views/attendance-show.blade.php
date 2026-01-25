@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-show.css') }}">
@endsection

@section('content')
<div class="attendance-show">
    <h1 class="attendance-show__title">勤怠詳細</h1>
    <div class="attendance-show__line"></div>
    <div class="attendance-show__container">
        <div class="attendance-show__row">
            <div class="attendance-show__label">名前</div>
            <div class="attendance-show__value">{{ $attendance->user->name }}</div>
        </div>
        <div class="attendance-show__divider"></div>
        <div class="attendance-show__row">
            <div class="attendance-show__label">日付</div>
            <div class="attendance-show__date-group">
                <span class="attendance-show__year">{{ $attendance->work_date ? \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                <span class="attendance-show__date">{{ $attendance->work_date ? \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
            </div>
        </div>
        <form action="{{ route('attendance.update', $attendance->id) }}" method="post">
            @csrf
            @method('PUT')
        <div class="attendance-show__divider"></div>
            <div class="attendance-show__row">
                <div class="attendance-show__label">出勤・退勤</div>
                <div class="attendance-show__time-group">
                    <div class="attendance-show__time-box"><input type="time" name="started_at" value="{{ $attendance->started_at ? $attendance->started_at->format('H:i') }}" /></div>
                    <span class="attendance-show__separator">〜</span>
                    <div class="attendance-show__time-box"><input type="time" name="ended_at" value="{{ $attendance->ended_at ? $attendance->ended_at->format('H:i') }}" /></div>
                </div>
            </div>
            @foreach($attendanceBreaks as $attendanceBreak)
                <div class="attendance-show__divider"></div>
                <div class="attendance-show__row attendance-show__row--break">
                    <div class="attendance-show__label">休憩{{ $loop->iteration }}</div>
                    <div class="attendance-show__break-group">
                        <div class="attendance-show__break-row">
                            <div class="attendance-show__time-box"><input type="time" name="break_start" value="{{ $attendanceBreak->break_start ? \Carbon\Carbon::parse($attendanceBreak->break_start)->format('H:i') }}" /></div>
                            <span class="attendance-show__separator">〜</span>
                            <div class="attendance-show__time-box"><input type="time" name="break_end" value="{{ $attendanceBreak->break_end ? \Carbon\Carbon::parse($attendanceBreak->break_end)->format('H:i') }}" /></div>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="attendance-show__divider"></div>
            <div class="attendance-show__row attendance-show__row--remarks">
                <div class="attendance-show__label">備考</div>
                <div class="attendance-show__remarks-box">
                    <input type="text" name="remarks" value="{{ $attendance->remarks }}" />
                </div>
            </div>
            <button type="submit" class="attendance-show__modify-button">修正</button>
        </form>
    </div>
</div>
@endsection