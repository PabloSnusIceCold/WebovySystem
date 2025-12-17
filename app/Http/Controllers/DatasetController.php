<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatasetController extends Controller
{
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

        Dataset::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
        ]);

        return back()->with('success', 'Dataset bol úspešne nahraný.');
    }
}
