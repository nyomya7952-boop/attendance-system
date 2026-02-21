<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\StampingController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\RequestController;
use App\Enums\Role;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Admin\AttendanceDetailController as AdminAttendanceDetailController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        if ($user && $user->role_id === Role::ADMIN->value) {
            return redirect('/admin/attendance/list');
        }

        return redirect('/attendance');
    }

    return redirect('/login');
});

// 一般ユーザー用ルート
// 認証不要のルート
Route::get('/register', [AuthController::class, 'showRegister'])->name('register.show');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// メール認証関連
Route::get('/email/verify', [EmailVerificationController::class, 'showVerificationNotice'])->name('verification.notice');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resendVerificationEmail'])->name('verification.resend');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyEmail'])->name('verification.verify')->middleware('signed');

// 認証必須のルート
Route::middleware(['auth'])->group(function () {
    // ログアウト
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 出勤登録
    Route::get('/attendance', [StampingController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [StampingController::class, 'store'])->name('attendance.store');

    // 勤怠一覧・詳細（一般ユーザー）
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::get('/attendance/detail', [AttendanceDetailController::class, 'showByDate'])->name('attendance.detail.byDate');
    Route::post('/attendance/detail', [AttendanceDetailController::class, 'storeByDate'])->name('attendance.store.byDate');

    // 申請一覧（一般ユーザー）
    Route::get('/stamp_correction_request/list', [RequestController::class, 'index'])->name('request.list');
});

// 管理者用ルート
Route::prefix('admin')->name('admin.')->group(function () {
    // 管理者ログイン（認証不要）
    // 注意: Fortifyは /login のみを自動登録するため、/admin/login は手動で定義が必要
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login.show');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login');

    // 認証必須の管理者ルート
    Route::middleware(['auth'])->group(function () {

        // ログアウト
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // スタッフ一覧
        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.list');

        // スタッフ別勤怠一覧
        Route::get('/attendance/staff/{id}', [StaffController::class, 'showAttendanceStaffList'])->name('staff.attendance');
        Route::get('/attendance/staff/{id}/csv', [StaffController::class, 'exportAttendanceStaffCsv'])->name('staff.attendance.csv');

        // 勤怠一覧・詳細（管理者）
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');
        Route::get('/attendance/detail', [AdminAttendanceDetailController::class, 'showByDate'])->name('attendance.detail.byDate');
        Route::post('/attendance/detail', [AdminAttendanceDetailController::class, 'storeByDate'])->name('attendance.store.byDate');
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
        Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');

        // 申請一覧（管理者）
        Route::get('/stamp_correction_request/list', [AdminRequestController::class, 'index'])->name('request.list');

        // 修正申請承認
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminRequestController::class, 'showApproval'])->name('request.approve.show');
        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminRequestController::class, 'approve'])->name('request.approve');

    });
});

