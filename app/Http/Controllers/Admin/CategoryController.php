<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('datasets')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect('/admin?tab=categories')->with('success', 'Kategória bola vytvorená.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect('/admin?tab=categories')->with('success', 'Kategória bola upravená.');
    }

    public function destroy(Category $category)
    {
        $hasDatasets = $category->datasets()->exists();
        if ($hasDatasets) {
            return redirect('/admin?tab=categories')->with('error', 'Kategóriu nie je možné odstrániť, pretože obsahuje datasety.');
        }

        $category->delete();

        return redirect('/admin?tab=categories')->with('success', 'Kategória bola odstránená.');
    }

    public function show(Category $category)
    {
        // datasets_count on category + list of datasets with owner and file counts
        $category->loadCount('datasets');
        $category->load([
            'datasets' => function ($q) {
                $q->with('user')
                    ->withCount('files')
                    ->latest();
            },
        ]);

        return view('admin.categories.show', compact('category'));
    }
}
