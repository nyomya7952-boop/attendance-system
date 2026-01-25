<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <a class="header__logo" href="/">
                <img src="{{ asset('images/logo.png') }}" alt="COACHTECH" class="header__logo-image">
                <span class="header__logo-text">COACHTECH</span>
            </a>
        </div>
        @php
            $currentRoute = Route::currentRouteName();
            $hideNavRoutes = ['login', 'register', 'verification.notice', 'admin.login.show', 'admin.login'];
            $shouldHideNav = in_array($currentRoute, $hideNavRoutes);
        @endphp
        @if(!$shouldHideNav)
            @if (auth()->check() && auth()->user()->role_id === \App\Enums\Role::GENERAL_USER->value)
                <nav class="header__nav">
                    <ul class="header__nav-list">
                        <li class="header__nav-item">
                            <a class="header__nav-link" href="{{ route('user.profile') }}">勤怠一覧</a>
                        </li>
                        <li class="header__nav-item">
                            <a class="header__nav-button" href="/sell">スタッフ一覧</a>
                        </li>
                        <li class="header__nav-item">
                            <a class="header__nav-button" href="/sell">申請一覧</a>
                        </li>
                        <li class="header__nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a href="{{ route('logout') }}" class="header__nav-link"
                                    onclick="event.preventDefault(); this.closest('form').submit();">ログアウト</a>
                            </form>
                        </li>
                    </ul>
                </nav>
            @endif
            @if (auth()->check() && auth()->user()->role_id === \App\Enums\Role::ADMIN->value)
                <nav class="header__nav">
                    <ul class="header__nav-list">
                        <li class="header__nav-item">
                            <a class="header__nav-link" href="{{ route('user.profile') }}">勤怠</a>
                        </li>
                        <li class="header__nav-item">
                            <a class="header__nav-button" href="/sell">勤怠一覧</a>
                        </li>
                        <li class="header__nav-item">
                            <a class="header__nav-button" href="/sell">申請</a>
                        </li>
                        <li class="header__nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a href="{{ route('logout') }}" class="header__nav-link"
                                    onclick="event.preventDefault(); this.closest('form').submit();">ログアウト</a>
                            </form>
                        </li>
                    </ul>
                </nav>
            @endif
        @endif
    </header>

    <main>
        @include('partials.flash')
        @yield('content')
    </main>
    @yield('js')
</body>
</html>
