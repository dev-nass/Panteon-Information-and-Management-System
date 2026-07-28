<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileRequest;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/ProfileView');
    }

    public function update(ProfileRequest $request)
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Profile updated successfully!');
    }
}
