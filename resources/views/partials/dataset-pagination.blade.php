@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Pagination\Paginator|mixed $datasets */
@endphp

@if (isset($datasets) && $datasets instanceof \Illuminate\Pagination\AbstractPaginator)
    <div class="mt-4 d-flex justify-content-center">
        {{ $datasets->links() }}
    </div>
@endif

