@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Datasety</h2>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Názov</th>
                        <th>Kategória</th>
                        <th>Vlastník</th>
                        <th>Viditeľnosť</th>
                        <th class="text-end">Súborov</th>
                        <th>Dátum</th>
                        <th class="text-end">Akcie</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($datasets as $dataset)
                        <tr>
                            <td class="text-muted">{{ $dataset->id }}</td>
                            <td class="fw-semibold">{{ $dataset->name }}</td>
                            <td>{{ $dataset->category?->name ?? '—' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $dataset->user?->username ?? '—' }}</div>
                                <div class="text-muted small">{{ $dataset->user?->email ?? '' }}</div>
                            </td>
                            <td>
                                @if ($dataset->is_public)
                                    <span class="badge text-bg-success">Verejný</span>
                                @else
                                    <span class="badge text-bg-secondary">Súkromný</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $dataset->files?->count() ?? 0 }}</td>
                            <td class="text-muted">{{ $dataset->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('datasets.show', $dataset->id) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                    <a href="{{ route('admin.datasets.edit', $dataset) }}" class="btn btn-sm btn-outline-primary">Edit</a>

                                    <form action="{{ route('admin.datasets.destroy', $dataset) }}" method="POST" class="m-0"
                                          onsubmit="return confirm('Naozaj chceš odstrániť tento dataset?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Žiadne datasety.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($datasets, 'links'))
            <div class="mt-3">
                {{ $datasets->links() }}
            </div>
        @endif
    </div>
</div>

