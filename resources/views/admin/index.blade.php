@extends('layouts.app')

@section('title', 'Administration')

@section('content')
    @php
        $activeTab = $tab ?? 'users';
    @endphp

    <div class="ws-admin-wrap">
        {{-- Dashboard header / hero --}}
        <section class="ws-admin-hero mb-4">
            <div class="ws-admin-hero-inner">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="ws-admin-hero-icon" aria-hidden="true">⚙️</span>
                            <h1 class="ws-admin-title mb-0">Administration</h1>
                        </div>
                        <p class="ws-admin-subtitle ws-muted mb-0 mt-2">
                            A modern admin dashboard for managing users, datasets, and categories.
                        </p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ url('/') }}" class="btn btn-sm btn-outline-secondary ws-btn">Back to site</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Stats cards --}}
        <div class="ws-admin-stats row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card ws-admin-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="ws-admin-stat-icon" aria-hidden="true">👤</div>
                        <div>
                            <div class="ws-muted small">Users</div>
                            <div class="fs-4 fw-bold">{{ (int) ($stats['users'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card ws-admin-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="ws-admin-stat-icon" aria-hidden="true">📦</div>
                        <div>
                            <div class="ws-muted small">Datasets</div>
                            <div class="fs-4 fw-bold">{{ (int) ($stats['datasets'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card ws-admin-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="ws-admin-stat-icon" aria-hidden="true">🏷️</div>
                        <div>
                            <div class="ws-muted small">Categories</div>
                            <div class="fs-4 fw-bold">{{ (int) ($stats['categories'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs (nav-pills) --}}
        <ul class="nav nav-pills ws-admin-pills mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'users' ? 'active' : '' }}" href="{{ url('/admin?tab=users') }}">
                    Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'datasets' ? 'active' : '' }}" href="{{ url('/admin?tab=datasets') }}">
                    Datasets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'categories' ? 'active' : '' }}" href="{{ url('/admin?tab=categories') }}">
                    Categories
                </a>
            </li>
        </ul>

        {{-- Tab content in one big card (partials untouched) --}}
        <div class="card ws-admin-card ws-admin-content">
            <div class="card-body p-3 p-md-4">
                @switch($activeTab)
                    @case('users')
                        @include('admin.partials.users-table', ['users' => $users])
                        @break

                    @case('datasets')
                        @include('admin.partials.datasets-table', ['datasets' => $datasets])
                        @break

                    @default
                        @include('admin.partials.categories-table', ['categories' => $categories])
                @endswitch
            </div>
        </div>
    </div>
@endsection
