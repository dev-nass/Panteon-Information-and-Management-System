<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/RegistrationView');
    }

    public function store(RegistrationRequest $request)
    {

        $validated = $request->validated();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'contact_number' => $validated['contact_number'],
            'email' => strtolower($validated['email']),
            'password' => bcrypt($validated['password']),
            'role' => 'clerk',
        ]);

        auth()->login($user);

        return redirect()->route('clerk.dashboard');
    }
}
