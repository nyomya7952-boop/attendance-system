<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RequestController extends Controller
{
    /**
     * 申請一覧画面を表示（一般ユーザー）
     */
    public function index()
    {
        // TODO: 申請一覧画面のビューを返す
        return view('request.index');
    }
}

