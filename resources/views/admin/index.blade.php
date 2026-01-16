@extends('layouts.app')

@section('title', 'Administrácia')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h1 class="h3 mb-0">Administrácia</h1>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a
                class="nav-link {{ ($tab ?? 'users') === 'users' ? 'active' : '' }}"
                href="{{ url('/admin?tab=users') }}"
            >
                Používatelia
            </a>
        </li>
        <li class="nav-item">
            <a
                class="nav-link {{ ($tab ?? 'users') === 'datasets' ? 'active' : '' }}"
                href="{{ url('/admin?tab=datasets') }}"
            >
                Datasety
            </a>
        </li>
        <li class="nav-item">
            <a
                class="nav-link {{ ($tab ?? 'users') === 'categories' ? 'active' : '' }}"
                href="{{ url('/admin?tab=categories') }}"
            >
                Kategórie
            </a>
        </li>
    </ul>

    @if (($tab ?? 'users') === 'users')
        @include('admin.partials.users-table', ['users' => $users])
    @elseif (($tab ?? 'users') === 'datasets')
        @include('admin.partials.datasets-table', ['datasets' => $datasets])
    @else
        @include('admin.partials.categories-table', ['categories' => $categories])
    @endif
@endsection
