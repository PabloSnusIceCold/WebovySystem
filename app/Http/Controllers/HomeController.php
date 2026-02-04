<?php

/**
 * Poznámka: Tento controller bol vytvorený/upravený s pomocou AI nástrojov (GitHub Copilot).
 */

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Homepage: list datasets with respect to public/private rules.
     */
    public function index(Request $request)
    {
        $categories = Category::orderBy('name')->get();

        $query = Dataset::with(['user', 'category', 'files']);

        // Add "liked_by_me" flag when authenticated and the pivot table exists.
        if (Auth::check()) {
            $userId = (int) Auth::id();
            if (Schema::hasTable('dataset_likes')) {
                $query->withExists([
                    'likedByUsers as liked_by_me' => function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    },
                ]);
            }
        }

        // Visibility rules (avoid SQL errors if column not present).
        if (Schema::hasColumn('datasets', 'is_public')) {
            if (!Auth::check()) {
                $query->where('is_public', true);
            } else {
                /** @var User $user */
                $user = Auth::user();

                if ($user->role !== 'admin') {
                    $query->where(function ($q) use ($user) {
                        $q->where('is_public', true)
                            ->orWhere('user_id', $user->id);
                    });
                }
            }
        }

        $categoryId = trim((string) $request->query('category_id', ''));
        if ($categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $datasets = $query->latest()->get();

        // --- Right sidebar: Top lists ---
        $topDownloads = collect();
        $topLikes = collect();

        // Base query for top lists: same visibility rules as main query.
        $topBase = Dataset::query()->with(['category']);

        if (Schema::hasColumn('datasets', 'is_public')) {
            if (!Auth::check()) {
                $topBase->where('is_public', true);
            } else {
                /** @var User $user */
                $user = Auth::user();
                if ($user->role !== 'admin') {
                    $topBase->where(function ($q) use ($user) {
                        $q->where('is_public', true)
                            ->orWhere('user_id', $user->id);
                    });
                }
            }
        }

        if (Schema::hasColumn('datasets', 'download_count')) {
            $topDownloads = (clone $topBase)
                ->orderByDesc('download_count')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'name', 'category_id', 'download_count', 'is_public']);
        }

        if (Schema::hasColumn('datasets', 'likes_count')) {
            $topLikes = (clone $topBase)
                ->orderByDesc('likes_count')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'name', 'category_id', 'likes_count', 'is_public']);
        }

        if ($request->ajax() || $request->expectsJson()) {
            return view('partials.dataset-cards', compact('datasets'));
        }

        return view('home', compact('datasets', 'categories', 'topDownloads', 'topLikes'));
    }
}
