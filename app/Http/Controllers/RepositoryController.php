<?php

/**
 * Poznámka: Tento controller bol vytvorený/upravený s pomocou AI nástrojov (GitHub Copilot).
 */

namespace App\Http\Controllers;

use App\Models\Dataset;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RepositoryController extends Controller
{
    /**
     * Zoznam repozitárov prihláseného používateľa.
     */
    public function index()
    {
        $repositories = Repository::query()
            ->where('user_id', Auth::id())
            ->withCount('datasets')
            ->latest()
            ->get();

        $datasets = Dataset::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'is_public', 'created_at']);

        return view('repositories.index', compact('repositories', 'datasets'));
    }

    /**
     * Vytvorenie nového repozitára + priradenie vybraných datasetov.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'dataset_ids' => ['nullable', 'array'],
            'dataset_ids.*' => ['integer', 'exists:datasets,id'],
        ]);

        $repository = Repository::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $datasetIds = $validated['dataset_ids'] ?? [];
        if (!empty($datasetIds)) {
            Dataset::query()
                ->where('user_id', Auth::id())
                ->whereIn('id', $datasetIds)
                ->update(['repository_id' => $repository->id]);
        }

        return redirect()->route('repositories.index')->with('success', 'Repository has been created.');
    }

    /**
     * Detail repozitára (len owner alebo admin).
     */
    public function show(Repository $repository)
    {
        $user = Auth::user();
        $isOwner = $user && ((int) $repository->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        if (!$isOwner && !$isAdmin) {
            abort(403);
        }

        $repository->loadCount('datasets');
        $repository->load([
            'datasets' => function ($q) {
                $q->withCount('files')->with(['user', 'category'])->latest();
            },
        ]);

        return view('repositories.show', compact('repository'));
    }

    /**
     * Generate and return the share URL for a repository (owner/admin only).
     */
    public function share(Repository $repository, Request $request)
    {
        $user = Auth::user();
        $isOwner = $user && ((int) $repository->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        if (!$isOwner && !$isAdmin) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                ], 403);
            }

            abort(403);
        }

        $token = $repository->ensureShareToken();
        $shareUrl = url('/repositories/share/' . $token);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'share_url' => $shareUrl,
                'token' => $token,
            ]);
        }

        return back()->with('share_url', $shareUrl);
    }

    /**
     * Public view of a repository via share token.
     * IMPORTANT: Dataset detail is allowed ONLY for public datasets.
     */
    public function shareShow(string $token)
    {
        $repository = Repository::query()
            ->where('share_token', $token)
            ->withCount('datasets')
            ->with([
                'datasets' => function ($q) {
                    $q->withCount('files')
                        ->with(['user', 'category'])
                        ->latest();
                },
            ])
            ->firstOrFail();

        return view('repositories.share', compact('repository'));
    }
}
