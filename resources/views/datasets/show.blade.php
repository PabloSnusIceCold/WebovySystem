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
                    <a href="{{ route('datasets.download', $dataset->id) }}" class="btn btn-primary btn-sm">Stiahnuť</a>
                @endif
            @else
                <a href="/login" class="btn btn-primary btn-sm">Prihlásiť sa pre stiahnutie</a>
            @endauth

            <a href="{{ route('datasets.index') }}" class="btn btn-outline-secondary btn-sm">Späť</a>
        </div>
    </div>

    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4">
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
@endsection
