@extends('layouts.app')

@section('title', $repository->name)

@section('content')
    @php
        $user = auth()->user();
        $isOwner = $user && ((int) $repository->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

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
            <a href="{{ route('repositories.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Späť</a>
        </div>
    </div>

    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Vytvorené</dt>
            <dd class="col-sm-9">{{ $repository->created_at?->format('d.m.Y H:i') }}</dd>

            <dt class="col-sm-3">Počet datasetov</dt>
            <dd class="col-sm-9">{{ (int) ($repository->datasets_count ?? 0) }}</dd>
        </dl>
    </div>

    <section class="bg-white rounded-3 shadow-sm p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Datasety v repozitári</h2>
            <span class="text-muted small">{{ $datasets->count() }} datasetov</span>
        </div>

        @if ($datasets->isEmpty())
            <div class="alert alert-info mb-0">Repozitár je zatiaľ prázdny.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Názov</th>
                            <th>Vlastník</th>
                            <th>Viditeľnosť</th>
                            <th class="text-end">Súborov</th>
                            <th>Dátum</th>
                            <th class="text-end">Akcie</th>
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
                                        <span class="badge text-bg-success">Verejný</span>
                                    @else
                                        <span class="badge text-bg-secondary">Súkromný</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ (int) ($ds->files_count ?? 0) }}</td>
                                <td class="text-muted text-nowrap">{{ $ds->created_at?->format('d.m.Y') }}</td>
                                <td class="text-end">
                                    @if ($canOpenDataset)
                                        <a href="{{ route('datasets.show', $ds->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill">Detail datasetu</a>
                                    @else
                                        <span class="text-muted small">Súkromný</span>
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

