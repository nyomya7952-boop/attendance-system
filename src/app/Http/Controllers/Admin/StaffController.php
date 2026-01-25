<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * スタッフ一覧画面を表示（管理者）
     */
    public function index()
    {
        // TODO: スタッフ一覧画面のビューを返す
        return view('admin.staff.index');
    }
}

