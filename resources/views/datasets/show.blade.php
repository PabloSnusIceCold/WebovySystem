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
        $fileTypesText = $fileTypes->isEmpty() ? '—' : $fileTypes->implode(', ');

        $categories = \App\Models\Category::orderBy('name')->get(['id', 'name']);
    @endphp

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0" id="datasetTitle">{{ $dataset->name }}</h1>
            @auth
                @if ($canManage)
                    <div class="text-muted small">You can edit or delete this dataset directly from this page.</div>
                @endif
            @endauth
        </div>

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

    @auth
        @if ($canManage)
            <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="fw-semibold mb-1">Manage dataset</div>
                        <div class="text-muted small">Changes are saved via AJAX (no page reload).</div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" id="editDatasetBtn" class="btn btn-outline-primary btn-sm">Edit</button>
                        <button type="button" id="deleteDatasetBtn" class="btn btn-outline-danger btn-sm">Delete</button>
                    </div>
                </div>

                <div id="manageDatasetAlert" class="alert ws-alert mt-3 mb-0 d-none" role="alert"></div>

                <form id="datasetInlineEditForm" class="mt-3 d-none">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="datasetNameInput" class="form-label">Name</label>
                            <input
                                id="datasetNameInput"
                                name="name"
                                type="text"
                                class="form-control ws-form-control"
                                maxlength="255"
                                required
                                value="{{ $dataset->name }}"
                            />
                            <div class="invalid-feedback" id="datasetNameError"></div>
                        </div>

                        <div class="col-12">
                            <label for="datasetDescInput" class="form-label">Description</label>
                            <textarea
                                id="datasetDescInput"
                                name="description"
                                class="form-control ws-form-control"
                                rows="4"
                            >{{ $dataset->description }}</textarea>
                            <div class="invalid-feedback" id="datasetDescError"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="datasetCategoryInput" class="form-label">Category</label>
                            <select id="datasetCategoryInput" name="category_id" class="form-select">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (int) $dataset->category_id === (int) $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="datasetCategoryError"></div>
                        </div>

                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="datasetPublicInput" name="is_public" {{ $dataset->is_public ? 'checked' : '' }}>
                                <label class="form-check-label" for="datasetPublicInput">Public dataset</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" id="cancelInlineEditBtn" class="btn btn-outline-secondary btn-sm">Cancel</button>
                        <button type="submit" id="saveInlineEditBtn" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="fw-semibold mb-1">Manage files</div>
                        <div class="text-muted small">Add or remove files without leaving this page.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" id="addFilesBtn" class="btn btn-outline-secondary btn-sm">Add files</button>
                    </div>
                </div>

                <form id="datasetAddFilesForm" class="mt-3 d-none" enctype="multipart/form-data">
                    <input type="file" id="datasetFilesInput" name="files[]" class="form-control" multiple accept=".csv,.txt,.xlsx,.json,.xml,.arff,.zip">
                    <div class="text-muted small mt-1">Supported: .csv .txt .xlsx .json .xml .arff .zip</div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" id="cancelAddFilesBtn" class="btn btn-outline-secondary btn-sm">Cancel</button>
                        <button type="submit" id="uploadFilesBtn" class="btn btn-primary btn-sm">Upload</button>
                    </div>
                </form>
            </div>
        @endif
    @endauth

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
            <dd class="col-sm-9" id="datasetNameText">{{ $dataset->name }}</dd>

            <dt class="col-sm-3">Owner</dt>
            <dd class="col-sm-9">
                {{ $dataset->user?->username ?? $dataset->user?->email ?? '—' }}
            </dd>

            <dt class="col-sm-3">Category</dt>
            <dd class="col-sm-9" id="datasetCategoryText">{{ $dataset->category->name ?? '—' }}</dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9" id="datasetDescriptionText">{{ $dataset->description ?: '—' }}</dd>

            <dt class="col-sm-3">File types</dt>
            <dd class="col-sm-9">{{ $fileTypesText }}</dd>

            <dt class="col-sm-3">Files</dt>
            <dd class="col-sm-9" id="datasetFilesCountText">{{ $fileCount }}</dd>

            <dt class="col-sm-3">Total size</dt>
            <dd class="col-sm-9">{{ $dataset->total_size_human }}</dd>

            <dt class="col-sm-3">Uploaded</dt>
            <dd class="col-sm-9">{{ $dataset->created_at?->format('d.m.Y H:i') }}</dd>
        </dl>
    </div>

    <section class="bg-white rounded-3 shadow-sm p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Files</h2>
            <span class="text-muted small"><span id="datasetFilesCountBadge">{{ $dataset->files?->count() ?? 0 }}</span> file(s)</span>
        </div>

        @if (($dataset->files ?? collect())->isEmpty())
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
                        @foreach ($dataset->files as $file)
                            <tr>
                                <td class="fw-semibold">{{ $file->file_name }}</td>
                                <td class="text-muted">{{ $file->file_type ?: '—' }}</td>
                                <td class="text-end text-muted">{{ $file->size_human }}</td>
                                <td class="text-end">
                                    @auth
                                        <a href="{{ route('files.download', $file->id) }}" class="btn btn-outline-primary btn-sm">Download</a>
                                        @if ($canManage)
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm ms-2 js-delete-dataset-file"
                                                data-file-id="{{ $file->id }}"
                                            >Delete</button>
                                        @endif
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

    @auth
        @if ($canManage)
            <script>
                (function () {
                    const editBtn = document.getElementById('editDatasetBtn');
                    const deleteBtn = document.getElementById('deleteDatasetBtn');
                    const form = document.getElementById('datasetInlineEditForm');
                    const cancelBtn = document.getElementById('cancelInlineEditBtn');
                    const saveBtn = document.getElementById('saveInlineEditBtn');

                    const nameInput = document.getElementById('datasetNameInput');
                    const descInput = document.getElementById('datasetDescInput');
                    const categoryInput = document.getElementById('datasetCategoryInput');
                    const publicInput = document.getElementById('datasetPublicInput');
                    const nameError = document.getElementById('datasetNameError');
                    const descError = document.getElementById('datasetDescError');
                    const categoryError = document.getElementById('datasetCategoryError');

                    const titleEl = document.getElementById('datasetTitle');
                    const nameTextEl = document.getElementById('datasetNameText');
                    const descTextEl = document.getElementById('datasetDescriptionText');
                    const categoryTextEl = document.getElementById('datasetCategoryText');
                    const filesCountTextEl = document.getElementById('datasetFilesCountText');
                    const filesCountBadgeEl = document.getElementById('datasetFilesCountBadge');
                    const filesCountTableBadgeEl = document.getElementById('datasetFilesCountBadge');

                    const alertBox = document.getElementById('manageDatasetAlert');

                    if (!editBtn || !deleteBtn || !form || !cancelBtn || !saveBtn || !nameInput || !descInput || !alertBox) {
                        return;
                    }

                    const updateUrl = @json(route('datasets.update.ajax', $dataset->id));
                    const deleteUrl = @json(route('datasets.destroy.ajax', $dataset->id));
                    const addFilesUrl = @json(route('datasets.files.add.ajax', $dataset->id));
                    const deleteFileUrlTemplate = @json(url('/datasets/' . $dataset->id . '/files/__FILE__/ajax'));

                    const addFilesBtn = document.getElementById('addFilesBtn');
                    const addFilesForm = document.getElementById('datasetAddFilesForm');
                    const filesInput = document.getElementById('datasetFilesInput');
                    const cancelAddFilesBtn = document.getElementById('cancelAddFilesBtn');
                    const uploadFilesBtn = document.getElementById('uploadFilesBtn');

                    function setAlert(type, message) {
                        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info', 'alert-warning');
                        alertBox.classList.add(type === 'success' ? 'alert-success' : (type === 'danger' ? 'alert-danger' : 'alert-info'));
                        alertBox.textContent = message;
                    }

                    function hideAlert() {
                        alertBox.classList.add('d-none');
                        alertBox.textContent = '';
                        alertBox.classList.remove('alert-success', 'alert-danger', 'alert-info', 'alert-warning');
                    }

                    function clearErrors() {
                        if (nameError) nameError.textContent = '';
                        if (descError) descError.textContent = '';
                        if (categoryError) categoryError.textContent = '';
                        nameInput.classList.remove('is-invalid');
                        descInput.classList.remove('is-invalid');
                        if (categoryInput) categoryInput.classList.remove('is-invalid');
                    }

                    function showForm() {
                        hideAlert();
                        clearErrors();
                        form.classList.remove('d-none');
                    }

                    function hideForm() {
                        clearErrors();
                        form.classList.add('d-none');
                    }

                    function getCsrfToken() {
                        const tokenEl = document.querySelector('meta[name="csrf-token"]');
                        return tokenEl ? tokenEl.getAttribute('content') : null;
                    }

                    function setFilesCount(count) {
                        if (filesCountTextEl) filesCountTextEl.textContent = String(count);
                        if (filesCountBadgeEl) filesCountBadgeEl.textContent = String(count);
                        if (filesCountTableBadgeEl) filesCountTableBadgeEl.textContent = String(count);
                    }

                    function findCategoryNameById(id) {
                        if (!categoryInput) return '';
                        const opt = categoryInput.querySelector(`option[value="${CSS.escape(String(id))}"]`);
                        return opt ? (opt.textContent || '').trim() : '';
                    }

                    editBtn.addEventListener('click', function () {
                        if (form.classList.contains('d-none')) {
                            showForm();
                            nameInput.focus();
                        } else {
                            hideForm();
                        }
                    });

                    cancelBtn.addEventListener('click', function () {
                        // reset to current visible values
                        nameInput.value = (nameTextEl ? nameTextEl.textContent : nameInput.value) || '';
                        const currentDesc = (descTextEl ? descTextEl.textContent : '') || '';
                        descInput.value = currentDesc === '—' ? '' : currentDesc;
                        if (categoryInput) {
                            // Keep current selected value as-is; user can reopen edit.
                        }
                        hideForm();
                        hideAlert();
                    });

                    form.addEventListener('submit', async function (e) {
                        e.preventDefault();
                        hideAlert();
                        clearErrors();

                        const csrfToken = getCsrfToken();

                        const payload = new URLSearchParams();
                        payload.set('name', nameInput.value || '');
                        payload.set('description', descInput.value || '');
                        if (categoryInput) payload.set('category_id', categoryInput.value || '');
                        payload.set('is_public', publicInput && publicInput.checked ? '1' : '0');

                        saveBtn.disabled = true;
                        const oldText = saveBtn.textContent;
                        saveBtn.textContent = 'Saving…';

                        try {
                            const res = await fetch(updateUrl, {
                                method: 'PUT',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                                },
                                body: payload.toString(),
                            });

                            if (res.status === 422) {
                                const data = await res.json().catch(() => null);
                                const errors = data && data.errors ? data.errors : {};

                                if (errors.name && errors.name[0]) {
                                    nameInput.classList.add('is-invalid');
                                    if (nameError) nameError.textContent = String(errors.name[0]);
                                }
                                if (errors.description && errors.description[0]) {
                                    descInput.classList.add('is-invalid');
                                    if (descError) descError.textContent = String(errors.description[0]);
                                }
                                if (errors.category_id && errors.category_id[0] && categoryInput) {
                                    categoryInput.classList.add('is-invalid');
                                    if (categoryError) categoryError.textContent = String(errors.category_id[0]);
                                }
                                setAlert('danger', 'Please fix the highlighted errors.');
                                return;
                            }

                            if (!res.ok) {
                                const data = await res.json().catch(() => null);
                                setAlert('danger', (data && data.message) ? String(data.message) : 'Update failed.');
                                return;
                            }

                            const data = await res.json().catch(() => null);
                            if (!data || data.success !== true || !data.dataset) {
                                setAlert('danger', 'Unexpected server response.');
                                return;
                            }

                            const newName = data.dataset.name ?? '';
                            const newDesc = data.dataset.description ?? '';
                            const newCategoryId = data.dataset.category_id;

                            if (titleEl) titleEl.textContent = newName;
                            if (nameTextEl) nameTextEl.textContent = newName;
                            if (descTextEl) descTextEl.textContent = (newDesc && String(newDesc).trim() !== '') ? newDesc : '—';
                            if (categoryTextEl && newCategoryId) {
                                const catName = findCategoryNameById(newCategoryId) || '';
                                categoryTextEl.textContent = catName || categoryTextEl.textContent;
                            }

                            hideForm();
                            setAlert('success', 'Saved successfully.');
                        } catch (err) {
                            setAlert('danger', 'Network error. Please try again.');
                        } finally {
                            saveBtn.disabled = false;
                            saveBtn.textContent = oldText;
                        }
                    });

                    deleteBtn.addEventListener('click', async function () {
                        hideAlert();
                        const ok = confirm('Are you sure you want to delete this dataset? This action cannot be undone.');
                        if (!ok) return;

                        const csrfToken = getCsrfToken();

                        deleteBtn.disabled = true;
                        const oldText = deleteBtn.textContent;
                        deleteBtn.textContent = 'Deleting…';

                        try {
                            const res = await fetch(deleteUrl, {
                                method: 'DELETE',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                                },
                            });

                            if (!res.ok) {
                                const data = await res.json().catch(() => null);
                                setAlert('danger', (data && data.message) ? String(data.message) : 'Delete failed.');
                                return;
                            }

                            const data = await res.json().catch(() => null);
                            window.location.href = (data && data.redirect_url) ? String(data.redirect_url) : '/';
                        } catch (err) {
                            setAlert('danger', 'Network error. Please try again.');
                        } finally {
                            deleteBtn.disabled = false;
                            deleteBtn.textContent = oldText;
                        }
                    });

                    // --- Files: add ---
                    if (addFilesBtn && addFilesForm && filesInput && cancelAddFilesBtn && uploadFilesBtn) {
                        addFilesBtn.addEventListener('click', function () {
                            addFilesForm.classList.toggle('d-none');
                            if (!addFilesForm.classList.contains('d-none')) {
                                filesInput.focus();
                            }
                        });

                        cancelAddFilesBtn.addEventListener('click', function () {
                            filesInput.value = '';
                            addFilesForm.classList.add('d-none');
                            hideAlert();
                        });

                        addFilesForm.addEventListener('submit', async function (e) {
                            e.preventDefault();
                            hideAlert();

                            const csrfToken = getCsrfToken();

                            const fd = new FormData();
                            const selected = filesInput.files ? Array.from(filesInput.files) : [];
                            if (selected.length < 1) {
                                setAlert('danger', 'Please select at least one file.');
                                return;
                            }
                            selected.forEach(f => fd.append('files[]', f));

                            uploadFilesBtn.disabled = true;
                            const oldText = uploadFilesBtn.textContent;
                            uploadFilesBtn.textContent = 'Uploading…';

                            try {
                                const res = await fetch(addFilesUrl, {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                                    },
                                    body: fd,
                                });

                                if (res.status === 422) {
                                    const data = await res.json().catch(() => null);
                                    const msg = (data && data.message) ? String(data.message) : 'Validation failed.';
                                    setAlert('danger', msg);
                                    return;
                                }

                                if (!res.ok) {
                                    const data = await res.json().catch(() => null);
                                    setAlert('danger', (data && data.message) ? String(data.message) : 'Upload failed.');
                                    return;
                                }

                                const data = await res.json().catch(() => null);
                                if (!data || data.success !== true || !Array.isArray(data.files)) {
                                    setAlert('danger', 'Unexpected server response.');
                                    return;
                                }

                                const tbody = document.querySelector('#datasetFilesTable tbody');
                                if (tbody) {
                                    data.files.forEach(f => {
                                        const tr = document.createElement('tr');
                                        tr.innerHTML = `
                                            <td class="fw-semibold">${escapeHtml(String(f.file_name || ''))}</td>
                                            <td class="text-muted">${escapeHtml(String(f.file_type || '—'))}</td>
                                            <td class="text-end text-muted">${escapeHtml(String(f.size_human || ''))}</td>
                                            <td class="text-end">
                                                <a href="/files/${encodeURIComponent(String(f.id))}/download" class="btn btn-outline-primary btn-sm">Download</a>
                                                <button type="button" class="btn btn-outline-danger btn-sm ms-2 js-delete-dataset-file" data-file-id="${escapeHtml(String(f.id))}">Delete</button>
                                            </td>
                                        `.trim();
                                        tbody.appendChild(tr);
                                    });
                                }

                                if (typeof data.files_count === 'number') {
                                    setFilesCount(data.files_count);
                                }

                                filesInput.value = '';
                                addFilesForm.classList.add('d-none');
                                setAlert('success', 'Files uploaded successfully.');
                            } catch (err) {
                                setAlert('danger', 'Network error. Please try again.');
                            } finally {
                                uploadFilesBtn.disabled = false;
                                uploadFilesBtn.textContent = oldText;
                            }
                        });
                    }

                    // --- Files: delete (event delegation) ---
                    document.addEventListener('click', async function (e) {
                        const btn = e.target.closest('.js-delete-dataset-file');
                        if (!btn) return;

                        const fileId = btn.getAttribute('data-file-id');
                        if (!fileId) return;

                        const ok = confirm('Delete this file?');
                        if (!ok) return;

                        const csrfToken = getCsrfToken();
                        const url = deleteFileUrlTemplate.replace('__FILE__', encodeURIComponent(String(fileId)));

                        btn.disabled = true;
                        const oldText = btn.textContent;
                        btn.textContent = 'Deleting…';

                        try {
                            const res = await fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                                },
                            });

                            if (!res.ok) {
                                const data = await res.json().catch(() => null);
                                setAlert('danger', (data && data.message) ? String(data.message) : 'Delete failed.');
                                return;
                            }

                            const data = await res.json().catch(() => null);
                            const tr = btn.closest('tr');
                            if (tr) tr.remove();

                            if (data && typeof data.files_count === 'number') {
                                setFilesCount(data.files_count);
                            }

                            setAlert('success', 'File deleted.');
                        } catch (err) {
                            setAlert('danger', 'Network error. Please try again.');
                        } finally {
                            btn.disabled = false;
                            btn.textContent = oldText;
                        }
                    });

                    function escapeHtml(str) {
                        return String(str)
                            .replaceAll('&', '&amp;')
                            .replaceAll('<', '&lt;')
                            .replaceAll('>', '&gt;')
                            .replaceAll('"', '&quot;')
                            .replaceAll("'", '&#039;');
                    }
                })();
            </script>
        @endif
    @endauth
@endsection
