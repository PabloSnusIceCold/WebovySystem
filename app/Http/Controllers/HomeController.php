<?php

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

        $query = Dataset::with(['user', 'category'])->whereNull('deleted_at');

        // If the DB isn't migrated yet, this column may not exist.
        // In that case we avoid applying visibility filtering to prevent SQL errors.
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

        if ($request->ajax() || $request->expectsJson()) {
            return view('partials.dataset-cards', compact('datasets'));
        }

        return view('home', compact('datasets', 'categories'));
    }
}
