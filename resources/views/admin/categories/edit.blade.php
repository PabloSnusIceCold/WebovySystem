@extends('layouts.app')

@section('title', 'Upraviť kategóriu')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h1 class="h3 mb-0">Upraviť kategóriu</h1>
        <a href="{{ url('/admin?tab=categories') }}" class="btn btn-outline-secondary btn-sm">Späť</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold">Formulár obsahuje chyby:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Názov</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Popis</label>
                            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
                            <a href="{{ url('/admin?tab=categories') }}" class="btn btn-outline-secondary">Zrušiť</a>
                        </div>
                    </form>

                    <hr class="my-4">

                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Naozaj odstrániť kategóriu?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Odstrániť kategóriu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

