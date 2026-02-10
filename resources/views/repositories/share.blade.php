@extends('layouts.app')

@section('title', 'Shared repository')

@section('content')
    @php
        $datasets = $repository->datasets ?? collect();
    @endphp

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $repository->name }}</h1>
            <div class="text-muted small">Shared repository</div>
            @if ($repository->description)
                <div class="text-muted mt-2">{{ $repository->description }}</div>
            @endif
        </div>

        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Back</a>
    </div>

    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Created</dt>
            <dd class="col-sm-9">{{ $repository->created_at?->format('d.m.Y H:i') }}</dd>

            <dt class="col-sm-3">Datasets</dt>
            <dd class="col-sm-9">{{ (int) ($repository->datasets_count ?? 0) }}</dd>
        </dl>
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
                                    @if ($ds->is_public)
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
@endsection

