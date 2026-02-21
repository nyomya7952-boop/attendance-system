@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/approve.css') }}">
@endsection

@section('content')
<div class="approve__content">
    <div class="approve__container">
        <div class="approve__header">
            <img src="{{ asset('images/title_line.png') }}" alt="LINE" class="approve__title-line">
            <h1 class="approve__title">勤怠詳細</h1>
        </div>
        <div class="approve__body">
            <div class="approve__row">
                <div class="approve__label">名前</div>
                <div class="approve__value">{{ $attendanceRequest->requestedBy->name }}</div>
            </div>
            <div class="approve__divider"></div>
            <div class="approve__row">
                <div class="approve__label">日付</div>
                <div class="approve__date-group">
                    <span class="approve__year">{{ $attendanceRequest->requested_work_date ? \Illuminate\Support\Carbon::parse($attendanceRequest->requested_work_date)->format('Y年') : '' }}</span>
                    <span class="approve__date">{{ $attendanceRequest->requested_work_date ? \Illuminate\Support\Carbon::parse($attendanceRequest->requested_work_date)->format('n月j日') : '' }}</span>
                </div>
            </div>
            <div class="approve__divider"></div>
            <div class="approve__row">
                <div class="approve__label">出勤・退勤</div>
                <div class="approve__time-group">
                    <div class="approve__time-box">{{ $attendanceRequest->requested_started_at ? \Illuminate\Support\Carbon::parse($attendanceRequest->requested_started_at)->format('H:i') : '' }}</div>
                    <span class="approve__separator">〜</span>
                    <div class="approve__time-box">{{ $attendanceRequest->requested_ended_at ? \Illuminate\Support\Carbon::parse($attendanceRequest->requested_ended_at)->format('H:i') : '' }}</div>
                </div>
            </div>
            @foreach($attendanceRequest->attendanceRequestBreaks as $attendanceRequestBreak)
            <div class="approve__divider"></div>
            <div class="approve__row">
                <div class="approve__label">
                    {{ $loop->iteration === 1 ? '休憩' : '休憩' . $loop->iteration }}
                </div>
                <div class="approve__break-group">
                    <div class="approve__time-box">{{ $attendanceRequestBreak->break_start_at ? \Illuminate\Support\Carbon::parse($attendanceRequestBreak->break_start_at)->format('H:i') : '' }}</div>
                    <span class="approve__separator">〜</span>
                    <div class="approve__time-box">{{ $attendanceRequestBreak->break_end_at ? \Illuminate\Support\Carbon::parse($attendanceRequestBreak->break_end_at)->format('H:i') : '' }}</div>
                </div>
            </div>
            @endforeach
            <div class="approve__divider"></div>
            <div class="approve__row approve__row--remarks">
                <div class="approve__label">備考</div>
                <div class="approve__remarks-box">
                    <textarea name="remarks">{{ $attendanceRequest->remarks }}</textarea>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.request.approve', ['attendance_correct_request_id' => $attendanceRequest->id]) }}">
            @csrf
            @if(!$isApproved)
                <button type="submit" class="approve__approval-button--enabled">承認する</button>
            @else
                <button type="button" class="approve__approval-button--disabled" disabled>承認済み</button>
            @endif
        </form>
    </div>
</div>
@endsection