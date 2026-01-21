<?php

/**
 * Poznámka: Tento controller bol vytvorený/upravený s pomocou AI nástrojov (GitHub Copilot).
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DatasetController extends Controller
{
    /**
     * Admin listing of all datasets (public + private).
     */
    public function index()
    {
        $datasets = Dataset::with(['user', 'category', 'files'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.datasets.index', compact('datasets'));
    }

    /**
     * Show admin edit form for a dataset.
     */
    public function edit(Dataset $dataset)
    {
        $dataset->loadMissing(['user', 'category', 'files']);
        $categories = Category::orderBy('name')->get();

        return view('admin.datasets.edit', compact('dataset', 'categories'));
    }

    /**
     * Update dataset as admin.
     */
    public function update(Request $request, Dataset $dataset)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $dataset->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => (int) $validated['category_id'],
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect('/admin?tab=datasets')->with('success', 'Dataset bol upravený.');
    }

    /**
     * Delete dataset as admin.
     * - delete stored files
     * - delete File rows
     * - hard-delete dataset (new system)
     */
    public function destroy(Dataset $dataset)
    {
        $dataset->loadMissing('files');

        foreach ($dataset->files as $file) {
            if (!empty($file->file_path)) {
                Storage::delete($file->file_path);
            }
        }

        // Also try to remove legacy single-file path if present
        if (!empty($dataset->file_path)) {
            Storage::delete($dataset->file_path);
        }

        // Delete DB records for files first (if FK cascade exists, this is still safe)
        $dataset->files()->delete();

        // Hard delete dataset
        $dataset->delete();

        return redirect('/admin?tab=datasets')->with('success', 'Dataset bol zmazaný.');
    }
}
