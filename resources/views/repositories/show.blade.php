@extends('layouts.app')

@section('title', $repository->name)

@section('content')
    @php
        $user = auth()->user();
        $isOwner = $user && ((int) $repository->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        $canShare = (bool) ($isOwner || $isAdmin);

        $datasets = $repository->datasets ?? collect();
    @endphp

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $repository->name }}</h1>
            <div class="text-muted small">
                {{ $repository->description ?: '—' }}
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('repositories.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Back</a>
        </div>
    </div>

    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Created</dt>
            <dd class="col-sm-9">{{ $repository->created_at?->format('d.m.Y H:i') }}</dd>

            <dt class="col-sm-3">Datasets</dt>
            <dd class="col-sm-9">{{ (int) ($repository->datasets_count ?? 0) }}</dd>
        </dl>

        @if ($canShare)
            <hr>
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="fw-semibold mb-1">Share repository</div>
                    <div class="text-muted small">A share link will be generated (token is created only once).</div>
                </div>

                <form id="shareRepositoryForm" action="{{ route('repositories.share', $repository->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill">Share</button>
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
        @endif
    </div>

    <section class="bg-white rounded-3 shadow-sm p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Datasets</h2>
            <span class="text-muted small">{{ $datasets->count() }} dataset(s)</span>
        </div>

        @if ($datasets->isEmpty())
            <div class="alert alert-info mb-0">This repository is empty.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Owner</th>
                            <th>Visibility</th>
                            <th class="text-end">Files</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datasets as $ds)
                            @php
                                $canOpenDataset = (bool) ($ds->is_public || $isOwner || $isAdmin);
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $ds->id }}</td>
                                <td class="fw-semibold">{{ $ds->name }}</td>
                                <td class="text-muted">
                                    {{ $ds->user->username ?? '—' }}<br>
                                    <span class="small">{{ $ds->user->email ?? '' }}</span>
                                </td>
                                <td>
                                    @if ($ds->is_public)
                                        <span class="badge text-bg-success">Public</span>
                                    @else
                                        <span class="badge text-bg-secondary">Private</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ (int) ($ds->files_count ?? 0) }}</td>
                                <td class="text-muted text-nowrap">{{ $ds->created_at?->format('d.m.Y') }}</td>
                                <td class="text-end">
                                    @if ($canOpenDataset)
                                        <a href="{{ route('datasets.show', $ds->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill">Dataset details</a>
                                    @else
                                        <span class="text-muted small">Private</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($canShare)
        @push('scripts')
            <script>
                (function () {
                    const form = document.getElementById('shareRepositoryForm');
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
        @endpush
    @endif
@endsection

