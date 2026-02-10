@extends('layouts.app')

@section('title', 'My datasets')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h1 class="h3 mb-0">My datasets</h1>

        <a href="{{ route('datasets.upload') }}" class="btn btn-primary">
            Upload new dataset
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('share_url'))
        <div class="alert alert-info">
            <div class="fw-semibold">You can share this dataset using this link:</div>
            <a href="{{ session('share_url') }}" target="_blank" rel="noopener" class="text-break">{{ session('share_url') }}</a>
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if ($datasets->isEmpty())
        <div class="alert alert-info mb-0">
            You haven't uploaded any datasets yet.
        </div>
    @else
        @php
            $currentUser = auth()->user();
        @endphp

        <div class="d-flex flex-column gap-3">
            @foreach ($datasets as $dataset)
                @php
                    $canDelete = $currentUser && ((int) $currentUser->id === (int) $dataset->user_id || $currentUser->role === 'admin');
                @endphp

                <section class="bg-white rounded-3 shadow-sm p-3 p-md-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-lg-8">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="fw-bold">{{ $dataset->name }}</div>
                                @if ($dataset->is_public)
                                    <span class="badge text-bg-success">Public</span>
                                @else
                                    <span class="badge text-bg-secondary">Private</span>
                                @endif
                            </div>

                            @if ($dataset->description)
                                <div class="text-muted small">{{ $dataset->description }}</div>
                            @endif

                            <div class="text-muted small mt-2">
                                <span class="me-3"><span class="fw-semibold">Category:</span> {{ $dataset->category->name ?? '—' }}</span>
                                <span class="me-3"><span class="fw-semibold">Format:</span> {{ $dataset->file_type ?? '—' }}</span>
                                <span class="me-3"><span class="fw-semibold">Size:</span> {{ $dataset->total_size_human }}</span>
                                <span class="text-nowrap"><span class="fw-semibold">Date:</span> {{ $dataset->created_at?->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                <a href="{{ route('datasets.show', $dataset->id) }}" class="btn btn-outline-secondary btn-sm">View</a>
                                <a href="{{ route('datasets.edit', $dataset->id) }}" class="btn btn-outline-secondary btn-sm">Edit</a>

                                <form action="{{ route('datasets.share', $dataset->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Share</button>
                                </form>

                                @if ($canDelete)
                                    <form action="{{ route('datasets.destroy', $dataset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this dataset?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
@endsection
