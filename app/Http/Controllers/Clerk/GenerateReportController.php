<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;

class GenerateReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Clerk/GenerateReport/IndexView');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'reportType' => 'required|in:burial,deceased,summary',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'format' => 'required|in:pdf,excel',
        ]);

        $reportType = $request->reportType;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $format = $request->format;

        $data = $this->getReportData($reportType, $startDate, $endDate);

        if ($format === 'pdf') {
            return $this->generatePDF($reportType, $data, $startDate, $endDate);
        }

        return $this->generateExcel($reportType, $data, $startDate, $endDate);
    }

    private function getReportData($reportType, $startDate, $endDate)
    {
        switch ($reportType) {
            case 'burial':
                return BurialRecord::with(['deceasedRecord', 'lot.cluster.phase', 'user'])
                    ->whereHas('deceasedRecord', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('date_of_depository', [$startDate, $endDate]);
                    })
                    ->get();

            case 'deceased':
                return DeceasedRecord::with(['applicant'])
                    ->whereBetween('date_of_depository', [$startDate, $endDate])
                    ->get();

            case 'summary':
                return [
                    'total_burials' => BurialRecord::whereHas('deceasedRecord', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('date_of_depository', [$startDate, $endDate]);
                    })->count(),
                    'total_deceased' => DeceasedRecord::whereBetween('date_of_depository', [$startDate, $endDate])->count(),
                    'by_month' => DeceasedRecord::whereBetween('date_of_depository', [$startDate, $endDate])
                        ->selectRaw('DATE_FORMAT(date_of_depository, "%Y-%m") as month, COUNT(*) as count')
                        ->groupBy('month')
                        ->get(),
                ];
        }
    }

    private function generatePDF($reportType, $data, $startDate, $endDate)
    {
        $pdf = Pdf::loadView('reports.' . $reportType, [
            'data' => $data,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $filename = $reportType . '_report_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    private function generateExcel($reportType, $data, $startDate, $endDate)
    {
        $filename = $reportType . '_report_' . date('Y-m-d') . '.xlsx';
        
        // Add header row with date range
        $headerRow = [
            'Report Type' => ucfirst($reportType) . ' Report',
            'Period' => $startDate . ' to ' . $endDate,
            'Generated' => date('Y-m-d H:i:s'),
        ];
        
        if ($reportType === 'burial') {
            $exportData = collect([]);
            $exportData->push($headerRow);
            $exportData->push([]); // Empty row
            // Column headers
            $exportData->push([
                'Seq. No' => 'Seq. No',
                'Deceased Name' => 'Deceased Name',
                'Date of Burial' => 'Date of Burial',
                'Phase' => 'Phase',
                'Cluster' => 'Cluster',
                'Lot' => 'Lot',
                'Address' => 'Address',
            ]);
            
            foreach ($data as $index => $burial) {
                $exportData->push([
                    'Seq. No' => $index + 1,
                    'Deceased Name' => $burial->deceasedRecord->first_name . ' ' . $burial->deceasedRecord->last_name,
                    'Date of Burial' => $burial->deceasedRecord->date_of_depository,
                    'Phase' => $burial->lot && $burial->lot->cluster && $burial->lot->cluster->phase ? $burial->lot->cluster->phase->phase_name : 'N/A',
                    'Cluster' => $burial->lot && $burial->lot->cluster ? $burial->lot->cluster->cluster_name : 'N/A',
                    'Lot' => $burial->lot ? $burial->lot->column . $burial->lot->row : 'N/A',
                    'Address' => $burial->deceasedRecord->address,
                ]);
            }
        } elseif ($reportType === 'deceased') {
            $exportData = collect([]);
            $exportData->push($headerRow);
            $exportData->push([]); // Empty row
            // Column headers
            $exportData->push([
                'ID' => 'ID',
                'Full Name' => 'Full Name',
                'Date of Burial' => 'Date of Burial',
                'Address' => 'Address',
                'Applicant' => 'Applicant',
            ]);
            
            foreach ($data as $deceased) {
                $fullName = trim($deceased->first_name . ' ' . ($deceased->middle_name ?? '') . ' ' . $deceased->last_name);
                $exportData->push([
                    'ID' => $deceased->id,
                    'Full Name' => $fullName,
                    'Date of Burial' => $deceased->date_of_depository,
                    'Address' => $deceased->address,
                    'Applicant' => $deceased->applicant ? $deceased->applicant->first_name . ' ' . $deceased->applicant->last_name : 'N/A',
                ]);
            }
        } else {
            $exportData = collect([]);
            $exportData->push($headerRow);
            $exportData->push([]); // Empty row
            // Column headers
            $exportData->push(['Metric' => 'Metric', 'Value' => 'Value']);
            $exportData->push(['Metric' => 'Total Burials', 'Value' => $data['total_burials']]);
            $exportData->push(['Metric' => 'Total Deceased', 'Value' => $data['total_deceased']]);
        }

        return (new FastExcel($exportData))->download($filename);
    }
}
