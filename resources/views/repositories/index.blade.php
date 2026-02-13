@extends('layouts.app')

@section('title', 'My repositories')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">My repositories</h1>
            <div class="text-muted small">A repository groups your datasets in one place.</div>
        </div>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRepositoryModal">
            Create new repository
        </button>
    </div>

    <form method="GET" action="{{ route('repositories.index') }}" class="mb-4">
        <div class="input-group">
            <span class="input-group-text bg-white">🔎</span>
            <input type="search" name="search" class="form-control" placeholder="Search repositories by name" value="{{ $search ?? request('search') }}">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('repositories.index') }}">Reset</a>
        </div>
    </form>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($repositories->isEmpty())
        <div class="alert alert-info mb-0">You don't have any repositories yet.</div>
    @else
        <div class="row g-3">
            @foreach ($repositories as $repo)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card border-0 rounded-4 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div>
                                    <div class="fw-bold">{{ $repo->name }}</div>
                                    @if ($repo->description)
                                        <div class="text-muted small">{{ $repo->description }}</div>
                                    @endif
                                </div>
                                <span class="badge text-bg-primary">{{ (int) $repo->datasets_count }} datasets</span>
                            </div>

                            <div class="text-muted small mt-2">
                                Created: {{ $repo->created_at?->format('d.m.Y H:i') }}
                            </div>

                            <div class="mt-3">
                                <a href="{{ route('repositories.show', $repo->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $repositories->links() }}
        </div>
    @endif

    {{-- Modal: Create repository --}}
    <div class="modal fade" id="createRepositoryModal" tabindex="-1" aria-labelledby="createRepositoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRepositoryModalLabel">Create new repository</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('repositories.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="repoName" class="form-label">Repository name</label>
                                <input id="repoName" type="text" name="name" class="form-control" maxlength="255" required>
                            </div>

                            <div class="col-12">
                                <label for="repoDesc" class="form-label">Description (optional)</label>
                                <textarea id="repoDesc" name="description" class="form-control" rows="3" maxlength="2000"></textarea>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <div class="fw-semibold">Select datasets for this repository</div>
                                    <div class="text-muted small">Only your datasets are shown (paginated).</div>
                                </div>

                                @if ($datasets->isEmpty())
                                    <div class="alert alert-info mb-0">You don't have any datasets yet. Upload one first.</div>
                                @else
                                    <div id="wsRepoDatasetsModalContainer">
                                        @include('repositories.partials.datasets-modal-table', ['datasets' => $datasets])
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-body border-top" style="position: sticky; bottom: 0; z-index: 5;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modalEl = document.getElementById('createRepositoryModal');
            if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

            const form = modalEl.querySelector('form');
            const container = document.getElementById('wsRepoDatasetsModalContainer');
            if (!form || !container) return;

            const STORAGE_KEY = 'ws_selected_repository_dataset_ids';

            function getSelectedSet() {
                try {
                    const raw = sessionStorage.getItem(STORAGE_KEY);
                    const arr = raw ? JSON.parse(raw) : [];
                    return new Set(Array.isArray(arr) ? arr.map(String) : []);
                } catch (e) {
                    return new Set();
                }
            }

            function saveSelectedSet(set) {
                try {
                    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(set)));
                } catch (e) {
                    // ignore
                }
            }

            function syncCheckboxesFromStorage(scopeEl) {
                const selected = getSelectedSet();
                const root = scopeEl || modalEl;
                const inputs = root.querySelectorAll('input[type="checkbox"][name="dataset_ids[]"]');
                inputs.forEach((cb) => {
                    cb.checked = selected.has(String(cb.value));
                });
            }

            function updateStorageFromCheckbox(cb) {
                const selected = getSelectedSet();
                const val = String(cb.value);
                if (cb.checked) selected.add(val);
                else selected.delete(val);
                saveSelectedSet(selected);
            }

            // Persist selections even when list changes
            modalEl.addEventListener('change', (e) => {
                const target = e.target;
                if (!target || !target.matches('input[type="checkbox"][name="dataset_ids[]"]')) return;
                updateStorageFromCheckbox(target);
            });

            // Before submit, inject hidden inputs for ids selected on other pages
            form.addEventListener('submit', () => {
                form.querySelectorAll('input[type="hidden"][data-ws-selected="1"]').forEach((el) => el.remove());

                const selected = getSelectedSet();
                selected.forEach((id) => {
                    const exists = form.querySelector('input[type="checkbox"][name="dataset_ids[]"][value="' + CSS.escape(String(id)) + '"]');
                    if (exists) return;

                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'dataset_ids[]';
                    hidden.value = String(id);
                    hidden.setAttribute('data-ws-selected', '1');
                    form.appendChild(hidden);
                });

                try { sessionStorage.removeItem(STORAGE_KEY); } catch (e) {}
            });

            async function fetchDatasetsPage(url) {
                try {
                    const res = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!res.ok) return;

                    const data = await res.json();
                    if (!data || data.success !== true || !data.html) return;

                    container.innerHTML = data.html;
                    syncCheckboxesFromStorage(container);
                } catch (e) {
                    // ignore
                }
            }

            // Intercept pagination clicks inside the modal and load via AJAX (no page reload)
            modalEl.addEventListener('click', (e) => {
                const link = e.target && e.target.closest ? e.target.closest('.ws-modal-datasets-pagination a') : null;
                if (!link) return;

                e.preventDefault();
                e.stopPropagation();

                // Convert paginator URL to our AJAX endpoint
                try {
                    const u = new URL(link.getAttribute('href'), window.location.href);
                    const datasetPage = u.searchParams.get('dataset_page') || u.searchParams.get('page') || '1';

                    const ajaxUrl = new URL('{{ route('repositories.modal.datasets') }}', window.location.href);
                    ajaxUrl.searchParams.set('dataset_page', datasetPage);

                    fetchDatasetsPage(ajaxUrl.toString());
                } catch (err) {
                    // ignore
                }
            });

            // When modal opens, ensure checkboxes match storage (important for first render)
            modalEl.addEventListener('shown.bs.modal', () => {
                syncCheckboxesFromStorage(modalEl);
            });

            // Initial sync
            syncCheckboxesFromStorage(modalEl);
        })();
    </script>
@endpush
