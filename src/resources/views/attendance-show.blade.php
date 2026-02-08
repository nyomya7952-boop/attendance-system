@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-show.css') }}">
@endsection

@section('content')
<div class="attendance-show__content">
    <div class="attendance-show__container">
        <div class="attendance-show__header">
            <img src="{{ asset('images/title_line.png') }}" alt="LINE" class="attendance-show__title-line">
            <h1 class="attendance-show__title">勤怠詳細</h1>
        </div>
        <div class="attendance-show__body {{ $attendanceRequest ? 'attendance-show__body--readonly' : '' }}">
            <div class="attendance-show__row">
                <div class="attendance-show__label">名前</div>
                <div class="attendance-show__value">{{ $attendance->user->name }}</div>
            </div>
            <div class="attendance-show__divider"></div>
            <div class="attendance-show__row">
                <div class="attendance-show__label">日付</div>
                <div class="attendance-show__date-group">
                    <span class="attendance-show__year">{{ $attendance->work_date ? \Illuminate\Support\Carbon::parse($attendance->work_date)->format('Y年') : '' }}</span>
                    <span class="attendance-show__date">{{ $attendance->work_date ? \Illuminate\Support\Carbon::parse($attendance->work_date)->format('n月j日') : '' }}</span>
                </div>
            </div>
            <!-- 修正申請がない、または修正申請が承認されている場合 -->
            @if(!$attendanceRequest)
                <form action="{{ route('attendance.update', $attendance->id) }}" method="post">
                    @csrf
                    <div class="attendance-show__divider"></div>
                        <div class="attendance-show__row">
                            <div class="attendance-show__label">出勤・退勤</div>
                            <div class="attendance-show__time-group">
                                <div class="attendance-show__time-box"><input type="time" name="started_at" value="{{ $attendance->started_at ? \Illuminate\Support\Carbon::parse($attendance->started_at)->format('H:i') : '' }}" /></div>
                                <span class="attendance-show__separator">〜</span>
                                <div class="attendance-show__time-box"><input type="time" name="ended_at" value="{{ $attendance->ended_at ? \Illuminate\Support\Carbon::parse($attendance->ended_at)->format('H:i') : '' }}" /></div>
                            </div>
                        </div>
                        @foreach($attendanceBreaks as $attendanceBreak)
                        <div class="attendance-show__divider"></div>
                        <div class="attendance-show__row">
                            <div class="attendance-show__label">
                                {{ $loop->iteration === 1 ? '休憩' : '休憩' . $loop->iteration }}
                            </div>
                            <div class="attendance-show__break-group">
                                <div class="attendance-show__time-box"><input type="time" name="breaks[{{ $attendanceBreak->id }}][break_start_at]" value="{{ $attendanceBreak->break_start_at ? \Illuminate\Support\Carbon::parse($attendanceBreak->break_start_at)->format('H:i') : '' }}" /></div>
                                <span class="attendance-show__separator">〜</span>
                                <div class="attendance-show__time-box"><input type="time" name="breaks[{{ $attendanceBreak->id }}][break_end_at]" value="{{ $attendanceBreak->break_end_at ? \Illuminate\Support\Carbon::parse($attendanceBreak->break_end_at)->format('H:i') : '' }}" /></div>
                            </div>
                        </div>
                        @endforeach
                    <div class="attendance-show__divider"></div>
                    <div class="attendance-show__row">
                        <div class="attendance-show__label">
                            休憩{{ $attendanceBreaks->count() + 1 }}
                        </div>
                        <div class="attendance-show__break-group">
                            <div class="attendance-show__time-box"><input type="time" name="breaks[new][break_start_at]" value="" /></div>
                            <span class="attendance-show__separator">〜</span>
                            <div class="attendance-show__time-box"><input type="time" name="breaks[new][break_end_at]" value="" /></div>
                        </div>
                    </div>
                        <div class="attendance-show__divider"></div>
                        <div class="attendance-show__row attendance-show__row--remarks">
                            <div class="attendance-show__label">備考</div>
                            <div class="attendance-show__remarks-box">
                                <textarea name="remarks">{{ $attendance->remarks }}</textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="attendance-show__modify-button">修正</button>
                </form>
            @endif
            <!-- 修正申請がある場合 -->
            @if($attendanceRequest)
                <div class="attendance-show__divider"></div>
                <div class="attendance-show__row">
                    <div class="attendance-show__label">出勤・退勤</div>
                        <div class="attendance-show__time-group">
                            <div class="attendance-show__time-box">{{ $attendance->started_at ? \Illuminate\Support\Carbon::parse($attendance->started_at)->format('H:i') : '' }}</div>
                            <span class="attendance-show__separator">〜</span>
                            <div class="attendance-show__time-box">{{ $attendance->ended_at ? \Illuminate\Support\Carbon::parse($attendance->ended_at)->format('H:i') : '' }}</div>
                        </div>
                    </div>
                    @foreach($attendance->attendanceBreak as $attendanceBreak)
                    <div class="attendance-show__divider"></div>
                    <div class="attendance-show__row">
                        <div class="attendance-show__label">
                            {{ $loop->iteration === 1 ? '休憩' : '休憩' . $loop->iteration }}
                        </div>
                        <div class="attendance-show__break-group">
                            <div class="attendance-show__time-box">{{ $attendanceBreak->break_start_at ? \Illuminate\Support\Carbon::parse($attendanceBreak->break_start_at)->format('H:i') : '' }}</div>
                            <span class="attendance-show__separator">〜</span>
                            <div class="attendance-show__time-box">{{ $attendanceBreak->break_end_at ? \Illuminate\Support\Carbon::parse($attendanceBreak->break_end_at)->format('H:i') : '' }}</div>
                        </div>
                    </div>
                    @endforeach
                    <div class="attendance-show__divider"></div>
                    <div class="attendance-show__row attendance-show__row--remarks">
                        <div class="attendance-show__label">備考</div>
                        <div class="attendance-show__remarks-box">
                            <textarea name="remarks">{{ $attendance->remarks }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="attendance-show__approval-waiting">*承認待ちのため修正はできません。</div>
            @endif
        </div>
    </div>
</div>
@endsection