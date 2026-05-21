<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClerkInvitationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ClerkInvitation/IndexView');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // TODO: Implement invitation logic
        
        return back()->with('success', 'Invitation sent successfully!');
    }
}
