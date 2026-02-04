<?php

/**
 * Poznámka: Tento controller bol vytvorený/upravený s pomocou AI nástrojov (GitHub Copilot).
 */

namespace App\Http\Controllers;

use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatasetLikeController extends Controller
{
    /**
     * Toggle like for a dataset (auth only, JSON).
     */
    public function toggle(int $id, Request $request)
    {
        $dataset = Dataset::query()->with('user')->findOrFail($id);

        // Authorization: user must be allowed to see/like the dataset.
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
            // Lock the dataset row so counter stays consistent.
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
}

