<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatasetController extends Controller
{
    /**
     * List datasets for the currently authenticated user.
     */
    public function index()
    {
        $datasets = Dataset::where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('datasets.index', compact('datasets'));
    }

    /**
     * Show the dataset upload form.
     */
    public function uploadForm()
    {
        return view('datasets.upload');
    }

    /**
     * Handle the dataset upload.
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'description' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $path = $file->store('datasets');

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $fileType = match ($extension) {
            'csv' => 'CSV',
            'txt' => 'TXT',
            'json' => 'JSON',
            default => strtoupper($extension ?: 'N/A'),
        };

        Dataset::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_type' => $fileType,
            'file_size' => $file->getSize() ?: null,
        ]);

        return back()->with('success', 'Dataset bol úspešne nahraný.');
    }

    /**
     * Show a single dataset (must belong to authenticated user).
     */
    public function show(int $id)
    {
        $dataset = Dataset::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('datasets.show', compact('dataset'));
    }

    /**
     * Show edit form for a dataset (must belong to authenticated user).
     */
    public function edit(int $id)
    {
        $dataset = Dataset::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('datasets.edit', compact('dataset'));
    }

    /**
     * Update dataset (must belong to authenticated user).
     */
    public function update(Request $request, int $id)
    {
        $dataset = Dataset::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $dataset->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('datasets.index')->with('success', 'Dataset bol upravený.');
    }

    /**
     * Soft delete dataset (must belong to authenticated user).
     */
    public function destroy(int $id)
    {
        $dataset = Dataset::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $dataset->delete();

        return redirect()->route('datasets.index')->with('success', 'Dataset bol odstránený.');
    }

    /**
     * Placeholder share action.
     */
    public function share(int $id)
    {
        $dataset = Dataset::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // TODO: implement real sharing
        return back()->with('info', 'Funkcia zatiaľ nie je implementovaná.');
    }
}
