<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BackupController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $files = collect(Storage::disk('backups')->files($this->directory()))
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

            $files = collect(Storage::disk('backups')->files($this->directory()))
                ->filter(fn (string $path) => str_ends_with($path, '.zip'))
                ->sortByDesc(fn ($path) => Storage::disk('backups')->lastModified($path))
                ->first();

            $this->logActivity(
                'created',
                null,
                'Created a database backup',
                extraProperties: [
                    'snapshot' => [
                        'filename' => $files ? basename($files) : null,
                        'size' => $files ? Storage::disk('backups')->size($files) : null,
                        'disk' => 'backups',
                    ],
                ],
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create backup: '.$e->getMessage());
        }

        return redirect()->route('admin.backup.index')
            ->with('success', 'Backup created successfully');
    }

    public function download(Request $request, string $filename)
    {
        $path = $this->backupPath($filename);

        if (! Storage::disk('backups')->exists($path)) {
            abort(404);
        }

        return Storage::disk('backups')->download($path, basename($path));
    }

    public function destroy(Request $request, string $filename)
    {
        $path = $this->backupPath($filename);

        if (! Storage::disk('backups')->exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }

        Storage::disk('backups')->delete($path);

        $this->logActivity(
            'deleted',
            null,
            'Deleted backup '.basename($path),
            extraProperties: ['snapshot' => ['filename' => basename($path), 'disk' => 'backups']],
        );

        return back()->with('success', 'Backup deleted successfully.');
    }

    /**
     * The subfolder on the `backups` disk that spatie/laravel-backup writes to.
     * It must stay in sync with `config('backup.backup.name')`, which is also
     * what `backup:list`, `backup:monitor` and `backup:clean` read from.
     */
    private function directory(): string
    {
        return config('backup.backup.name');
    }

    private function backupPath(string $filename): string
    {
        return $this->directory().'/'.basename($filename);
    }
}
