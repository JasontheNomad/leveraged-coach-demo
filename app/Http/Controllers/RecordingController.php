<?php

namespace App\Http\Controllers;

use App\Models\Recording;
use App\Services\GroupService;

class RecordingController extends Controller
{
    public function index(GroupService $groups)
    {
        $query = Recording::with('room')
            ->where('status', 'ready')
            ->orderByDesc('recorded_at');

        if (auth()->check()) {
            $groups->scopeAccessible($query, auth()->user());
        } else {
            $query->whereNull('group_id');
        }

        $recordings = $query->paginate(12);

        return view('recordings.index', compact('recordings'));
    }

    public function show(Recording $recording, GroupService $groups)
    {
        abort_unless($recording->isReady(), 404);

        abort_unless($groups->canAccessContent(auth()->user(), 'recording', $recording->id), 403);

        return view('recordings.show', compact('recording'));
    }
}
