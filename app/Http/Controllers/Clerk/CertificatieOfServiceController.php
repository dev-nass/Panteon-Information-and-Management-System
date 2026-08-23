<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use App\Models\CertificateTemplate;
use App\Traits\LogsActivity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use setasign\Fpdi\Fpdi;

class CertificatieOfServiceController extends Controller
{
    use LogsActivity;

    public function show(BurialRecord $burial_record)
    {
        $burial_record->load([
            'deceasedRecord',
            'deceasedRecord.applicant',
            'lot',
        ]);

        $deceased = $burial_record->deceasedRecord;
        $applicant = $deceased?->applicant;

        $templates = CertificateTemplate::with('uploadedBy')
            ->latest('created_at')
            ->get()
            ->map(fn (CertificateTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
            ]);

        return Inertia::render('Clerk/CertificateOfService/ShowView', [
            'burial_record_id' => $burial_record->id,
            'csrf_token' => csrf_token(),
            'prefilled' => [
                'deceased_name' => trim("{$deceased->first_name} {$deceased->middle_name} {$deceased->last_name}"),
                'deceased_address' => $deceased->address,
                'date_of_death' => $deceased->date_of_death,
                'place_of_death' => $deceased->place_of_death,
                'date_of_depository' => $deceased->date_of_depository,
                'burial_place' => $deceased->burial_place,
                'applicant_name' => $applicant ? trim("{$applicant->first_name} {$applicant->middle_name} {$applicant->last_name}") : '',
                'relationship' => $applicant?->relationship ?? '',
            ],
            'templates' => $templates,
        ]);
    }

    public function generate(Request $request, BurialRecord $burial_record)
    {
        $data = $request->validate([
            'deceased_name' => 'required|string|max:255',
            'deceased_address' => 'required|string|max:255',
            'date_of_death' => 'required|date',
            'place_of_death' => 'required|string|max:255',
            'date_of_depository' => 'required|date',
            'burial_place' => 'required|string|max:255',
            'applicant_name' => 'required|string|max:255',
            'applicant_address' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'template_id' => 'nullable|integer|exists:certificate_templates,id',
        ]);

        $contentPdf = Pdf::loadView('certificates.certificate_of_service_content', [
            'data' => $data,
        ])->output();

        $description = "Generated certificate of service for {$data['deceased_name']}";

        if (! empty($data['template_id'])) {
            $templateName = CertificateTemplate::find($data['template_id'])?->name;
            $description .= " using template \"{$templateName}\"";
        }

        if (empty($data['template_id'])) {
            $this->logActivity('generated', $burial_record, $description);

            return response($contentPdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="certificate_of_service_'.$burial_record->id.'.pdf"',
            ]);
        }

        $template = CertificateTemplate::findOrFail($data['template_id']);
        $templatePath = $this->downgradePdf(Storage::disk('local')->path($template->file_path));

        // Write DomPDF output to a temp file so FPDI can import it
        $contentTmp = tempnam(sys_get_temp_dir(), 'cos_content_');
        file_put_contents($contentTmp, $contentPdf);

        $fpdi = new Fpdi;
        $fpdi->SetAutoPageBreak(false);

        // Use template page count as the page driver
        $templatePageCount = $fpdi->setSourceFile($templatePath);

        for ($p = 1; $p <= $templatePageCount; $p++) {
            $bgTpl = $fpdi->importPage($p);
            $size = $fpdi->getTemplateSize($bgTpl);

            $fpdi->AddPage(
                $size['width'] > $size['height'] ? 'L' : 'P',
                [$size['width'], $size['height']]
            );

            // Draw background (uploaded template)
            $fpdi->useTemplate($bgTpl, 0, 0, $size['width'], $size['height']);

            // Draw content (DomPDF Blade output) on top
            $fpdi->setSourceFile($contentTmp);
            $contentPageCount = $fpdi->setSourceFile($contentTmp);
            if ($p <= $contentPageCount) {
                $fgTpl = $fpdi->importPage($p);
                $fpdi->useTemplate($fgTpl, 0, 0, $size['width'], $size['height']);
            }

            // Switch back to template source for next iteration
            $fpdi->setSourceFile($templatePath);
        }

        unlink($contentTmp);
        unlink($templatePath);

        $filename = 'certificate_of_service_'.$burial_record->id.'.pdf';

        $this->logActivity('generated', $burial_record, $description);

        return response($fpdi->Output('S', $filename), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Convert a PDF to 1.4 (uncompressed) so FPDI's free parser can read it.
     * Returns path to a temp file — caller must unlink() it.
     */
    private function downgradePdf(string $sourcePath): string
    {
        $out = tempnam(sys_get_temp_dir(), 'cos_tpl_');
        exec(sprintf(
            'gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=%s %s',
            escapeshellarg($out),
            escapeshellarg($sourcePath)
        ));

        return $out;
    }
}
