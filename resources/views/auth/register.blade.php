@extends('layouts.app')

@section('title', 'Registrácia')
@section('nav_home_active', '')
@section('nav_datasets_active', '')
@section('nav_upload_active', '')
@section('nav_admin_active', '')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">Registrácia</h1>
                <form id="registerForm" method="POST" action="{{ route('register.perform') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="username" class="form-label">Používateľské meno</label>
                        <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required>
                        <div class="invalid-feedback">Prosím, zadajte používateľské meno.</div>
                        @error('username')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                        <div class="invalid-feedback" id="emailError">Prosím, zadajte platný email (musí obsahovať @).</div>
                        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Heslo</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback" id="passwordError">Heslo musí mať aspoň 8 znakov, obsahovať veľké písmeno, malé písmeno a číslo.</div>
                        @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Potvrdenie hesla</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        <div class="invalid-feedback" id="passwordConfirmError">Heslá sa musia zhodovať.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill">Vytvoriť účet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('registerForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    const usernameInput = document.getElementById('username');

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

    function passwordsMatch(p1, p2) {
        return p1 === p2 && p1.length > 0;
    }

    usernameInput.addEventListener('input', () => setValidity(usernameInput, !!usernameInput.value));
    emailInput.addEventListener('input', () => setValidity(emailInput, validateEmail(emailInput.value)));
    passwordInput.addEventListener('input', () => setValidity(passwordInput, validatePassword(passwordInput.value)));
    passwordConfirmInput.addEventListener('input', () => setValidity(passwordConfirmInput, passwordsMatch(passwordInput.value, passwordConfirmInput.value)));

    form.addEventListener('submit', (e) => {
        const validUsername = !!usernameInput.value;
        const validEmail = validateEmail(emailInput.value);
        const validPassword = validatePassword(passwordInput.value);
        const validConfirm = passwordsMatch(passwordInput.value, passwordConfirmInput.value);

        setValidity(usernameInput, validUsername);
        setValidity(emailInput, validEmail);
        setValidity(passwordInput, validPassword);
        setValidity(passwordConfirmInput, validConfirm);

        if (!validUsername || !validEmail || !validPassword || !validConfirm) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
})();
</script>
@endsection
