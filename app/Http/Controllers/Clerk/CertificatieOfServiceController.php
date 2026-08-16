<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CertificatieOfServiceController extends Controller
{
    public function show(BurialRecord $burial_record)
    {
        $burial_record->load([
            'deceasedRecord',
            'deceasedRecord.applicant',
            'lot',
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
                'place_of_death' => $deceased->place_of_death,
                'date_of_depository' => $deceased->date_of_depository,
                'burial_place' => $deceased->burial_place,
                'applicant_name' => $applicant ? trim("{$applicant->first_name} {$applicant->middle_name} {$applicant->last_name}") : '',
                'relationship' => $applicant?->relationship ?? '',
            ],
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
        ]);

        $pdf = Pdf::loadView('certificates.certificate_of_service_content', [
            'data' => $data,
        ]);

        $filename = 'certificate_of_service_'.$burial_record->id.'.pdf';

        return $pdf->download($filename);
    }
}
