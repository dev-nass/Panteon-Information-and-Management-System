<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Models\User;
use App\Services\BurialRecordService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;

class UserManagementController extends Controller
{
    use LogsActivity;

    public function __construct(protected BurialRecordService $service) {}

    public function index(Request $request)
    {
        $query = $this->applyFilters($request, User::query());

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

    public function export(Request $request)
    {
        $query = $this->applyFilters($request, User::query());

        $users = $query->get()->map(function ($user) {
            return [
                'ID' => $user->id,
                'First Name' => $user->first_name,
                'Middle Name' => $user->middle_name,
                'Last Name' => $user->last_name,
                'Email' => $user->email,
                'Contact Number' => $user->contact_number,
                'Role' => $user->role,
                'Verified' => $user->email_verified_at ? 'Yes' : 'No',
                'Member Since' => $user->created_at?->format('Y-m-d'),
            ];
        });

        $filename = 'users_'.date('Y-m-d').'.csv';

        return (new FastExcel($users))->download($filename);
    }

    private function applyFilters(Request $request, $query)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                    ->orWhere('first_name', 'like', "%{$search}%")
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

        return $query;
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === 'admin') {
            return back()->with('error', 'Admin accounts cannot be deleted.');
        }

        $this->logActivity(
            'deleted',
            $user,
            "Deleted user {$user->first_name} {$user->last_name}",
        );

        $user->delete();

        return redirect()->route('admin.user_management.index')
            ->with('success', 'User deleted successfully');
    }

    public function show(Request $request, User $user)
    {
        $search = $request->search;
        $filter = $request->filter ?? 'all';
        $disposal = $request->disposal;

        $allowedSorts = [
            'id',
            'deceased_first_name',
            'deceased_date_of_birth',
            'deceased_date_of_death',
            'deceased_date_of_depository',
        ];
        $sortField = in_array($request->sort_field, $allowedSorts)
            ? $request->sort_field
            : 'id';

        $sortDirection = $request->sort_direction ?? 'desc';

        $burialRecords = $this->service->index($sortField, $sortDirection, $search, $filter, $disposal, $user->id);

        return Inertia::render('Admin/UserManagement/ShowView', [
            'user_data' => $user->loadCount('burialRecords'),
            'burial_records' => BurialRecordResource::collection($burialRecords),
            'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'filter', 'disposal']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'contact_number' => 'required|string|max:20',
            'role' => 'required|in:clerk,head,admin',
        ]);

        if ($user->id === $request->user()->id && $user->role !== $validated['role']) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $name = "{$user->first_name} {$user->last_name}";
        $oldValues = $user->only(['first_name', 'middle_name', 'last_name', 'email', 'contact_number', 'role']);

        $user->update($validated);

        $newValues = $user->only(['first_name', 'middle_name', 'last_name', 'email', 'contact_number', 'role']);

        if ($oldValues['role'] !== $newValues['role']) {
            $this->logActivity(
                'role_changed',
                $user,
                "Changed {$name}'s role from {$oldValues['role']} to {$newValues['role']}",
                ['role' => $oldValues['role']],
                ['role' => $newValues['role']],
            );
        } else {
            $this->logActivity(
                'updated',
                $user,
                "Updated {$name}'s profile",
                $oldValues,
                $newValues,
            );
        }

        return back()->with('success', 'User updated successfully.');
    }
}
