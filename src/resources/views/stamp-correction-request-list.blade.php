@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/stamp-correction-request-list.css') }}">
@endsection

@section('content')
<div class="stamp-correction-request-list__content">
    <div class="stamp-correction-request-list__container">
        <div class="stamp-correction-request-list__controls">
            <img src="{{ asset('images/title_line.png') }}" alt="LINE" class="stamp-correction-request-list__title-line">
            <h1 class="stamp-correction-request-list__list-title">申請一覧</h1>
        </div>
    </div>
    <!-- タブセクション -->
    <div class="stamp-correction-request-list__tabs">
        <a href="{{ route('request.list', ['tab' => 'submitted']) }}" class="stamp-correction-request-list__tab {{ $activeTab === 'submitted' ? 'stamp-correction-request-list__tab--active' : '' }}">
            {{ \App\Enums\AttendanceRequestStatus::SUBMITTED->value }}
        </a>
        <a href="{{ route('request.list', ['tab' => 'approved']) }}" class="stamp-correction-request-list__tab {{ $activeTab === 'approved' ? 'stamp-correction-request-list__tab--active' : '' }}">
            {{ \App\Enums\AttendanceRequestStatus::APPROVED->value }}
        </a>
    </div>
    <div class="stamp-correction-request-list__table-container">
        <div class="stamp-correction-request-list__table-scroll">
            <table class="stamp-correction-request-list__table">
            <thead class="stamp-correction-request-list__table-header">
                <tr>
                    <th class="stamp-correction-request-list__table-header-cell">状態</th>
                    <th class="stamp-correction-request-list__table-header-cell">名前</th>
                    <th class="stamp-correction-request-list__table-header-cell">対象日時</th>
                    <th class="stamp-correction-request-list__table-header-cell">申請理由</th>
                    <th class="stamp-correction-request-list__table-header-cell">申請日時</th>
                    <th class="stamp-correction-request-list__table-header-cell">詳細</th>
                </tr>
            </thead>
            <tbody class="stamp-correction-request-list__table-body">
                    @if(isset($attendanceRequests) && count($attendanceRequests) > 0)
                        @foreach($attendanceRequests as $attendanceRequest)
                            <tr class="stamp-correction-request-list__table-row">
                                <td class="stamp-correction-request-list__table-cell stamp-correction-request-list__table-cell--status">
                                    {{ $attendanceRequest->status }}
                                </td>
                                <td class="stamp-correction-request-list__table-cell stamp-correction-request-list__table-cell--name">
                                    {{ $attendanceRequest->requestedBy->name }}
                                </td>
                                <td class="stamp-correction-request-list__table-cell stamp-correction-request-list__table-cell--date">
                                    {{ \Illuminate\Support\Carbon::parse($attendanceRequest->requested_work_date)->format('Y/m/d') }}
                                </td>
                                <td class="stamp-correction-request-list__table-cell stamp-correction-request-list__table-cell--remarks">
                                    {{ $attendanceRequest->remarks }}
                                </td>
                                <td class="stamp-correction-request-list__table-cell stamp-correction-request-list__table-cell--created-at">
                                    {{ \Illuminate\Support\Carbon::parse($attendanceRequest->created_at)->format('Y/m/d') }}
                                </td>
                                <td class="stamp-correction-request-list__table-cell">
                                    <a href="{{ route('attendance.detail', $attendanceRequest->attendance_id) }}" class="stamp-correction-request-list__detail-link">詳細</a>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection