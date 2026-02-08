<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Auth\Events\Verified;

class EmailVerificationController extends Controller
{
    /**
     * メール認証画面を表示
     */
    public function showVerificationNotice(Request $request)
    {
        // ログインしている場合はユーザーを取得、していない場合はセッションからメールアドレスで取得
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $email = $request->session()->get('verification_email');
            $user = $email ? User::where('email', $email)->first() : null;
        }

        // メール認証画面のビューを返す
        return view('verify-email-notice', ['user' => $user]);
    }

    /**
     * メール認証通知を再送信
     */
    public function resendVerificationEmail(Request $request)
    {
        // ログインしている場合はユーザーを取得、していない場合はセッションからメールアドレスを取得
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasVerifiedEmail()) {
                // 認証済みの場合は出勤画面にリダイレクト
                // 勤怠登録の有無を確認
                $attendance = Attendance::where('user_id', $user->id)->where('work_date', date('Y-m-d'))->first();
                if ($attendance) {
                    // URL直打ちしても再送信できないようにする
                    return redirect()->route('attendance.index')->with('error', '勤怠登録があるため再送信できません。');
                } else {
                    return redirect()->route('attendance.index');
                }
            }
        } else {
            $email = $request->session()->get('verification_email');
            if (!$email) {
                return redirect()->route('login')->with('error', 'メールアドレスが見つかりませんでした。再度ログインしてください。');
            }
            $user = User::where('email', $email)->first();
            if (!$user) {
                return redirect()->route('login')->with('error', 'ユーザーが見つかりませんでした。');
            }
            if ($user->hasVerifiedEmail()) {
                return redirect()->route('login')->with('message', 'メール認証は既に完了しています。ログインしてください。');
            }
        }

        $user->sendEmailVerificationNotification();

        return back()->with('message', '認証メールを再送信しました');
    }

    /**
     * メール認証を実行
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            // 既に認証済みの場合はログイン状態にしてからリダイレクト
            if (!Auth::check()) {
                Auth::login($user);
            }
            // セッションからメールアドレスを削除し、トップページにリダイレクト
            $request->session()->forget('verification_email');
            return redirect('/');
        }

        if ($user->markEmailAsVerified()) {
            // メール認証完了イベントを発行
            event(new Verified($user));
        }

        // メール認証完了後、ログイン状態にする
        Auth::login($user);

        // セッションからメールアドレスを削除し、トップページにリダイレクト
        $request->session()->forget('verification_email');
        return redirect('/');
    }
}

