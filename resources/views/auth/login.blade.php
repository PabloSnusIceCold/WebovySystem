@extends('layouts.app')

@section('title', 'Log in')
@section('nav_home_active', '')
@section('nav_datasets_active', '')
@section('nav_upload_active', '')
@section('nav_admin_active', '')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">Log in</h1>
                <form id="loginForm" method="POST" action="{{ route('login.perform', request()->has('redirect') ? ['redirect' => request()->query('redirect')] : []) }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                        <div class="invalid-feedback" id="emailError">Please enter a valid email (must contain @).</div>
                        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback" id="passwordError">Password must be at least 8 characters and include an uppercase letter, a lowercase letter, and a number.</div>
                        @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill">Log in</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <span>Don't have an account?</span>
                    <a href="{{ route('register.show') }}" class="text-primary fw-semibold">Create one</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    function validateEmail(value) {
        return value && value.includes('@');
    }

    function validatePassword(value) {
        const hasMinLen = value && value.length >= 8;
        const hasUpper = /[A-Z]/.test(value);
        const hasLower = /[a-z]/.test(value);
        const hasDigit = /\d/.test(value);
        return hasMinLen && hasUpper && hasLower && hasDigit;
    }

    function setValidity(input, isValid) {
        input.classList.toggle('is-invalid', !isValid);
        input.classList.toggle('is-valid', isValid);
    }

    emailInput.addEventListener('input', () => {
        setValidity(emailInput, validateEmail(emailInput.value));
    });
    passwordInput.addEventListener('input', () => {
        setValidity(passwordInput, validatePassword(passwordInput.value));
    });

    form.addEventListener('submit', (e) => {
        const validEmail = validateEmail(emailInput.value);
        const validPassword = validatePassword(passwordInput.value);
        setValidity(emailInput, validEmail);
        setValidity(passwordInput, validPassword);
        if (!validEmail || !validPassword) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
})();
</script>
@endsection
