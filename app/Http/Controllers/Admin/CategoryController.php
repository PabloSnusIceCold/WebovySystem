<?php

/**
 * Poznámka: Tento controller bol vytvorený/upravený s pomocou AI nástrojov (GitHub Copilot).
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

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
        // Hard delete kategórie + všetkých jej datasetov.
        // Pozn.: DB cascade nezmaže fyzické súbory, preto to riešime tu.
        $category->load(['datasets.files']);

        foreach ($category->datasets as $dataset) {
            foreach ($dataset->files as $file) {
                if (!empty($file->file_path)) {
                    Storage::delete($file->file_path);
                }
            }

            if (!empty($dataset->file_path)) {
                Storage::delete($dataset->file_path);
            }

            $dataset->files()->delete();
            $dataset->delete();
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
