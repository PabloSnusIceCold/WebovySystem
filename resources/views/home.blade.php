@extends('layouts.app')

@section('content')
    @php
        $layout = request('layout', 'cards');
        $layout = in_array($layout, ['cards', 'list'], true) ? $layout : 'cards';
    @endphp

    <div class="mt-4 mb-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="h3 mb-0">Public datasets</h1>
                    <span class="badge rounded-pill text-bg-primary">
                        {{ isset($datasets) ? $datasets->count() : 0 }}
                    </span>
                </div>
                <div class="text-muted mt-1">
                    Dataset marketplace – browse public datasets (and your private ones when logged in).
                </div>
            </div>

            {{-- Layout switcher --}}
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group" role="group" aria-label="Dataset view">
                    <a
                        href="{{ request()->fullUrlWithQuery(['layout' => 'cards']) }}"
                        class="btn btn-sm {{ $layout === 'cards' ? 'btn-primary' : 'btn-outline-primary' }}"
                        title="Card view"
                    >
                        Cards
                    </a>
                    <a
                        href="{{ request()->fullUrlWithQuery(['layout' => 'list']) }}"
                        class="btn btn-sm {{ $layout === 'list' ? 'btn-primary' : 'btn-outline-primary' }}"
                        title="List view"
                    >
                        List
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            {{-- LEFT: filters + datasets --}}
            <div class="col-12 col-lg-8">
                <div class="card border-0 rounded-4 shadow mb-4">
                    <div class="card-body p-3 p-md-4">
                        <form id="datasetFilterForm" method="GET" action="{{ route('home') }}">
                            <input type="hidden" name="layout" value="{{ $layout }}">

                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-lg-6">
                                    <label for="datasetSearch" class="form-label text-muted small mb-1">Search</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">🔎</span>
                                        <input
                                            id="datasetSearch"
                                            name="search"
                                            type="search"
                                            class="form-control"
                                            placeholder="Search datasets…"
                                            aria-label="Search datasets"
                                            value="{{ request('search') }}"
                                        >
                                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label for="category_id" class="form-label text-muted small mb-1">Category</label>
                                    <select id="category_id" name="category_id" class="form-select">
                                        <option value="">All categories</option>
                                        @foreach (($categories ?? collect()) as $category)
                                            <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-lg-2 d-flex justify-content-lg-end">
                                    <button name="reset" value="1" class="btn btn-outline-secondary w-100 w-lg-auto" type="submit">
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="datasetCardsContainer" data-layout="{{ $layout }}">
                    @if ($layout === 'list')
                        @include('partials.dataset-list', ['datasets' => $datasets ?? collect()])
                    @else
                        @include('partials.dataset-cards', ['datasets' => $datasets ?? collect()])
                    @endif
                </div>
            </div>

            {{-- RIGHT: sidebar (top lists) --}}
            <div class="col-12 col-lg-4">
                <div class="card border-0 rounded-4 shadow-sm mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="ws-section-title mb-3">Popular datasets</div>
                        <div class="text-muted small mb-3">By downloads</div>

                        @if (($topDownloads ?? collect())->isEmpty())
                            <div class="text-muted small">No data yet.</div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach (($topDownloads ?? collect()) as $ds)
                                    <a href="{{ route('datasets.show', $ds->id) }}" class="list-group-item list-group-item-action d-flex align-items-start justify-content-between gap-2">
                                        <div class="me-2">
                                            <div class="fw-semibold text-body">{{ $ds->name }}</div>
                                            <div class="text-muted small">{{ $ds->category->name ?? '—' }}</div>
                                        </div>
                                        <span class="badge rounded-pill text-bg-primary">{{ (int) ($ds->download_count ?? 0) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card border-0 rounded-4 shadow-sm">
                    <div class="card-body p-3 p-md-4">
                        <div class="ws-section-title mb-3">Most liked</div>
                        <div class="text-muted small mb-3">By likes</div>

                        @if (($topLikes ?? collect())->isEmpty())
                            <div class="text-muted small">No data yet.</div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach (($topLikes ?? collect()) as $ds)
                                    <a href="{{ route('datasets.show', $ds->id) }}" class="list-group-item list-group-item-action d-flex align-items-start justify-content-between gap-2">
                                        <div class="me-2">
                                            <div class="fw-semibold text-body">{{ $ds->name }}</div>
                                            <div class="text-muted small">{{ $ds->category->name ?? '—' }}</div>
                                        </div>
                                        <span class="badge rounded-pill text-bg-danger">{{ (int) ($ds->likes_count ?? 0) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('datasetFilterForm');
            const cardsContainer = document.getElementById('datasetCardsContainer');
            const categorySelect = document.getElementById('category_id');
            const layoutSwitcher = document.querySelector('.btn-group[aria-label="Dataset view"]');

            if (!form || !cardsContainer || !categorySelect) {
                return;
            }

            let abortController = null;

            function syncLayoutButtons(layout) {
                if (!layoutSwitcher) return;
                const buttons = layoutSwitcher.querySelectorAll('a');
                buttons.forEach((a) => {
                    const href = a.getAttribute('href') || '';
                    let isActive = false;
                    try {
                        const u = new URL(href, window.location.origin);
                        isActive = (u.searchParams.get('layout') || 'cards') === layout;
                    } catch (_) {
                        // ignore
                    }

                    a.classList.toggle('btn-primary', isActive);
                    a.classList.toggle('btn-outline-primary', !isActive);
                });
            }

            function buildUrlFromForm() {
                const url = new URL(form.action, window.location.origin);
                const formData = new FormData(form);

                // Build query string from current form fields
                for (const [key, value] of formData.entries()) {
                    if (value !== null && String(value).trim() !== '') {
                        url.searchParams.set(key, String(value));
                    } else {
                        url.searchParams.delete(key);
                    }
                }

                return url;
            }

            async function fetchAndRender(urlOverride) {
                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                const url = urlOverride instanceof URL ? urlOverride : buildUrlFromForm();

                try {
                    const res = await fetch(url.toString(), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: abortController.signal,
                    });

                    if (!res.ok) {
                        // fallback to full navigation if something unexpected happens
                        window.location.href = url.toString();
                        return;
                    }

                    cardsContainer.innerHTML = await res.text();

                    const layout = url.searchParams.get('layout') || 'cards';
                    cardsContainer.setAttribute('data-layout', layout);

                    // Keep URL in sync (so refresh/share works)
                    window.history.replaceState({}, '', url.toString());

                    // Fix active state
                    syncLayoutButtons(layout);
                } catch (e) {
                    // If aborted, silently ignore
                    if (e && e.name === 'AbortError') {
                        return;
                    }
                    // fallback
                    window.location.href = (urlOverride instanceof URL ? urlOverride : buildUrlFromForm()).toString();
                }
            }

            // Initial sync on load
            syncLayoutButtons(cardsContainer.getAttribute('data-layout') || 'cards');

            // Trigger AJAX on category change
            categorySelect.addEventListener('change', function () {
                fetchAndRender();
            });

            // Keep normal GET submit for fallback; enhance with AJAX when possible
            form.addEventListener('submit', function (e) {
                if (!window.fetch) {
                    return;
                }

                e.preventDefault();

                // Detect which submit button triggered the submit (Reset vs Search)
                const submitter = e.submitter || document.activeElement;
                const isReset = submitter && submitter.getAttribute && submitter.getAttribute('name') === 'reset';

                if (isReset) {
                    // Clear UI fields
                    const searchInput = form.querySelector('input[name="search"]');
                    if (searchInput) searchInput.value = '';
                    if (categorySelect) categorySelect.value = '';

                    // Build reset URL explicitly: keep layout, add reset=1
                    const resetUrl = new URL(form.action, window.location.origin);
                    const layoutInput = form.querySelector('input[name="layout"]');
                    const layout = (layoutInput && layoutInput.value) ? layoutInput.value : 'cards';
                    resetUrl.searchParams.set('layout', layout);
                    resetUrl.searchParams.set('reset', '1');

                    fetchAndRender(resetUrl);
                    return;
                }

                // Normal search/filter submit
                fetchAndRender();
            });

            // Layout switch via links should also re-render with AJAX when possible
            document.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (!link) return;

                // Only handle clicks inside the layout switcher group
                if (!link.closest('.btn-group[aria-label="Dataset view"]')) return;

                if (!window.fetch) return;

                // Prevent full reload
                e.preventDefault();

                const targetUrl = new URL(link.getAttribute('href'), window.location.origin);

                // Sync hidden layout input
                const layoutInput = form.querySelector('input[name="layout"]');
                if (layoutInput) {
                    layoutInput.value = targetUrl.searchParams.get('layout') || 'cards';
                }

                fetchAndRender(targetUrl);
            });

            // --- AJAX #2: increment download count when ZIP download is triggered (event delegation) ---
            document.addEventListener('click', async function (e) {
                const link = e.target.closest('.js-zip-download');
                if (!link) return;

                const datasetId = link.getAttribute('data-dataset-id');
                if (!datasetId) return;

                const tokenEl = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = tokenEl ? tokenEl.getAttribute('content') : null;

                try {
                    const res = await fetch(`/datasets/${datasetId}/download-count`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                        },
                    });

                    if (!res.ok) {
                        return;
                    }

                    const data = await res.json();
                    if (!data || data.success !== true) {
                        return;
                    }

                    const countEl = document.getElementById(`downloadCount-${datasetId}`);
                    if (countEl) {
                        countEl.textContent = String(data.download_count ?? countEl.textContent);
                    }
                } catch (err) {
                    // ignore
                }
            });

            // --- AJAX #3: like/unlike toggle (event delegation) ---
            document.addEventListener('click', async function (e) {
                const btn = e.target.closest('.js-like-toggle');
                if (!btn) return;

                const datasetId = btn.getAttribute('data-dataset-id');
                if (!datasetId) return;

                const tokenEl = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = tokenEl ? tokenEl.getAttribute('content') : null;

                const oldText = btn.textContent;
                btn.disabled = true;

                try {
                    const res = await fetch(`/datasets/${datasetId}/like/toggle`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                        },
                    });

                    if (!res.ok) {
                        btn.disabled = false;
                        btn.textContent = oldText;
                        return;
                    }

                    const data = await res.json();
                    if (!data || data.success !== true) {
                        btn.disabled = false;
                        btn.textContent = oldText;
                        return;
                    }

                    const liked = !!data.liked;
                    const likesCount = data.likes_count;

                    btn.textContent = liked ? '👍 Liked' : '👍 Like';
                    btn.classList.toggle('btn-primary', liked);
                    btn.classList.toggle('btn-outline-primary', !liked);

                    const countEl = document.getElementById(`likesCount-${datasetId}`);
                    if (countEl) {
                        countEl.textContent = String(likesCount ?? countEl.textContent);
                    }
                } catch (err) {
                    btn.textContent = oldText;
                } finally {
                    btn.disabled = false;
                }
            });
        })();
    </script>
@endsection
