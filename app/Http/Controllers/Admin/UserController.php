<?php

/**
 * Poznámka: Tento controller bol vytvorený/upravený s pomocou AI nástrojov (GitHub Copilot).
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::query()
            ->withCount('datasets')
            ->orderBy('id')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|min:3|max:50|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
            ],
            'role' => ['sometimes', Rule::in(['user', 'admin'])],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'user',
        ]);

        return redirect('/admin?tab=users')->with('success', 'Používateľ bol vytvorený.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'username' => ['required', 'string', 'min:3', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['user', 'admin'])],
        ];

        // Password is optional on update
        if ($request->filled('password')) {
            $rules['password'] = [
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
            ];
        }

        $data = $request->validate($rules);

        $user->username = $data['username'];
        $user->email = $data['email'];

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if (isset($data['role'])) {
            $user->role = $data['role'];
        }

        $user->save();

        return redirect('/admin?tab=users')->with('success', 'Používateľ bol upravený.');
    }

    /**
     * Remove the specified user from storage (soft delete).
     */
    public function destroy(User $user)
    {
        $currentUserId = Auth::id();
        $isSelfDelete = $currentUserId !== null && (int) $user->id === (int) $currentUserId;

        // Load datasets + files so we can delete physical files from storage
        $user->load(['datasets.files']);

        foreach ($user->datasets as $dataset) {
            foreach ($dataset->files as $file) {
                if (!empty($file->file_path)) {
                    \Illuminate\Support\Facades\Storage::delete($file->file_path);
                }
            }

            if (!empty($dataset->file_path)) {
                \Illuminate\Support\Facades\Storage::delete($dataset->file_path);
            }

            // Delete DB file records (FK cascade would also do this, but this is explicit)
            $dataset->files()->delete();

            // Delete dataset record
            $dataset->delete();
        }

        // Now delete the user itself (datasets table has onDelete('cascade') so this should be safe either way)
        $user->delete();

        if ($isSelfDelete) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('home')->with('success', 'Tvoj účet bol odstránený a bol si odhlásený.');
        }

        return redirect('/admin?tab=users')->with('success', 'Používateľ bol odstránený.');
    }
}
