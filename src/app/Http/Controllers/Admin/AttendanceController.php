<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面を表示（管理者）
     */
    public function index()
    {
        // TODO: 管理者用勤怠一覧画面のビューを返す
        return view('admin.attendance.index');
    }

    /**
     * 勤怠詳細画面を表示（管理者）
     */
    public function show($id)
    {
        // TODO: 管理者用勤怠詳細画面のビューを返す
        return view('admin.attendance.show', ['id' => $id]);
    }

    /**
     * 勤怠詳細更新処理（管理者）
     */
    public function update(Request $request, $id)
    {
        // TODO: 管理者用勤怠詳細更新処理を実装
    }

    /**
     * スタッフ別勤怠一覧画面を表示（管理者）
     */
    public function showStaffAttendance($id)
    {
        // TODO: スタッフ別勤怠一覧画面のビューを返す
        return view('admin.attendance.staff', ['staffId' => $id]);
    }

    /**
     * スタッフ別勤怠更新処理（管理者）
     */
    public function updateStaffAttendance(Request $request, $id)
    {
        // TODO: スタッフ別勤怠更新処理を実装
    }
}

