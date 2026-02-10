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
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 40px;"></th>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Visibility</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($datasets as $ds)
                                                    <tr>
                                                        <td>
                                                            <input class="form-check-input" type="checkbox" name="dataset_ids[]" value="{{ $ds->id }}" id="ds-{{ $ds->id }}">
                                                        </td>
                                                        <td class="text-muted">{{ $ds->id }}</td>
                                                        <td>
                                                            <label for="ds-{{ $ds->id }}" class="mb-0">{{ $ds->name }}</label>
                                                        </td>
                                                        <td>
                                                            @if ($ds->is_public)
                                                                <span class="badge text-bg-success">Public</span>
                                                            @else
                                                                <span class="badge text-bg-secondary">Private</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-muted">{{ $ds->created_at?->format('d.m.Y') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-3 d-flex justify-content-center">
                                        {{ $datasets->links() }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
