<?php

/**
 * Poznámka: Tento controller bol vytvorený/upravený s pomocou AI nástrojov (GitHub Copilot).
 */

namespace App\Http\Controllers;

use App\Models\Dataset;
use App\Models\File;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DatasetController extends Controller
{
    /**
     * List datasets for the currently authenticated user.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = Dataset::query()
            ->with(['category'])
            ->withCount('files')
            ->where('user_id', Auth::id());

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $datasets = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('datasets.index', compact('datasets', 'search'));
    }

    /**
     * Show the dataset upload form.
     */
    public function uploadForm()
    {
        $categories = Category::orderBy('name')->get();

        return view('datasets.upload', compact('categories'));
    }

    /**
     * Handle the dataset upload.
     */
    public function upload(Request $request)
    {
        $allowedExtensions = ['csv', 'txt', 'xlsx', 'json', 'xml', 'arff', 'zip'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'required',
                'file',
                function (string $attribute, $value, $fail) use ($allowedExtensions) {
                    if (!$value instanceof UploadedFile) {
                        $fail('Invalid file.');
                        return;
                    }

                    $ext = strtolower((string) $value->getClientOriginalExtension());
                    if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
                        $fail('Unsupported file type. Allowed: ' . strtoupper(implode(', ', $allowedExtensions)) . '.');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'is_public' => ['nullable'],
        ]);

        $files = $request->file('files', []);
        if (!is_array($files) || count($files) < 1) {
            return back()->withErrors(['files' => 'You must upload at least 1 file.'])->withInput();
        }

        // We'll create the dataset using the FIRST uploaded file for backward compatibility
        // (datasets.file_* columns). Then we store each file exactly once and create File rows.
        $firstFile = $files[0];

        $firstExtension = strtolower((string) $firstFile->getClientOriginalExtension());
        $firstFileType = match ($firstExtension) {
            'csv' => 'CSV',
            'txt' => 'TXT',
            'xlsx' => 'XLSX',
            'json' => 'JSON',
            'xml' => 'XML',
            'arff' => 'ARFF',
            'zip' => 'ZIP',
            default => strtoupper($firstExtension ?: 'N/A'),
        };

        // Store the first file once.
        $firstPath = $firstFile->store('datasets');

        $dataset = Dataset::create([
            'user_id' => Auth::id(),
            'category_id' => (int) $validated['category_id'],
            'is_public' => $request->boolean('is_public'),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'file_path' => $firstPath,
            'file_type' => $firstFileType,
            'file_size' => $firstFile->getSize() ?: null,
        ]);

        // Create File record for the first file.
        $dataset->files()->create([
            'file_name' => (string) $firstFile->getClientOriginalName(),
            'file_type' => (string) $firstFileType,
            'file_path' => (string) $firstPath,
            'file_size' => (int) ($firstFile->getSize() ?: 0),
        ]);

        // Store remaining files (if any).
        foreach (array_slice($files, 1) as $file) {
            $path = $file->store('datasets');

            $extension = strtolower((string) $file->getClientOriginalExtension());
            $fileType = match ($extension) {
                'csv' => 'CSV',
                'txt' => 'TXT',
                'xlsx' => 'XLSX',
                'json' => 'JSON',
                'xml' => 'XML',
                'arff' => 'ARFF',
                'zip' => 'ZIP',
                default => strtoupper($extension ?: 'N/A'),
            };

            $dataset->files()->create([
                'file_name' => (string) $file->getClientOriginalName(),
                'file_type' => (string) $fileType,
                'file_path' => (string) $path,
                'file_size' => (int) ($file->getSize() ?: 0),
            ]);
        }

        return back()->with('success', 'Dataset uploaded successfully.');
    }

    /**
     * Show a single dataset.
     * - public: visible to anyone
     * - private: only owner or admin
     */
    public function show(int $id)
    {
        $dataset = Dataset::with(['user', 'category', 'files'])->findOrFail($id);

        if (!$dataset->is_public) {
            $user = Auth::user();
            $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
            $isAdmin = $user && ($user->role === 'admin');

            if (!$isOwner && !$isAdmin) {
                abort(403);
            }
        }

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

        return redirect()->route('datasets.index')->with('success', 'Dataset has been updated.');
    }

    /**
     * Soft delete dataset (must belong to authenticated user).
     */
    public function destroy(int $id)
    {
        $dataset = Dataset::with('files')->findOrFail($id);

        $user = Auth::user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        if (!$isOwner && !$isAdmin) {
            abort(403);
        }

        // Delete physical files in storage (all related files)
        foreach ($dataset->files as $file) {
            if (!empty($file->file_path)) {
                Storage::delete($file->file_path);
            }
        }

        // Also remove legacy single-file path if present
        if (!empty($dataset->file_path)) {
            Storage::delete($dataset->file_path);
        }

        // Delete DB records (files first, then dataset)
        $dataset->files()->delete();
        $dataset->delete();

        return back()->with('success', 'Dataset has been deleted.');
    }

    /**
     * Generate and return the share URL for a dataset.
     */
    public function share(int $id)
    {
        // Find dataset by ID first (do not scope by user_id), then authorize.
        $dataset = Dataset::findOrFail($id);

        $user = Auth::user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        if (!$isOwner && !$isAdmin) {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                ], 403);
            }

            abort(403);
        }

        if (empty($dataset->share_token)) {
            $dataset->share_token = (string) Str::uuid();
            $dataset->save();
        }

        $shareUrl = url('/datasets/share/' . $dataset->share_token);

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'share_url' => $shareUrl,
                'token' => $dataset->share_token,
            ]);
        }

        return back()->with('share_url', $shareUrl);
    }

    /**
     * Show a dataset via share token.
     *
     * Guests are allowed to view the page.
     * If the dataset is private, access is still granted via possession of the token.
     */
    public function shareShow(string $token)
    {
        $dataset = Dataset::with(['user', 'files'])->where('share_token', $token)->firstOrFail();

        // Mark this dataset as shared for this browser/session so downloads can be allowed.
        session(['shared_dataset_' . $dataset->id => true]);

        return view('datasets.share', compact('dataset'));
    }

    /**
     * Download dataset file.
     * - public: anyone can download
     * - private: owner/admin OR user who opened via valid share token (session flag)
     */
    public function download(int $id)
    {
        // Keep backward compatibility: /datasets/{id}/download now returns ZIP.
        return $this->downloadZip($id);
    }

    /**
     * Download a single file belonging to a dataset.
     * Access rules:
     * - public dataset: anyone
     * - private: owner/admin or shared in session
     */
    public function downloadFile(File $file)
    {
        $file->loadMissing('dataset');
        $dataset = $file->dataset;

        if (!$dataset) {
            abort(404);
        }

        if (!$dataset->is_public) {
            $user = Auth::user();
            $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
            $isAdmin = $user && ($user->role === 'admin');
            $sharedInSession = session()->has('shared_dataset_' . $dataset->id);

            if (!$isOwner && !$isAdmin && !$sharedInSession) {
                abort(403);
            }
        }

        if (!Storage::exists($file->file_path)) {
            abort(404);
        }

        // Count a successful authorized download attempt
        if (Schema::hasColumn('datasets', 'download_count')) {
            $dataset->increment('download_count');
        }

        $downloadName = $file->file_name ?: 'file';

        return Storage::download($file->file_path, $downloadName);
    }

    /**
     * Download all dataset files packed as a ZIP.
     * Access rules are the same as download(): public, owner/admin, or shared in session.
     */
    public function downloadZip(int $id)
    {
        $dataset = Dataset::with('files')->findOrFail($id);

        if (!$dataset->is_public) {
            $sharedInSession = session()->has('shared_dataset_' . $dataset->id);

            $user = Auth::user();
            $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
            $isAdmin = $user && ($user->role === 'admin');

            if (!$isOwner && !$isAdmin && !$sharedInSession) {
                abort(403);
            }
        }

        if ($dataset->files->isEmpty()) {
            abort(404);
        }

        $tempDir = 'temp';
        Storage::makeDirectory($tempDir);

        $safeBase = ($dataset->name ?: 'dataset');
        $safeBase = preg_replace('/[^A-Za-z0-9_\-. ]+/', '', $safeBase) ?: 'dataset';
        $zipRelative = $tempDir . '/' . $safeBase . '-' . Str::random(12) . '.zip';

        $zipPath = Storage::path($zipRelative);

        $zip = new \ZipArchive();
        $opened = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($opened !== true) {
            abort(500, 'Failed to create ZIP archive.');
        }

        foreach ($dataset->files as $file) {
            if (!$file->file_path || !Storage::exists($file->file_path)) {
                continue;
            }

            $absolute = Storage::path($file->file_path);
            $nameInZip = $file->file_name ?: basename($absolute);

            // Avoid duplicate names inside zip
            if ($zip->locateName($nameInZip) !== false) {
                $nameInZip = Str::random(6) . '-' . $nameInZip;
            }

            $zip->addFile($absolute, $nameInZip);
        }

        $zip->close();

        $downloadZipName = $safeBase . '.zip';

        // Count a successful authorized download attempt
        if (Schema::hasColumn('datasets', 'download_count')) {
            $dataset->increment('download_count');
        }

        return response()->download($zipPath, $downloadZipName)->deleteFileAfterSend(true);
    }

    /**
     * AJAX endpoint: increment download_count for ZIP download without page reload.
     * Must NOT return a file, only JSON.
     */
    public function incrementDownloadCount(int $id)
    {
        $dataset = Dataset::findOrFail($id);

        // Apply the same authorization as downloadZip (public OR owner/admin OR shared in session).
        if (!$dataset->is_public) {
            $sharedInSession = session()->has('shared_dataset_' . $dataset->id);

            $user = Auth::user();
            $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
            $isAdmin = $user && ($user->role === 'admin');

            if (!$isOwner && !$isAdmin && !$sharedInSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                ], 403);
            }
        }

        $dataset->increment('download_count');

        if (config('app.debug')) {
            Log::info('DOWNLOAD COUNT++ (AJAX) dataset=' . $dataset->id);
        }

        return response()->json([
            'success' => true,
            'download_count' => (int) $dataset->download_count,
        ]);
    }

    /**
     * AJAX endpoint: toggle like/unlike for a dataset.
     * - auth required (route group)
     * - public dataset: any authed user
     * - private: only owner/admin
     */
    public function toggleLike(int $id, Request $request)
    {
        $dataset = Dataset::findOrFail($id);

        $user = Auth::user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        if (!$dataset->is_public && !$isOwner && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $userId = (int) $user->id;
        $datasetId = (int) $dataset->id;

        $liked = false;
        $likesCount = (int) ($dataset->likes_count ?? 0);

        DB::transaction(function () use ($userId, $datasetId, &$liked, &$likesCount) {
            $ds = Dataset::query()->lockForUpdate()->findOrFail($datasetId);

            $exists = DB::table('dataset_likes')
                ->where('dataset_id', $datasetId)
                ->where('user_id', $userId)
                ->exists();

            if ($exists) {
                DB::table('dataset_likes')
                    ->where('dataset_id', $datasetId)
                    ->where('user_id', $userId)
                    ->delete();

                $ds->likes_count = max(0, (int) ($ds->likes_count ?? 0) - 1);
                $ds->save();

                $liked = false;
            } else {
                DB::table('dataset_likes')->insert([
                    'dataset_id' => $datasetId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $ds->likes_count = (int) ($ds->likes_count ?? 0) + 1;
                $ds->save();

                $liked = true;
            }

            $likesCount = (int) ($ds->likes_count ?? 0);
        });

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $likesCount,
        ]);
    }

    /**
     * AJAX: Update dataset from the dataset detail page.
     * Authorization: owner or admin.
     */
    public function updateAjax(Request $request, int $id)
    {
        $dataset = Dataset::findOrFail($id);

        $user = Auth::user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $dataset->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => (int) $validated['category_id'],
            'is_public' => $request->boolean('is_public'),
        ]);

        return response()->json([
            'success' => true,
            'dataset' => [
                'id' => (int) $dataset->id,
                'name' => (string) $dataset->name,
                'description' => $dataset->description,
                'category_id' => (int) $dataset->category_id,
                'is_public' => (bool) $dataset->is_public,
                'updated_at' => $dataset->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * AJAX: Add one or more files to an existing dataset (owner/admin).
     */
    public function addFilesAjax(Request $request, int $id)
    {
        $dataset = Dataset::findOrFail($id);

        $user = Auth::user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $allowedExtensions = ['csv', 'txt', 'xlsx', 'json', 'xml', 'arff', 'zip'];

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'required',
                'file',
                function (string $attribute, $value, $fail) use ($allowedExtensions) {
                    if (!$value instanceof UploadedFile) {
                        $fail('Invalid file.');
                        return;
                    }

                    $ext = strtolower((string) $value->getClientOriginalExtension());
                    if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
                        $fail('Unsupported file type. Allowed: ' . strtoupper(implode(', ', $allowedExtensions)) . '.');
                    }
                },
            ],
        ]);

        $uploadedFiles = $request->file('files', []);
        if (!is_array($uploadedFiles) || count($uploadedFiles) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'No files uploaded.',
            ], 422);
        }

        $created = [];

        foreach ($uploadedFiles as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('datasets');

            $extension = strtolower((string) $file->getClientOriginalExtension());
            $fileType = match ($extension) {
                'csv' => 'CSV',
                'txt' => 'TXT',
                'xlsx' => 'XLSX',
                'json' => 'JSON',
                'xml' => 'XML',
                'arff' => 'ARFF',
                'zip' => 'ZIP',
                default => strtoupper($extension ?: 'N/A'),
            };

            $row = $dataset->files()->create([
                'file_name' => (string) $file->getClientOriginalName(),
                'file_type' => (string) $fileType,
                'file_path' => (string) $path,
                'file_size' => (int) ($file->getSize() ?: 0),
            ]);

            $created[] = [
                'id' => (int) $row->id,
                'file_name' => (string) $row->file_name,
                'file_type' => (string) ($row->file_type ?? ''),
                'file_size' => (int) ($row->file_size ?? 0),
                'size_human' => (string) ($row->size_human ?? ''),
            ];
        }

        return response()->json([
            'success' => true,
            'files' => $created,
            'files_count' => (int) $dataset->files()->count(),
        ]);
    }

    /**
     * AJAX: Delete a single file from a dataset (owner/admin).
     */
    public function deleteFileAjax(int $datasetId, int $fileId)
    {
        $dataset = Dataset::with('files')->findOrFail($datasetId);

        $user = Auth::user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $file = $dataset->files()->where('id', $fileId)->firstOrFail();

        if (!empty($file->file_path)) {
            Storage::delete($file->file_path);
        }

        $file->delete();

        return response()->json([
            'success' => true,
            'files_count' => (int) $dataset->files()->count(),
        ]);
    }

    /**
     * AJAX: Delete dataset from the dataset detail page.
     * Authorization: owner or admin.
     */
    public function destroyAjax(int $id)
    {
        $dataset = Dataset::with('files')->findOrFail($id);

        $user = Auth::user();
        $isOwner = $user && ((int) $dataset->user_id === (int) $user->id);
        $isAdmin = $user && ($user->role === 'admin');

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

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

        return response()->json([
            'success' => true,
            'redirect_url' => route('home'),
        ]);
    }
}
