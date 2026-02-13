@if ($datasets->isEmpty())
    <div class="alert alert-info mb-0">You don't have any datasets yet. Upload one first.</div>
@else
    <div class="border rounded-3" style="max-height: 45vh; overflow: auto;">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="position-sticky top-0 bg-body" style="z-index: 2;">
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
    </div>

    <div class="mt-3 d-flex justify-content-center ws-modal-datasets-pagination">
        {{ $datasets->onEachSide(1)->links() }}
    </div>
@endif

