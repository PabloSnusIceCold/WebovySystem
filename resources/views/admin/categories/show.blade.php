@extends('layouts.app')

@section('title', 'Kategória – ' . ($category->name ?? 'Detail'))

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $category->name }}</h1>
            <div class="text-muted">Detail kategórie</div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ url('/admin?tab=categories') }}" class="btn btn-outline-secondary btn-sm">Späť na kategórie</a>
            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-primary btn-sm">Upraviť kategóriu</a>

            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Naozaj odstrániť kategóriu?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Zmazať kategóriu</button>
            </form>
        </div>
    </div>

    {{-- Admin tabs (same style as admin dashboard) --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link" href="{{ url('/admin?tab=users') }}">Používatelia</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ url('/admin?tab=datasets') }}">Datasety</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ url('/admin?tab=categories') }}">Kategórie</a>
        </li>
    </ul>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold mb-2">Informácie</div>

                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Názov</dt>
                        <dd class="col-7 mb-2">{{ $category->name }}</dd>

                        <dt class="col-5 text-muted">Vytvorená</dt>
                        <dd class="col-7 mb-2">{{ $category->created_at?->format('d.m.Y H:i') ?? '—' }}</dd>

                        <dt class="col-5 text-muted">Počet datasetov</dt>
                        <dd class="col-7 mb-0">{{ (int) ($category->datasets_count ?? 0) }}</dd>
                    </dl>

                    @if (!empty($category->description))
                        <hr>
                        <div class="fw-semibold mb-1">Popis</div>
                        <div class="text-muted">{{ $category->description }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <div class="fw-semibold">Datasety v kategórii</div>
                        <div class="text-muted small">{{ (int) ($category->datasets_count ?? 0) }} ks</div>
                    </div>

                    @php
                        $datasets = $category->datasets ?? collect();
                    @endphp

                    @if ($datasets->isEmpty())
                        <div class="alert alert-info mb-0">Táto kategória zatiaľ neobsahuje žiadne datasety.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Názov</th>
                                        <th scope="col">Vlastník</th>
                                        <th scope="col">Viditeľnosť</th>
                                        <th scope="col" class="text-end">Súborov</th>
                                        <th scope="col">Dátum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($datasets as $dataset)
                                        @php
                                            $ownerText = $dataset->user?->username
                                                ?? $dataset->user?->email
                                                ?? '—';

                                            $isPublic = (bool) ($dataset->is_public ?? false);
                                        @endphp

                                        <tr>
                                            <td>{{ $dataset->id }}</td>
                                            <td class="fw-semibold">
                                                {{ $dataset->name }}
                                            </td>
                                            <td>{{ $ownerText }}</td>
                                            <td>
                                                @if ($isPublic)
                                                    <span class="badge text-bg-success">Verejný</span>
                                                @else
                                                    <span class="badge text-bg-secondary">Súkromný</span>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ (int) ($dataset->files_count ?? 0) }}</td>
                                            <td class="text-muted">{{ $dataset->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

