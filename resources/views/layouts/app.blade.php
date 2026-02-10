<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Web System')</title>

    {{-- Bootstrap 5 CSS --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    {{-- Vlastné doplnkové štýly --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="d-flex flex-column min-vh-100">

    {{-- NAVBAR --}}
    <header class="sticky-top header-glass shadow-sm">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                {{-- Left: Logo --}}
                <a class="navbar-brand fw-bold text-primary" href="{{ url('/') }}">
                    Web System
                </a>

                {{-- Toggler (mobile) --}}
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                        aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                {{-- Collapsible content --}}
                <div class="collapse navbar-collapse" id="mainNavbar">
                    {{-- Center: Menu položky --}}
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link @yield('nav_home_active')" href="{{ url('/') }}">
                                Home
                            </a>
                        </li>
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('datasets.index') ? 'active' : '' }}" href="{{ route('datasets.index') }}">
                                    My datasets
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('repositories.*') ? 'active' : '' }}" href="{{ route('repositories.index') }}">
                                    My repositories
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @yield('nav_upload_active')" href="{{ route('datasets.upload') }}">
                                    Upload dataset
                                </a>
                            </li>
                        @endauth
                    </ul>

                    {{-- Right: Auth-aware actions --}}
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if (Auth::check())
                            <span class="navbar-text me-2 text-muted">Hi, <span class="fw-semibold text-body">{{ Auth::user()->username }}</span></span>
                            @if (Auth::user()->role === 'admin')
                                <a href="{{ route('admin') }}" class="btn btn-outline-secondary btn-sm">Admin</a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">Log out</button>
                            </form>
                        @else
                            <a href="{{ route('login.show') }}" class="btn btn-primary rounded-pill btn-sm">Log in</a>
                            <a href="{{ route('register.show') }}" class="btn btn-outline-primary rounded-pill btn-sm">Register</a>
                        @endif
                    </div>
                </div>
            </div>
        </nav>
    </header>

    {{-- HLAVNÝ OBSAH --}}
    <main class="flex-grow-1 py-4">
        <div class="container-xxl">
            @yield('content')
        </div>
    </main>

    {{-- FOOTER --}}
    <footer class="footer-soft py-3 mt-auto">
        <div class="container">
            <p class="mb-0 text-center text-muted">
                © 2025 Matej Halama
            </p>
        </div>
    </footer>

    {{-- Bootstrap 5 JS (Bundle s Popperom) --}}
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"
    ></script>

    {{-- Stránka môže pridať vlastné skripty cez @push('scripts') --}}
    @stack('scripts')
</body>
</html>
