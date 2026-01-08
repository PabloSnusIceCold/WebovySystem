@php
    /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $datasets */
    $publicDatasets = $datasets ?? collect();
@endphp

@if ($publicDatasets->isEmpty())
    <div class="card shadow-sm rounded-3">
        <div class="card-body text-center text-muted">
            Zatiaľ nie sú dostupné žiadne verejné datasety.
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach ($publicDatasets as $dataset)
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm rounded-3">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5 mb-2">{{ $dataset->name }}</h2>

                        <p class="text-muted mb-3 dataset-desc" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            {{ $dataset->description ?? '—' }}
                        </p>

                        <div class="mb-3 text-muted small">
                            <div><span class="fw-semibold">Kategória:</span> {{ $dataset->category->name ?? '—' }}</div>
                            <div><span class="fw-semibold">Typ:</span> {{ $dataset->file_type ?? '—' }}</div>
                            <div><span class="fw-semibold">Veľkosť:</span> {{ $dataset->total_size_human }}</div>
                            <div><span class="fw-semibold">Dátum:</span> {{ $dataset->created_at?->format('d.m.Y H:i') }}</div>
                            <div><span class="fw-semibold">Používateľ:</span> {{ $dataset->user?->username ?? '—' }}</div>
                        </div>

                        <div class="mt-auto d-flex gap-2">
                            <a href="{{ route('datasets.show', $dataset->id) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                            <a href="{{ route('datasets.download', $dataset->id) }}" class="btn btn-primary btn-sm">Stiahnuť ZIP</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
