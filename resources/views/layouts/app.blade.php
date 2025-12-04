<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Webový systém')</title>

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
    <header class="sticky-top shadow-sm bg-white">
        <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <div class="container">
                {{-- Left: Logo --}}
                <a class="navbar-brand fw-bold text-primary" href="{{ url('/') }}">
                    Webový systém
                </a>

                {{-- Toggler (mobile) --}}
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                        aria-controls="mainNavbar" aria-expanded="false" aria-label="Prepnúť navigáciu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                {{-- Collapsible content --}}
                <div class="collapse navbar-collapse" id="mainNavbar">
                    {{-- Center: Menu položky --}}
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link @yield('nav_home_active')" href="{{ url('/') }}">
                                Domov
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('nav_datasets_active')" href="{{ url('/datasets') }}">
                                Moje datasety
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('nav_upload_active')" href="{{ url('/upload') }}">
                                Nahrať dataset
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('nav_admin_active')" href="{{ url('/admin') }}">
                                Administrácia
                            </a>
                        </li>
                    </ul>

                    {{-- Right: Prihlásenie --}}
                    <div class="d-flex">
                        <a href="{{ url('/login') }}" class="btn btn-primary rounded-pill">
                            Prihlásenie
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    {{-- HLAVNÝ OBSAH --}}
    <main class="flex-grow-1 py-4">
        <div class="container">
            @yield('content')
        </div>
    </main>

    {{-- FOOTER --}}
    <footer class="border-top py-3 mt-auto bg-white">
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
</body>
</html>
