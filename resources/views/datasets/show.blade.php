@extends('layouts.app')

@section('title', $dataset->name)

@section('content')
    @php
        $sizeBytes = (int) ($dataset->file_size ?? 0);
        $sizeMb = $sizeBytes > 0 ? round($sizeBytes / 1048576, 2) : null;

        $user = auth()->user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        $canDownload = (bool) ($dataset->is_public || $isOwner || $isAdmin);
    @endphp

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h1 class="h3 mb-0">{{ $dataset->name }}</h1>

        <div class="d-flex align-items-center gap-2">
            @auth
                @if ($canDownload)
                    <a href="{{ route('datasets.download', $dataset->id) }}" class="btn btn-primary btn-sm">Stiahnuť ZIP</a>
                @endif
            @else
                <a href="/login" class="btn btn-primary btn-sm">Prihlásiť sa pre stiahnutie</a>
            @endauth

            <a href="{{ route('datasets.index') }}" class="btn btn-outline-secondary btn-sm">Späť</a>
        </div>
    </div>

    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Názov</dt>
            <dd class="col-sm-9">{{ $dataset->name }}</dd>

            <dt class="col-sm-3">Kategória</dt>
            <dd class="col-sm-9">{{ $dataset->category->name ?? '—' }}</dd>

            <dt class="col-sm-3">Popis</dt>
            <dd class="col-sm-9">{{ $dataset->description ?: '—' }}</dd>

            <dt class="col-sm-3">Typ súboru</dt>
            <dd class="col-sm-9">{{ $dataset->file_type ?: '—' }}</dd>

            <dt class="col-sm-3">Veľkosť</dt>
            <dd class="col-sm-9">{{ $sizeMb !== null ? $sizeMb.' MB' : '—' }}</dd>

            <dt class="col-sm-3">Dátum nahratia</dt>
            <dd class="col-sm-9">{{ $dataset->created_at?->format('d.m.Y H:i') }}</dd>
        </dl>
    </div>

    <section class="bg-white rounded-3 shadow-sm p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Súbory datasetu</h2>
            <span class="text-muted small">{{ $dataset->files?->count() ?? 0 }} súbor(ov)</span>
        </div>

        @if (($dataset->files ?? collect())->isEmpty())
            <div class="alert alert-info mb-0">Dataset zatiaľ nemá žiadne súbory.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Názov súboru</th>
                            <th>Typ</th>
                            <th class="text-end">Veľkosť</th>
                            <th class="text-end">Akcie</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dataset->files as $file)
                            @php
                                $fileSizeBytes = (int) ($file->file_size ?? 0);
                                $fileSizeMb = $fileSizeBytes > 0 ? round($fileSizeBytes / 1048576, 2) : null;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $file->file_name }}</td>
                                <td class="text-muted">{{ $file->file_type ?: '—' }}</td>
                                <td class="text-end text-muted">{{ $fileSizeMb !== null ? $fileSizeMb.' MB' : '—' }}</td>
                                <td class="text-end">
                                    @auth
                                        <a href="{{ route('files.download', $file->id) }}" class="btn btn-outline-primary btn-sm">Stiahnuť súbor</a>
                                    @else
                                        <a href="/login" class="btn btn-outline-primary btn-sm">Prihlásiť sa</a>
                                    @endauth
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
