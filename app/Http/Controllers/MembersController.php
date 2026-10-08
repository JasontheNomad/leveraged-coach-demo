<?php

namespace App\Http\Controllers;

use App\Models\User;

class MembersController extends Controller
{
    public function index()
    {
        $members = User::orderBy('full_name')
            ->get(['id', 'full_name', 'role', 'avatar_url']);

        return view('members.index', compact('members'));
    }
}
