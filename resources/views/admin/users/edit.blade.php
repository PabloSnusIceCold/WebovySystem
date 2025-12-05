@extends('layouts.app')

@section('title', 'Upraviť používateľa')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Upraviť používateľa</h3>

                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input
                                id="username"
                                name="username"
                                type="text"
                                value="{{ old('username', $user->username) }}"
                                class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                                required
                            >
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', $user->email) }}"
                                class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nové heslo <small class="text-muted">(nepovinné)</small></label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.users') }}" class="btn btn-secondary">Zrušiť</a>
                            <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

