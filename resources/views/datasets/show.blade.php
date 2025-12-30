@extends('layouts.app')

@section('title', $dataset->name)

@section('content')
    @php
        $sizeBytes = (int) ($dataset->file_size ?? 0);
        $sizeMb = $sizeBytes > 0 ? round($sizeBytes / 1048576, 2) : null;
    @endphp

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h1 class="h3 mb-0">{{ $dataset->name }}</h1>
        <a href="{{ route('datasets.index') }}" class="btn btn-outline-secondary btn-sm">Späť</a>
    </div>

    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Názov</dt>
            <dd class="col-sm-9">{{ $dataset->name }}</dd>

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

