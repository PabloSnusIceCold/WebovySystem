@extends('layouts.app')

@section('content')
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Verejné datasety</h1>
            <div class="text-muted">
                Dostupných: {{ isset($datasets) ? $datasets->count() : 0 }} datasetov
            </div>
        </div>

        <form id="datasetFilterForm" class="w-100" style="max-width: 520px;" method="GET" action="{{ route('home') }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-7">
                    <label for="datasetSearch" class="form-label text-muted small mb-1">Vyhľadať</label>
                    <div class="input-group">
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

                <div class="col-12 col-md-5">
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
            </div>
        </form>
    </div>

    <div id="datasetCardsContainer">
        @include('partials.dataset-cards', ['datasets' => $datasets ?? collect()])
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

                    const html = await res.text();
                    cardsContainer.innerHTML = html;

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
        })();
    </script>
@endsection
