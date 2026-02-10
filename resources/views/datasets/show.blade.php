@extends('layouts.app')

@section('title', $dataset->name)

@section('content')
    @php
        $user = auth()->user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        $canDownload = (bool) ($dataset->is_public || $isOwner || $isAdmin);
        $canShare = (bool) ($isOwner || $isAdmin);

        $files = $dataset->files ?? collect();
        $fileCount = $files->count();
        $fileTypes = $files->pluck('file_type')->filter()->map(fn ($t) => strtoupper((string) $t))->unique()->values();
        $fileTypesText = $fileTypes->isEmpty() ? '—' : $fileTypes->implode(', ');
    @endphp

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h1 class="h3 mb-0">{{ $dataset->name }}</h1>

        <div class="d-flex align-items-center gap-2">
            @auth
                @if ($canDownload)
                    <a href="{{ route('datasets.download', $dataset->id) }}" class="btn btn-primary btn-sm">Download ZIP</a>
                @endif
            @else
                <a href="/login" class="btn btn-primary btn-sm">Log in to download</a>
            @endauth

            <a href="{{ route('datasets.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    {{-- Share (AJAX form submit) --}}
    @auth
        @if ($canShare)
            <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="fw-semibold mb-1">Share dataset</div>
                        <div class="text-muted small">A share link will be generated (token is created only once).</div>
                    </div>

                    <form id="shareDatasetForm" action="{{ route('datasets.share', $dataset->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm">Share</button>
                    </form>
                </div>

                @if (session('share_url'))
                    <div id="shareResult" class="alert alert-info mt-3 mb-0">
                        <div class="fw-semibold">Share link:</div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <a id="shareUrlLink" href="{{ session('share_url') }}" target="_blank" rel="noopener" class="text-break">{{ session('share_url') }}</a>
                            <button id="copyShareBtn" type="button" class="btn btn-sm btn-primary">Copy</button>
                        </div>
                    </div>
                @else
                    <div id="shareResult" class="alert alert-info mt-3 mb-0 d-none">
                        <div class="fw-semibold">Share link:</div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <a id="shareUrlLink" href="#" target="_blank" rel="noopener" class="text-break"></a>
                            <button id="copyShareBtn" type="button" class="btn btn-sm btn-primary">Copy</button>
                        </div>
                    </div>
                @endif

                <div id="shareError" class="alert alert-danger mt-3 mb-0 d-none"></div>
            </div>
        @endif
    @endauth

    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $dataset->name }}</dd>

            <dt class="col-sm-3">Owner</dt>
            <dd class="col-sm-9">
                {{ $dataset->user?->username ?? $dataset->user?->email ?? '—' }}
            </dd>

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

    <section class="bg-white rounded-3 shadow-sm p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Files</h2>
            <span class="text-muted small">{{ $dataset->files?->count() ?? 0 }} file(s)</span>
        </div>

        @if (($dataset->files ?? collect())->isEmpty())
            <div class="alert alert-info mb-0">This dataset has no files yet.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>File name</th>
                            <th>Type</th>
                            <th class="text-end">Size</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dataset->files as $file)
                            <tr>
                                <td class="fw-semibold">{{ $file->file_name }}</td>
                                <td class="text-muted">{{ $file->file_type ?: '—' }}</td>
                                <td class="text-end text-muted">{{ $file->size_human }}</td>
                                <td class="text-end">
                                    @auth
                                        <a href="{{ route('files.download', $file->id) }}" class="btn btn-outline-primary btn-sm">Download</a>
                                    @else
                                        <a href="/login" class="btn btn-outline-primary btn-sm">Log in</a>
                                    @endauth
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @auth
        @if ($canShare)
            <script>
                (function () {
                    const form = document.getElementById('shareDatasetForm');
                    const resultBox = document.getElementById('shareResult');
                    const errorBox = document.getElementById('shareError');
                    const linkEl = document.getElementById('shareUrlLink');
                    const copyBtn = document.getElementById('copyShareBtn');

                    if (!form || !resultBox || !errorBox || !linkEl || !copyBtn) {
                        return;
                    }

                    function showError(message) {
                        errorBox.textContent = message || 'Something went wrong.';
                        errorBox.classList.remove('d-none');
                    }

                    function hideError() {
                        errorBox.classList.add('d-none');
                        errorBox.textContent = '';
                    }

                    async function copyToClipboard(text) {
                        try {
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                await navigator.clipboard.writeText(text);
                                return true;
                            }
                        } catch (e) {
                            // ignore
                        }
                        return false;
                    }

                    copyBtn.addEventListener('click', async function () {
                        const url = linkEl.getAttribute('href') || '';
                        if (!url || url === '#') return;

                        const ok = await copyToClipboard(url);
                        if (ok) {
                            copyBtn.textContent = 'Copied';
                            setTimeout(() => (copyBtn.textContent = 'Copy'), 1500);
                        } else {
                            alert('Copy failed. Please copy the link manually.');
                        }
                    });

                    form.addEventListener('submit', async function (e) {
                        e.preventDefault();
                        hideError();

                        const url = form.getAttribute('action');
                        const token = form.querySelector('input[name="_token"]')?.value;

                        const submitBtn = form.querySelector('button[type="submit"]');
                        const oldText = submitBtn ? submitBtn.textContent : '';
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.textContent = 'Generating…';
                        }

                        try {
                            const res = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                },
                                body: new URLSearchParams({ _token: token || '' }).toString(),
                            });

                            if (!res.ok) {
                                let msg = 'Failed to generate share link.';
                                try {
                                    const data = await res.json();
                                    if (data && data.message) msg = data.message;
                                } catch (_) {
                                    // ignore
                                }
                                showError(msg);
                                return;
                            }

                            const data = await res.json();
                            if (!data || data.success !== true || !data.share_url) {
                                showError('Unexpected server response.');
                                return;
                            }

                            // Render/refresh result
                            linkEl.textContent = data.share_url;
                            linkEl.setAttribute('href', data.share_url);
                            resultBox.classList.remove('d-none');
                        } catch (err) {
                            showError('Network error. Please try again.');
                        } finally {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = oldText || 'Share';
                            }
                        }
                    });
                })();
            </script>
        @endif
    @endauth
@endsection
