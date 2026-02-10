@php
    /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $datasets */
    $publicDatasets = $datasets ?? collect();
    $currentUser = auth()->user();
@endphp

@if ($publicDatasets->isEmpty())
    <div class="card shadow-sm rounded-4 border-0 market-card">
        <div class="card-body text-center text-muted py-5">
            <div class="fw-semibold mb-1">Nothing here yet</div>
            <div>No public datasets available.</div>
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

                $visibilityText = $dataset->is_public ? 'Public' : 'Private';
                $visibilityClass = $dataset->is_public
                    ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis'
                    : 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis';

                $categoryName = $dataset->category->name ?? '—';
                $downloadCount = (int) ($dataset->download_count ?? 0);
                $likesCount = (int) ($dataset->likes_count ?? 0);

                $likedByMe = (bool) ($dataset->liked_by_me ?? false);
            @endphp

            <div class="col-12 col-md-6 col-lg-6">
                <div class="card h-100 border-0 market-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <h2 class="h5 mb-0 dataset-title-clamp">{{ $dataset->name }}</h2>

                            @if (auth()->check() && (auth()->user()->role === 'admin' || (int) auth()->id() === (int) $dataset->user_id))
                                <form
                                    action="{{ route('datasets.destroy', $dataset->id) }}"
                                    method="POST"
                                    class="m-0"
                                    onsubmit="return confirm('Are you sure you want to delete this dataset?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger rounded-3 ws-card-delete"
                                        title="Delete dataset"
                                        aria-label="Delete dataset"
                                    >
                                        ✕
                                    </button>
                                </form>
                            @endif
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
                                    No files
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
                                    <div class="fw-semibold text-body">Files</div>
                                    <div>{{ $fileCount }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-semibold text-body">Size</div>
                                    <div>{{ $dataset->total_size_human }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-semibold text-body">Date</div>
                                    <div>{{ $dataset->created_at?->format('d.m.Y H:i') }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-semibold text-body">User</div>
                                    <div>{{ $dataset->user?->username ?? '—' }}</div>
                                </div>

                                <div class="col-6">
                                    <div class="fw-semibold text-body">Downloads</div>
                                    <div id="downloadCount-{{ $dataset->id }}">{{ $downloadCount }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-semibold text-body">Likes</div>
                                    <div id="likesCount-{{ $dataset->id }}">{{ $likesCount }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto">
                            <div class="ws-card-actions">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                    <div class="d-flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('datasets.show', $dataset->id) }}"
                                            class="btn btn-sm btn-outline-primary rounded-3"
                                        >
                                            Details
                                        </a>
                                        <a
                                            href="{{ route('datasets.download', $dataset->id) }}"
                                            class="btn btn-sm btn-primary rounded-3 js-zip-download"
                                            data-dataset-id="{{ $dataset->id }}"
                                        >
                                            Download ZIP
                                        </a>
                                    </div>

                                    @auth
                                        <button
                                            type="button"
                                            class="btn btn-sm rounded-3 js-like-toggle {{ $likedByMe ? 'btn-primary' : 'btn-outline-primary' }}"
                                            data-dataset-id="{{ $dataset->id }}"
                                        >
                                            {{ $likedByMe ? '👍 Liked' : '👍 Like' }}
                                        </button>
                                    @endauth
                                </div>

                                {{-- Delete is in header --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
