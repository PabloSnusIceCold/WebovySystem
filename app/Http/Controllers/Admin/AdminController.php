<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Unified admin dashboard page with tabs (users|datasets).
     */
    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'users');
        if (!in_array($tab, ['users', 'datasets'], true)) {
            $tab = 'users';
        }

        // Keep existing logic: users list (no pagination yet in current implementation)
        $users = User::all();

        // Dataset list for admin with eager-loading + pagination
        $datasets = Dataset::with(['user', 'category', 'files'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.index', compact('tab', 'users', 'datasets'));
    }
}

