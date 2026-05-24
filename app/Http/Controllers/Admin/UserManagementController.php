<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter') && $request->filter !== 'all') {
            $query->where('role', $request->filter);
        }

        if ($request->filled('sort_field')) {
            $direction = $request->get('sort_direction', 'asc');
            $query->orderBy($request->sort_field, $direction);
        } else {
            $query->latest();
        }

        $users = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/UserManagement/IndexView', [
            'users' => $users,
            'filters' => [
                'search' => $request->search,
                'filter' => $request->filter,
                'sort_field' => $request->sort_field,
                'sort_direction' => $request->sort_direction,
            ],
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.user_management.index')
            ->with('success', 'User deleted successfully');
    }
}
