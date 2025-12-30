<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Public homepage showing public datasets.
     */
    public function index(Request $request)
    {
        $query = Dataset::query()->with('user')->latest();

        // If the DB wasn't migrated yet, this column may not exist.
        if (Schema::hasColumn('datasets', 'is_public')) {
            $query->where('is_public', true);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $datasets = $query->get();

        return view('home', compact('datasets'));
    }
}
