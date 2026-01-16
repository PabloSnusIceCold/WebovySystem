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

                // Let the browser continue with normal download via href.
                // We only add side-effect: increment download counter asynchronously.

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
                        // ignore silently, download still proceeds
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
                    // ignore - download still proceeds
                }
            });
        })();
    </script>
@endsection
