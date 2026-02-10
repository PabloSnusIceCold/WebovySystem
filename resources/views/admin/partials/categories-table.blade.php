@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Categories</h2>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Add category</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col" class="text-end">Datasets</th>
                    <th scope="col">Created</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>{{ $category->id }}</td>
                        <td class="fw-semibold">{{ $category->name }}</td>
                        <td class="text-end">{{ $category->datasets_count ?? 0 }}</td>
                        <td class="text-muted">{{ $category->created_at?->format('d.m.Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.categories.show', $category->id) }}" class="btn btn-sm btn-outline-secondary me-1">Details</a>
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>

                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">No categories.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if (method_exists($categories, 'links'))
    <div class="mt-3">
        {{ $categories->links() }}
    </div>
@endif
