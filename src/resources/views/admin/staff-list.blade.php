@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff-list.css') }}">
@endsection

@section('content')
<div class="staff-list__content">
    <div class="staff-list__container">
        <div class="staff-list__controls">
            <img src="{{ asset('images/title_line.png') }}" alt="LINE" class="staff-list__title-line">
            <h1 class="staff-list__list-title">スタッフ一覧</h1>
        </div>
    </div>
    <div class="staff-list__table-container">
        <div class="staff-list__table-scroll">
            <table class="staff-list__table">
            <thead class="staff-list__table-header">
                <tr>
                    <th class="staff-list__table-header-cell">名前</th>
                    <th class="staff-list__table-header-cell">メールアドレス</th>
                    <th class="staff-list__table-header-cell">月次勤怠</th>
                </tr>
            </thead>
            <tbody class="staff-list__table-body">
                    @if(isset($staffs) && count($staffs) > 0)
                        @foreach($staffs as $staff)
                            <tr class="staff-list__table-row">
                                <td class="staff-list__table-cell staff-list__table-cell--name">
                                    {{ $staff->name }}
                                </td>
                                <td class="staff-list__table-cell staff-list__table-cell--email">
                                    {{ $staff->email }}
                                </td>
                                <td class="staff-list__table-cell staff-list__table-cell--monthly-attendance">
                                    <a href="{{ route('admin.staff.attendance', $staff->id) }}" class="staff-list__attendance-link">詳細</a>
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