<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CertificatieOfService extends Controller
{
    public function show(BurialRecord $burial_record)
    {
        $burial_record->load([
            'deceasedRecord',
            'deceasedRecord.applicant',
        ]);

        $deceased = $burial_record->deceasedRecord;
        $applicant = $deceased?->applicant;

        return Inertia::render('Clerk/CertificateOfService/ShowView', [
            'burial_record_id' => $burial_record->id,
            'csrf_token' => csrf_token(),
            'prefilled' => [
                'deceased_name' => trim("{$deceased->first_name} {$deceased->middle_name} {$deceased->last_name}"),
                'deceased_address' => $deceased->address,
                'date_of_death' => $deceased->date_of_death,
                'date_of_depository' => $deceased->date_of_depository,
                'applicant_name' => $applicant ? trim("{$applicant->first_name} {$applicant->middle_name} {$applicant->last_name}") : '',
                'relationship' => $applicant?->relationship ?? '',
            ],
        ]);
    }

    public function generate(Request $request, BurialRecord $burial_record)
    {
        $data = $request->validate([
            'deceased_name' => 'required|string|max:255',
            'deceased_address' => 'nullable|string|max:255',
            'date_of_death' => 'nullable|date',
            'date_of_depository' => 'nullable|date',
            'applicant_name' => 'required|string|max:255',
            'applicant_address' => 'required|string|max:255',
            'relationship' => 'nullable|string|max:255',
        ]);

        $pdf = Pdf::loadView('certificates.certificate_of_service', [
            'data' => $data,
        ]);

        $filename = 'certificate_of_service_' . $burial_record->id . '.pdf';
        return $pdf->download($filename);
    }
}
