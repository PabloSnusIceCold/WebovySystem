@extends('layouts.app')

@section('title', 'Upload dataset')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Upload dataset</div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('datasets.upload.post') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Dataset name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select
                            name="category_id"
                            id="category_id"
                            class="form-select @error('category_id') is-invalid @enderror"
                            required
                        >
                            <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>-- Select a category --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="files" class="form-label">Dataset files (CSV, TXT, XLSX, JSON, XML, ARFF, ZIP)</label>

                        <div id="ws-dropzone" class="ws-dropzone">
                            <div class="d-flex">
                                <div class="ws-dropzone-icon">📁</div>
                                <div>
                                    <strong>Drag & drop files here</strong>
                                    <div class="text-muted small">or click to browse (supported: .csv .txt .xlsx .json .xml .arff .zip)</div>
                                </div>
                                <div class="ms-auto">
                                    <button type="button" id="ws-select-files" class="btn btn-outline-secondary btn-sm">Select files</button>
                                </div>
                            </div>

                            <input
                                type="file"
                                name="files[]"
                                id="files"
                                class="d-none @error('files') is-invalid @enderror"
                                accept=".csv,.txt,.xlsx,.json,.xml,.arff,.zip"
                                multiple
                                required
                            >

                            <div id="ws-file-preview" class="mt-3"></div>
                        </div>

                        @error('files')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if ($errors->has('files.*'))
                            <div class="invalid-feedback d-block">
                                {{ $errors->first('files.*') }}
                            </div>
                        @endif
                    </div>

                    <div class="form-check mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="is_public"
                            id="is_public"
                            value="1"
                            {{ old('is_public') ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="is_public">
                            Make this dataset public
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const dropzone = document.getElementById('ws-dropzone');
    const fileInput = document.getElementById('files');
    const selectBtn = document.getElementById('ws-select-files');
    const preview = document.getElementById('ws-file-preview');

    if (!dropzone || !fileInput || !preview) return;

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function renderPreview(files) {
        if (!files || files.length === 0) {
            preview.innerHTML = '<div class="text-muted small">No files selected</div>';
            return;
        }

        const list = document.createElement('ul');
        list.className = 'mb-0';

        Array.from(files).forEach((file, idx) => {
            const li = document.createElement('li');
            li.className = 'd-flex align-items-center justify-content-between';

            const meta = document.createElement('div');
            meta.className = 'file-meta';
            meta.innerHTML = `<div class=\"file-chip\">${file.name.split('.').pop().toUpperCase()}</div><div><div class=\"file-name\">${file.name}</div><div class=\"file-size\">${formatBytes(file.size)}</div></div>`;

            const actions = document.createElement('div');
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', function () { removeFileAtIndex(idx); });

            actions.appendChild(removeBtn);

            li.appendChild(meta);
            li.appendChild(actions);
            list.appendChild(li);
        });

        preview.innerHTML = '';
        preview.appendChild(list);
    }

    function removeFileAtIndex(index) {
        const dt = new DataTransfer();
        const files = Array.from(fileInput.files || []);
        files.splice(index, 1);
        files.forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
        renderPreview(fileInput.files);
    }

    fileInput.addEventListener('change', () => renderPreview(fileInput.files));
    selectBtn.addEventListener('click', () => fileInput.click());

    ['dragenter','dragover'].forEach(evt => dropzone.addEventListener(evt, (e)=>{ e.preventDefault(); e.stopPropagation(); dropzone.classList.add('ws-dropzone--dragover'); }));
    ['dragleave','drop'].forEach(evt => dropzone.addEventListener(evt, (e)=>{ e.preventDefault(); e.stopPropagation(); dropzone.classList.remove('ws-dropzone--dragover'); }));

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        if (!dt) return;
        const files = dt.files || [];
        if (!files.length) return;

        const existing = Array.from(fileInput.files || []);
        const merged = existing.concat(Array.from(files));

        const newDT = new DataTransfer();
        merged.forEach(f => newDT.items.add(f));
        fileInput.files = newDT.files;
        renderPreview(fileInput.files);
    });

    // initial render
    if (fileInput.files && fileInput.files.length > 0) {
        renderPreview(fileInput.files);
    } else {
        preview.innerHTML = '<div class="text-muted small">No files selected</div>';
    }
})();
</script>
@endpush
