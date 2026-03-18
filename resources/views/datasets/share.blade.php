@extends('layouts.app')

@section('title', $dataset->name)

@section('content')
    @php
        $user = auth()->user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        $canDownload = (bool) ($dataset->is_public || $isOwner || $isAdmin);
        $canShare = (bool) ($isOwner || $isAdmin);
        $canManage = (bool) ($isOwner || $isAdmin);

        $files = $dataset->files ?? collect();
        $fileCount = $files->count();
        $fileTypes = $files->pluck('file_type')->filter()->map(fn ($t) => strtoupper((string) $t))->unique()->values();
        $fileTypesText = $fileTypes->isEmpty() ? ($dataset->file_type ?: '—') : $fileTypes->implode(', ');

        $loginRedirectUrl = url('/login?redirect=' . urlencode(url('/datasets/share/' . $dataset->share_token)));
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
                @if ($canDownload)
                    <a href="{{ route('datasets.download', $dataset->id) }}" class="btn btn-primary btn-sm">Download ZIP</a>
                @else
                    <div class="text-muted small">You are not authorized to download this dataset.</div>
                @endif
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

            <dt class="col-sm-3">Owner</dt>
            <dd class="col-sm-9">{{ $dataset->user?->username ?? $dataset->user?->email ?? '—' }}</dd>

            <dt class="col-sm-3">Category</dt>
            <dd class="col-sm-9">{{ $dataset->category->name ?? '—' }}</dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9">{{ $dataset->description ?: '—' }}</dd>

            <dt class="col-sm-3">File types</dt>
            <dd class="col-sm-9">{{ $fileTypesText }}</dd>

            <dt class="col-sm-3">Files</dt>
            <dd class="col-sm-9">{{ $fileCount }}</dd>

            <dt class="col-sm-3">Total size</dt>
            <dd class="col-sm-9">{{ $dataset->total_size_human }}</dd>

            <dt class="col-sm-3">Uploaded</dt>
            <dd class="col-sm-9">{{ $dataset->created_at?->format('d.m.Y H:i') }}</dd>
        </dl>
    </div>

    <section class="bg-white rounded-3 shadow-sm p-3 p-md-4 mt-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Files</h2>
            <span class="text-muted small"><span id="datasetFilesCountBadge">{{ $fileCount }}</span> file(s)</span>
        </div>

        @if ($files->isEmpty())
            <div class="alert alert-info mb-0">This dataset has no files yet.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="datasetFilesTable">
                    <thead>
                        <tr>
                            <th>File name</th>
                            <th>Type</th>
                            <th class="text-end">Size</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($files as $file)
                            <tr>
                                <td class="fw-semibold">{{ $file->file_name }}</td>
                                <td class="text-muted">{{ $file->file_type ?: '—' }}</td>
                                <td class="text-end text-muted">{{ $file->size_human }}</td>
                                <td class="text-end">
                                    @auth
                                        @if ($canDownload)
                                            <a href="{{ route('files.download', $file->id) }}" class="btn btn-outline-primary btn-sm">Download</a>
                                        @else
                                            <span class="text-muted small">Download not allowed</span>
                                        @endif

                                        @if ($canManage)
                                            <button type="button" class="btn btn-outline-danger btn-sm ms-2 js-delete-dataset-file" data-file-id="{{ $file->id }}">Delete</button>
                                        @endif
                                    @else
                                        <a href="{{ $loginRedirectUrl }}" class="btn btn-outline-primary btn-sm">Log in</a>
                                    @endauth
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Keep existing share JS and manage JS blocks from original share view --}}
@endsection
