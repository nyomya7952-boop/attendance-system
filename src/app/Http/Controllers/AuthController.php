<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    /**
     * 会員登録画面を表示（一般ユーザー）
     */
    public function showRegister()
    {
        // 会員登録画面のビューを返す
        return view('register');
    }

    /**
     * 会員登録処理（一般ユーザー）
     */
    public function register(RegisterRequest $request)
    {
        // 入力したユーザ情報を登録
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => Role::GENERAL_USER->value,
        ]);

        // 認証メールを送信
        $user->sendEmailVerificationNotification();

        // メール認証画面に遷移
        $request->session()->put('verification_email', $user->email);
        return redirect()->route('verification.notice');
    }

    /**
     * ログイン画面を表示
     */
    public function login(Request $request)
    {
        return view('login');
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログアウト後はログイン画面にリダイレクト
        return redirect()->route('login');
    }
}

