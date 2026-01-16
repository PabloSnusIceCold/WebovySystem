@extends('layouts.app')

@section('title', 'Admin – Upraviť dataset')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h1 class="h3 mb-0">Upraviť dataset</h1>
        <a href="{{ url('/admin?tab=datasets') }}" class="btn btn-outline-secondary btn-sm">Späť</a>
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
                    <form action="{{ route('admin.datasets.update', $dataset) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Názov datasetu</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $dataset->name) }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Popis</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                class="form-control @error('description') is-invalid @enderror"
                            >{{ old('description', $dataset->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Kategória</label>
                            <select
                                id="category_id"
                                name="category_id"
                                class="form-select @error('category_id') is-invalid @enderror"
                                required
                            >
                                <option value="" disabled>-- Vyber kategóriu --</option>
                                @foreach ($categories as $category)
                                    <option
                                        value="{{ $category->id }}"
                                        {{ (string) old('category_id', $dataset->category_id) === (string) $category->id ? 'selected' : '' }}
                                    >
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="is_public"
                                id="is_public"
                                value="1"
                                {{ old('is_public', $dataset->is_public) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="is_public">Dataset je verejný</label>
                            @error('is_public')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
                            <a href="{{ url('/admin?tab=datasets') }}" class="btn btn-outline-secondary">Zrušiť</a>
                        </div>
                    </form>
                </div>
            </div>

            @if (($dataset->files ?? collect())->isNotEmpty())
                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Súbory datasetu</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Názov</th>
                                        <th>Typ</th>
                                        <th class="text-end">Veľkosť</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataset->files as $file)
                                        <tr>
                                            <td>{{ $file->file_name }}</td>
                                            <td class="text-muted">{{ $file->file_type ?: '—' }}</td>
                                            <td class="text-end text-muted">{{ $file->size_human }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
