<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Unified admin dashboard page with tabs (users|datasets|categories).
     */
    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'users');
        if (!in_array($tab, ['users', 'datasets', 'categories'], true)) {
            $tab = 'users';
        }

        // Users list for admin tab
        $users = User::query()
            ->withCount('datasets')
            ->orderBy('id')
            ->get();

        // Dataset list for admin with eager-loading + pagination
        $datasets = Dataset::with(['user', 'category', 'files'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Category list for admin tab
        $categories = Category::withCount('datasets')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.index', compact('tab', 'users', 'datasets', 'categories'));
    }
}
