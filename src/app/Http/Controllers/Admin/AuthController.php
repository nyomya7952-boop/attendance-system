<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\Role;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AuthController extends Controller
{
    /**
     * ログイン画面を表示（管理者）
     */
    public function showLogin()
    {
        return view('admin.login');
    }

    /**
     * ログイン処理（管理者）- Fortify標準処理を使用
     */
    public function login(LoginRequest $request)
    {
        // 管理者のみログイン可能にするため、認証前にチェック
        $user = User::where('email', $request->email)->first();

        if ($user && $user->role_id !== Role::ADMIN->value) {
            throw ValidationException::withMessages([
                Fortify::username() => [__('auth.failed')],
            ]);
        }

        // Fortify標準の認証処理を使用
        $authenticatedSessionController = new AuthenticatedSessionController();

        // リダイレクト先を一時的に変更
        $originalHome = config('fortify.home');
        config(['fortify.home' => '/admin/staff/list']);

        try {
            $response = $authenticatedSessionController->store($request);
        } finally {
            // 設定を元に戻す
            config(['fortify.home' => $originalHome]);
        }

        return $response;
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
        return redirect()->route('admin.login');
    }
}

