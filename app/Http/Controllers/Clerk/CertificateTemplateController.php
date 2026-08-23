<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CertificateTemplateController extends Controller
{
    public function index()
    {
        $templates = CertificateTemplate::with('uploadedBy')
            ->latest('created_at')
            ->get()
            ->map(fn (CertificateTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'uploaded_by' => $template->uploadedBy ? [
                    'first_name' => $template->uploadedBy->first_name,
                    'last_name' => $template->uploadedBy->last_name,
                ] : null,
                'created_at' => $template->created_at,
            ]);

        return Inertia::render('Clerk/CertificateTemplate/IndexView', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $path = $request->file('file')->storeAs(
            'certificate_templates',
            Str::uuid().'.pdf',
            'local'
        );

        CertificateTemplate::create([
            'name' => $validated['name'],
            'file_path' => $path,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Certificate template uploaded successfully.');
    }

    public function file(CertificateTemplate $certificate_template)
    {
        if (! str_ends_with($certificate_template->file_path, '.pdf')
            || ! Storage::disk('local')->exists($certificate_template->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->response(
            $certificate_template->file_path,
            $certificate_template->name.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function destroy(CertificateTemplate $certificate_template)
    {
        Storage::disk('local')->delete($certificate_template->file_path);

        $certificate_template->delete();

        return back()->with('success', 'Certificate template deleted successfully.');
    }
}
