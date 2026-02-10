@extends('layouts.app')

@section('title', $dataset->name)

@section('content')
    @php
        $shareUrl = url('/datasets/share/' . $dataset->share_token);
        $loginRedirectUrl = url('/login?redirect=' . urlencode($shareUrl));
    @endphp

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="h3 mb-0">{{ $dataset->name }}</h1>
            @if ($dataset->is_public)
                <span class="badge text-bg-success">Public</span>
            @else
                <span class="badge text-bg-secondary">Private</span>
            @endif
        </div>

        <div class="d-flex flex-column align-items-stretch align-items-md-end gap-1">
            @auth
                <a href="{{ route('datasets.download', $dataset->id) }}" class="btn btn-primary btn-sm">Download ZIP</a>
            @else
                <a href="{{ $loginRedirectUrl }}" class="btn btn-primary btn-sm">Log in to download</a>
                <div class="text-muted small">You must be logged in to download.</div>
            @endauth
        </div>
    </div>

    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $dataset->name }}</dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9">{{ $dataset->description ?: '—' }}</dd>

            <dt class="col-sm-3">File type</dt>
            <dd class="col-sm-9">{{ $dataset->file_type ?: '—' }}</dd>

            <dt class="col-sm-3">Size</dt>
            <dd class="col-sm-9">{{ $dataset->total_size_human }}</dd>

            <dt class="col-sm-3">Uploaded</dt>
            <dd class="col-sm-9">{{ $dataset->created_at?->format('d.m.Y H:i') }}</dd>
        </dl>
    </div>
@endsection
