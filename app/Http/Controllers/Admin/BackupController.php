<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function index()
    {
        $files = collect(Storage::disk('backups')->files())
            ->filter(fn (string $path) => str_ends_with($path, '.zip'))
            ->map(function (string $path) {
                return [
                    'filename' => basename($path),
                    'size' => Storage::disk('backups')->size($path),
                    'last_modified' => Storage::disk('backups')->lastModified($path),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();

        return Inertia::render('Admin/DatabaseBackup/IndexView', [
            'backups' => $files,
        ]);
    }

    public function store()
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true, '--no-interaction' => true]);

            $this->logBackupActivity('created', 'Created a database backup');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create backup: '.$e->getMessage());
        }

        return redirect()->route('admin.backup.index')
            ->with('success', 'Backup created successfully');
    }

    public function download(Request $request, string $filename)
    {
        $filename = basename($filename);

        if (! Storage::disk('backups')->exists($filename)) {
            abort(404);
        }

        return Storage::disk('backups')->download($filename);
    }

    public function destroy(Request $request, string $filename)
    {
        $filename = basename($filename);

        if (! Storage::disk('backups')->exists($filename)) {
            return back()->with('error', 'Backup file not found.');
        }

        Storage::disk('backups')->delete($filename);

        $this->logBackupActivity('deleted', "Deleted backup {$filename}");

        return back()->with('success', 'Backup deleted successfully.');
    }

    private function logBackupActivity(string $action, string $description): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => null,
            'subject_id' => null,
            'description' => $description,
            'properties' => null,
            'ip_address' => request()->ip(),
        ]);
    }
}
