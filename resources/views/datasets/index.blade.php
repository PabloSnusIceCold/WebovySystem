@extends('layouts.app')

@section('title', 'Moje datasety')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h1 class="h3 mb-0">Moje datasety</h1>

        <a href="{{ route('datasets.upload') }}" class="btn btn-primary">
            Nahrať nový dataset
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if ($datasets->isEmpty())
        <div class="alert alert-info mb-0">
            Zatiaľ nemáš nahrané žiadne datasety.
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach ($datasets as $dataset)
                @php
                    $sizeBytes = (int) ($dataset->file_size ?? 0);
                    $sizeMb = $sizeBytes > 0 ? round($sizeBytes / 1048576, 2) : null;
                @endphp

                <section class="bg-white rounded-3 shadow-sm p-3 p-md-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-lg-8">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="fw-bold">{{ $dataset->name }}</div>
                                @if ($dataset->is_public)
                                    <span class="badge text-bg-success">Verejný</span>
                                @else
                                    <span class="badge text-bg-secondary">Súkromný</span>
                                @endif
                            </div>

                            @if ($dataset->description)
                                <div class="text-muted small">{{ $dataset->description }}</div>
                            @endif

                            <div class="text-muted small mt-2">
                                <span class="me-3"><span class="fw-semibold">Kategória:</span> —</span>
                                <span class="me-3"><span class="fw-semibold">Formát:</span> {{ $dataset->file_type ?? '—' }}</span>
                                <span class="me-3"><span class="fw-semibold">Veľkosť:</span> {{ $sizeMb !== null ? $sizeMb.' MB' : '—' }}</span>
                                <span class="text-nowrap"><span class="fw-semibold">Dátum:</span> {{ $dataset->created_at?->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                <a href="{{ route('datasets.show', $dataset->id) }}" class="btn btn-outline-secondary btn-sm">Zobraziť</a>
                                <a href="{{ route('datasets.edit', $dataset->id) }}" class="btn btn-outline-secondary btn-sm">Upraviť</a>

                                <form action="{{ route('datasets.share', $dataset->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Zdieľať</button>
                                </form>

                                <form action="{{ route('datasets.destroy', $dataset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Naozaj chceš odstrániť tento dataset?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Zmazať</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
@endsection
