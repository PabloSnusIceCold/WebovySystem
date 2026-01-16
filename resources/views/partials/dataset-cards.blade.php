@php
    /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $datasets */
    $publicDatasets = $datasets ?? collect();
    $currentUser = auth()->user();
@endphp

@if ($publicDatasets->isEmpty())
    <div class="card shadow-sm rounded-4 border-0 market-card">
        <div class="card-body text-center text-muted py-5">
            <div class="fw-semibold mb-1">Zatiaľ tu nič nie je</div>
            <div>Nie sú dostupné žiadne verejné datasety.</div>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach ($publicDatasets as $dataset)
            @php
                $canDelete = $currentUser && ((int) $currentUser->id === (int) $dataset->user_id || $currentUser->role === 'admin');

                $files = $dataset->files ?? collect();
                $fileCount = $files->count();
                $fileTypes = $files->pluck('file_type')->filter()->map(fn ($t) => strtoupper((string) $t))->unique()->values();

                $visibilityText = $dataset->is_public ? 'Verejný' : 'Súkromný';
                $visibilityClass = $dataset->is_public
                    ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis'
                    : 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis';

                $categoryName = $dataset->category->name ?? '—';
                $downloadCount = (int) ($dataset->download_count ?? 0);
            @endphp

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 market-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <h2 class="h5 mb-0 dataset-title-clamp">{{ $dataset->name }}</h2>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge badge-soft text-bg-secondary-subtle border border-secondary-subtle text-secondary-emphasis">
                                {{ $categoryName }}
                            </span>

                            <span class="badge badge-soft {{ $visibilityClass }}">
                                {{ $visibilityText }}
                            </span>

                            @if ($fileCount === 0)
                                <span class="badge badge-soft text-bg-warning-subtle border border-warning-subtle text-warning-emphasis">
                                    Bez súborov
                                </span>
                            @else
                                @foreach ($fileTypes as $type)
                                    <span class="badge badge-soft text-bg-primary-subtle border border-primary-subtle text-primary-emphasis">
                                        {{ $type }}
                                    </span>
                                @endforeach
                            @endif
                        </div>

                        <p class="text-muted mb-3 dataset-desc-clamp">
                            {{ $dataset->description ?? '—' }}
                        </p>

                        <div class="mb-3">
                            <div class="row small text-muted g-2">
                                <div class="col-6">
                                    <div class="fw-semibold text-body">Súborov</div>
                                    <div>{{ $fileCount }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-semibold text-body">Veľkosť</div>
                                    <div>{{ $dataset->total_size_human }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-semibold text-body">Dátum</div>
                                    <div>{{ $dataset->created_at?->format('d.m.Y H:i') }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-semibold text-body">Používateľ</div>
                                    <div>{{ $dataset->user?->username ?? '—' }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="fw-semibold text-body">Stiahnutí</div>
                                    <div id="downloadCount-{{ $dataset->id }}">{{ $downloadCount }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto">
                            <div class="ws-card-actions d-flex flex-wrap align-items-center gap-2">
                                <div class="d-flex flex-wrap gap-2">
                                    <a
                                        href="{{ route('datasets.show', $dataset->id) }}"
                                        class="btn btn-sm btn-outline-primary rounded-3"
                                    >
                                        Detail
                                    </a>
                                    <a
                                        href="{{ route('datasets.download', $dataset->id) }}"
                                        class="btn btn-sm btn-primary rounded-3 js-zip-download"
                                        data-dataset-id="{{ $dataset->id }}"
                                    >
                                        Stiahnuť ZIP
                                    </a>
                                </div>

                                @if (auth()->check() && (auth()->user()->role === 'admin' || (int) auth()->id() === (int) $dataset->user_id))
                                    <div class="ms-auto">
                                        <form
                                            action="{{ route('datasets.destroy', $dataset->id) }}"
                                            method="POST"
                                            class="m-0"
                                            onsubmit="return confirm('Naozaj chceš odstrániť tento dataset?');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3">Zmazať</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
