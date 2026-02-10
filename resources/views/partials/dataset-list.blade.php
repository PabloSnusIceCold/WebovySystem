@php
    /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $datasets */
    $items = $datasets ?? collect();
    $currentUser = auth()->user();
@endphp

@if ($items->isEmpty())
    <div class="card shadow-sm rounded-4 border-0 market-card">
        <div class="card-body text-center text-muted py-5">
            <div class="fw-semibold mb-1">Nothing here yet</div>
            <div>No public datasets available.</div>
        </div>
    </div>
@else
    <div class="card border-0 rounded-4 shadow-sm market-card">
        <div class="list-group list-group-flush">
            @foreach ($items as $dataset)
                @php
                    $files = $dataset->files ?? collect();
                    $fileCount = $files->count();

                    $visibilityText = $dataset->is_public ? 'Public' : 'Private';
                    $visibilityClass = $dataset->is_public
                        ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis'
                        : 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis';

                    $downloadCount = (int) ($dataset->download_count ?? 0);
                    $likesCount = (int) ($dataset->likes_count ?? 0);
                    $likedByMe = (bool) ($dataset->liked_by_me ?? false);

                    $canDelete = $currentUser && ((int) $currentUser->id === (int) $dataset->user_id || $currentUser->role === 'admin');
                @endphp

                <div class="list-group-item ws-dataset-line">
                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                        <div class="flex-grow-1" style="min-width: 260px;">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="text-muted small">#{{ $dataset->id }}</span>
                                <a class="fw-semibold text-body" href="{{ route('datasets.show', $dataset->id) }}">
                                    {{ $dataset->name }}
                                </a>
                                <span class="badge badge-soft {{ $visibilityClass }}">{{ $visibilityText }}</span>
                            </div>

                            {{-- Popis hneď pod názvom (1 riadok, skrátené) --}}
                            <div class="text-muted small mt-1 ws-dataset-line-desc">
                                {{ $dataset->description ?? '—' }}
                            </div>

                            <div class="d-flex flex-wrap gap-2 mt-2 ws-dataset-line-meta">
                                <span class="ws-pill ws-pill--sm">{{ $dataset->category->name ?? '—' }}</span>
                                <span class="ws-pill ws-pill--sm">Files: {{ $fileCount }}</span>
                                <span class="ws-pill ws-pill--sm">{{ $dataset->total_size_human }}</span>
                                <span class="ws-pill ws-pill--sm">⬇ <span id="downloadCount-{{ $dataset->id }}">{{ $downloadCount }}</span></span>
                                <span class="ws-pill ws-pill--sm">👍 <span id="likesCount-{{ $dataset->id }}">{{ $likesCount }}</span></span>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end ws-dataset-line-actions">
                            <a href="{{ route('datasets.show', $dataset->id) }}" class="btn btn-sm btn-outline-primary rounded-3">Details</a>

                            <a href="{{ route('datasets.download', $dataset->id) }}"
                               class="btn btn-sm btn-primary rounded-3 js-zip-download"
                               data-dataset-id="{{ $dataset->id }}">ZIP</a>

                            @auth
                                <button type="button"
                                        class="btn btn-sm rounded-3 js-like-toggle {{ $likedByMe ? 'btn-primary' : 'btn-outline-primary' }}"
                                        data-dataset-id="{{ $dataset->id }}">
                                    {{ $likedByMe ? '👍' : '👍' }}
                                </button>
                            @endauth

                            @if ($canDelete)
                                <form action="{{ route('datasets.destroy', $dataset->id) }}" method="POST" class="m-0"
                                      onsubmit="return confirm('Are you sure you want to delete this dataset?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" title="Delete">Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @include('partials.dataset-pagination', ['datasets' => $items])
@endif
