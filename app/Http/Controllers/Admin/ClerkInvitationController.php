<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ClerkInvitationMail;
use App\Models\ClerkInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClerkInvitationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ClerkInvitation/CreateView');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email|unique:users,email']);

        $token = Str::random(64);

        ClerkInvitation::updateOrCreate(
            ['email' => $request->email],
            [
                'token' => $token,
                'expires_at' => now()->addHours(24),
                'used_at' => null,
            ]
        );

        $url = route('register', ['token' => $token]);

        Mail::to($request->email)->send(new ClerkInvitationMail($url));

        return back()->with('success', "Invitation sent to {$request->email}");
    }
}
