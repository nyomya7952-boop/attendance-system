@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')
<div class="verify-email__content">
    <div class="verify-email__message">
        <p>登録していただいたメールアドレスに認証メールを送付しました。</p>
        <p>メール認証を完了してください。</p>
    </div>

    @if (session('message'))
        <div class="verify-email__success">
            {{ session('message') }}
        </div>
    @endif

    <div class="verify-email__actions">
        @if(isset($user) && $user)
            <a href="http://localhost:8025" target="_blank" class="verify-email__button">
                認証はこちらから
            </a>
        @endif

        <div style="margin-top: 20px;">
            <form action="{{ route('verification.resend') }}" method="post" style="display: inline;">
                @csrf
                <button type="submit" class="verify-email__resend-link">認証メールを再送する</button>
            </form>
        </div>
    </div>
</div>
@endsection