<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function terminate(User $user, array $data, int $by): User
    {
        abort_if($user->terminated_at !== null, 422, 'Already terminated.');

        return DB::transaction(function () use ($user, $data, $by) {
            $user->update([
                'terminated_at' => now(),
                'terminated_reason' => $data['terminated_reason'],
                'terminated_by' => $by,
                'terminated_notes' => $data['terminated_notes'] ?? null,
            ]);

            return $user;
        });
    }

    public function reinstate(User $user): User
    {
        abort_if($user->terminated_at === null, 422, 'Not terminated.');

        if (User::active()->where('email', $user->email)->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Email already taken by active user. Free email first.']);
        }

        return DB::transaction(function () use ($user) {
            $user->update([
                'terminated_at' => null,
                'terminated_reason' => null,
                'terminated_by' => null,
                'terminated_notes' => null,
            ]);

            return $user;
        });
    }
}
