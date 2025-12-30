@extends('layouts.app')

@section('content')
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Verejné datasety</h1>
            <div class="text-muted">
                Dostupných: {{ isset($datasets) ? $datasets->count() : 0 }} datasetov
            </div>
        </div>

        <form class="w-100 w-md-auto" style="max-width: 360px;" method="GET" action="{{ route('home') }}">
            <label for="datasetSearch" class="form-label text-muted small mb-1">Vyhľadať</label>
            <div class="input-group">
                <input
                    id="datasetSearch"
                    name="search"
                    type="search"
                    class="form-control"
                    placeholder="Hľadať dataset…"
                    aria-label="Vyhľadať dataset"
                    value="{{ request('search') }}"
                >
                <button class="btn btn-outline-secondary" type="submit">Hľadať</button>
            </div>
        </form>
    </div>

    @php
        $publicDatasets = $datasets ?? collect();
    @endphp

    @if ($publicDatasets->isEmpty())
        <div class="card shadow-sm rounded-3">
            <div class="card-body text-center text-muted">
                Zatiaľ nie sú dostupné žiadne verejné datasety.
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach ($publicDatasets as $dataset)
                @php
                    $sizeBytes = (int) ($dataset->file_size ?? 0);
                    $sizeMb = $sizeBytes > 0 ? round($sizeBytes / 1048576, 2) : null;
                @endphp

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm rounded-3">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 mb-2">{{ $dataset->name }}</h2>

                            <p class="text-muted mb-3 dataset-desc" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                {{ $dataset->description ?? '—' }}
                            </p>

                            <div class="mb-3 text-muted small">
                                <div><span class="fw-semibold">Typ:</span> {{ $dataset->file_type ?? '—' }}</div>
                                <div><span class="fw-semibold">Veľkosť:</span> {{ $sizeMb !== null ? $sizeMb.' MB' : '—' }}</div>
                                <div><span class="fw-semibold">Dátum:</span> {{ $dataset->created_at?->format('d.m.Y H:i') }}</div>
                                <div><span class="fw-semibold">Používateľ:</span> {{ $dataset->user?->username ?? '—' }}</div>
                            </div>

                            <div class="mt-auto d-flex gap-2">
                                <a href="{{ route('datasets.show', $dataset->id) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                                <a href="{{ route('datasets.download', $dataset->id) }}" class="btn btn-primary btn-sm">Stiahnuť</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
