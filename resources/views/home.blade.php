@extends('layouts.app')

@section('content')
    <div class="container-lg mt-4 mb-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="h3 mb-0">Verejné datasety</h1>
                    <span class="badge rounded-pill text-bg-primary">
                        {{ isset($datasets) ? $datasets->count() : 0 }}
                    </span>
                </div>
                <div class="text-muted mt-1">
                    Marketplace datasetov – prehľad verejných datasetov (a tvojich súkromných, ak si prihlásený).
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            {{-- LEFT: filters + cards --}}
            <div class="col-12 col-lg-8">
                <div class="card border-0 rounded-4 shadow mb-4">
                    <div class="card-body p-3 p-md-4">
                        <form id="datasetFilterForm" method="GET" action="{{ route('home') }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-lg-6">
                                    <label for="datasetSearch" class="form-label text-muted small mb-1">Vyhľadať</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">🔎</span>
                                        <input
                                            id="datasetSearch"
                                            name="search"
                                            type="search"
                                            class="form-control"
                                            placeholder="Hľadať dataset…"
                                            aria-label="Vyhľadať dataset"
                                            value="{{ request('search') }}"
                                        >
                                        <button class="btn btn-outline-secondary" type="submit">Hľadať</button>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label for="category_id" class="form-label text-muted small mb-1">Kategória</label>
                                    <select id="category_id" name="category_id" class="form-select">
                                        <option value="">Všetky kategórie</option>
                                        @foreach (($categories ?? collect()) as $category)
                                            <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-lg-2 d-flex justify-content-lg-end">
                                    <a href="{{ url('/') }}" class="btn btn-outline-secondary w-100 w-lg-auto">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="datasetCardsContainer">
                    @include('partials.dataset-cards', ['datasets' => $datasets ?? collect()])
                </div>
            </div>

            {{-- RIGHT: sidebar (top lists) --}}
            <div class="col-12 col-lg-4">
                <div class="card border-0 rounded-4 shadow-sm mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="ws-section-title mb-3">Populárne datasety</div>
                        <div class="text-muted small mb-3">Podľa počtu stiahnutí</div>

                        @if (($topDownloads ?? collect())->isEmpty())
                            <div class="text-muted small">Zatiaľ žiadne dáta.</div>
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
                        <div class="ws-section-title mb-3">Najobľúbenejšie</div>
                        <div class="text-muted small mb-3">Podľa počtu likov</div>

                        @if (($topLikes ?? collect())->isEmpty())
                            <div class="text-muted small">Zatiaľ žiadne dáta.</div>
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

            if (!form || !cardsContainer || !categorySelect) {
                return;
            }

            let abortController = null;

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

            async function fetchAndRender() {
                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                const url = buildUrlFromForm();

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

                    // Keep URL in sync (so refresh/share works)
                    window.history.replaceState({}, '', url.toString());
                } catch (e) {
                    // If aborted, silently ignore
                    if (e && e.name === 'AbortError') {
                        return;
                    }
                    // fallback
                    window.location.href = buildUrlFromForm().toString();
                }
            }

            // Trigger AJAX on category change
            categorySelect.addEventListener('change', function () {
                fetchAndRender();
            });

            // Keep normal GET submit for fallback; enhance with AJAX when possible
            form.addEventListener('submit', function (e) {
                // If fetch is available, prevent reload and do AJAX
                if (window.fetch) {
                    e.preventDefault();
                    fetchAndRender();
                }
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

                const tokenEl = document.querySelector('meta[name=\"csrf-token\"]');
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
