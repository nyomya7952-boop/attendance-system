<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    /**
     * 申請一覧画面を表示（管理者）
     */
    public function index()
    {
        // TODO: 管理者用申請一覧画面のビューを返す
        return view('admin.request.index');
    }

    /**
     * 修正申請承認画面を表示（管理者）
     */
    public function showApproval($attendance_correct_request_id)
    {
        // TODO: 修正申請承認画面のビューを返す
        return view('admin.request.approve', ['requestId' => $attendance_correct_request_id]);
    }

    /**
     * 修正申請承認処理（管理者）
     */
    public function approve(Request $request, $attendance_correct_request_id)
    {
        // TODO: 修正申請承認処理を実装
    }
}

