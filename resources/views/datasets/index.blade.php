@extends('layouts.app')

@section('title', 'Moje datasety')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h1 class="h3 mb-0">Moje datasety</h1>

        <a href="{{ route('datasets.upload') }}" class="btn btn-primary">
            Nahrať nový dataset
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($datasets->isEmpty())
        <div class="alert alert-info mb-0">
            Zatiaľ nemáš nahrané žiadne datasety.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th scope="col">Názov</th>
                        <th scope="col">Popis</th>
                        <th scope="col" class="text-nowrap">Dátum nahratia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datasets as $dataset)
                        <tr>
                            <td class="fw-semibold">{{ $dataset->name }}</td>
                            <td class="text-muted">{{ $dataset->description ?? '—' }}</td>
                            <td class="text-nowrap">{{ $dataset->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection

