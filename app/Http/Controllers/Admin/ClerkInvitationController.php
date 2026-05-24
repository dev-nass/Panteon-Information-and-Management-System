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

    public function index(Request $request)
    {
        $query = ClerkInvitation::query();

        if ($request->filled('search')) {
            $query->where('email', 'like', "%{$request->search}%");
        }

        if ($request->filled('filter') && $request->filter !== 'all') {
            if ($request->filter === 'used') {
                $query->whereNotNull('used_at');
            } elseif ($request->filter === 'unused') {
                $query->whereNull('used_at');
            }
        }

        if ($request->filled('sort_field')) {
            $direction = $request->get('sort_direction', 'asc');
            $query->orderBy($request->sort_field, $direction);
        } else {
            $query->latest();
        }

        $invitations = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/ClerkInvitation/IndexView', [
            'invitations' => $invitations,
            'filters' => [
                'search' => $request->search,
                'filter' => $request->filter,
                'sort_field' => $request->sort_field,
                'sort_direction' => $request->sort_direction,
            ],
        ]);
    }


    public function create()
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

        $url = route('clerk.register', ['token' => $token]);

        Mail::to($request->email)->send(new ClerkInvitationMail($url));

        return back()->with('success', "Invitation sent to {$request->email}");
    }
}
