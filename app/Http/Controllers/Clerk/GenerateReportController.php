<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use App\Models\Phase;
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
        $reportType = $request->reportType;
        
        // Validate based on report type
        if (in_array($reportType, ['burial', 'deceased'])) {
            $request->validate([
                'reportType' => 'required|in:burial,deceased,summary,phase',
                'startDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:startDate',
                'format' => 'required|in:pdf,excel',
            ]);
        } elseif ($reportType === 'summary') {
            $request->validate([
                'reportType' => 'required|in:burial,deceased,summary,phase',
                'monthDate' => 'required|date',
                'format' => 'required|in:pdf,excel',
            ]);
        } elseif ($reportType === 'phase') {
            $request->validate([
                'reportType' => 'required|in:burial,deceased,summary,phase',
                'format' => 'required|in:pdf,excel',
            ]);
        }

        $format = $request->format;

        if ($reportType === 'phase') {
            $data = $this->getPhaseAvailabilityData();
            
            if ($format === 'pdf') {
                return $this->generatePhasePDF($data);
            }
            return $this->generatePhaseExcel($data);
        }

        if ($reportType === 'summary') {
            $monthDate = $request->monthDate;
            $data = $this->getMonthlySummaryData($monthDate);
            
            if ($format === 'pdf') {
                return $this->generateMonthlySummaryPDF($data, $monthDate);
            }
            return $this->generateMonthlySummaryExcel($data, $monthDate);
        }

        $startDate = $request->startDate;
        $endDate = $request->endDate;
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
        }
    }

    private function getMonthlySummaryData($monthDate)
    {
        $startOfMonth = date('Y-m-01', strtotime($monthDate));
        $endOfMonth = date('Y-m-t', strtotime($monthDate));

        return [
            'month' => date('F Y', strtotime($monthDate)),
            'total_burials' => BurialRecord::whereHas('deceasedRecord', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date_of_depository', [$startOfMonth, $endOfMonth]);
            })->count(),
            'total_deceased' => DeceasedRecord::whereBetween('date_of_depository', [$startOfMonth, $endOfMonth])->count(),
            'by_day' => DeceasedRecord::whereBetween('date_of_depository', [$startOfMonth, $endOfMonth])
                ->selectRaw('DATE(date_of_depository) as day, COUNT(*) as count')
                ->groupBy('day')
                ->orderBy('day')
                ->get(),
        ];
    }

    private function getPhaseAvailabilityData()
    {
        return Phase::with(['clusters.lots.burialRecords'])
            ->get()
            ->map(function ($phase) {
                $totalClusters = $phase->clusters->count();
                $totalLots = $phase->clusters->sum(function ($cluster) {
                    return $cluster->lots->count();
                });
                $totalOccupants = $phase->clusters->sum(function ($cluster) {
                    return $cluster->lots->sum(function ($lot) {
                        return $lot->burialRecords->count();
                    });
                });
                $availableLots = $totalLots - $totalOccupants;

                return [
                    'phase_name' => $phase->phase_name,
                    'total_clusters' => $totalClusters,
                    'total_lots' => $totalLots,
                    'total_occupants' => $totalOccupants,
                    'available_lots' => $availableLots,
                    'occupancy_rate' => $totalLots > 0 ? round(($totalOccupants / $totalLots) * 100, 2) : 0,
                ];
            });
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

    private function generateMonthlySummaryPDF($data, $monthDate)
    {
        $pdf = Pdf::loadView('reports.summary', [
            'data' => $data,
            'monthDate' => $monthDate,
        ]);

        $filename = 'monthly_summary_' . date('Y-m', strtotime($monthDate)) . '.pdf';
        return $pdf->download($filename);
    }

    private function generatePhasePDF($data)
    {
        $pdf = Pdf::loadView('reports.phase', [
            'data' => $data,
        ]);

        $filename = 'phase_availability_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    private function generateExcel($reportType, $data, $startDate, $endDate)
    {
        $filename = $reportType . '_report_' . date('Y-m-d') . '.xlsx';
        
        $headerRow = [
            'Report Type' => ucfirst($reportType) . ' Report',
            'Period' => $startDate . ' to ' . $endDate,
            'Generated' => date('Y-m-d H:i:s'),
        ];
        
        if ($reportType === 'burial') {
            $exportData = collect([]);
            $exportData->push($headerRow);
            $exportData->push([]);
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
            $exportData->push([]);
            $exportData->push([
                'Seq. No' => 'Seq. No',
                'Full Name' => 'Full Name',
                'Date of Burial' => 'Date of Burial',
                'Address' => 'Address',
                'Applicant' => 'Applicant',
            ]);
            
            foreach ($data as $index => $deceased) {
                $fullName = trim($deceased->first_name . ' ' . ($deceased->middle_name ?? '') . ' ' . $deceased->last_name);
                $exportData->push([
                    'Seq. No' => $index + 1,
                    'Full Name' => $fullName,
                    'Date of Burial' => $deceased->date_of_depository,
                    'Address' => $deceased->address,
                    'Applicant' => $deceased->applicant ? $deceased->applicant->first_name . ' ' . $deceased->applicant->last_name : 'N/A',
                ]);
            }
        }

        return (new FastExcel($exportData))->download($filename);
    }

    private function generateMonthlySummaryExcel($data, $monthDate)
    {
        $filename = 'monthly_summary_' . date('Y-m', strtotime($monthDate)) . '.xlsx';
        
        $exportData = collect([]);
        $exportData->push([
            'Report Type' => 'Monthly Summary',
            'Month' => $data['month'],
            'Generated' => date('Y-m-d H:i:s'),
        ]);
        $exportData->push([]);
        $exportData->push(['Metric' => 'Metric', 'Value' => 'Value']);
        $exportData->push(['Metric' => 'Total Burials', 'Value' => $data['total_burials']]);
        $exportData->push(['Metric' => 'Total Deceased', 'Value' => $data['total_deceased']]);
        $exportData->push([]);
        $exportData->push(['Day' => 'Day', 'Count' => 'Count']);
        
        foreach ($data['by_day'] as $day) {
            $exportData->push(['Day' => $day->day, 'Count' => $day->count]);
        }

        return (new FastExcel($exportData))->download($filename);
    }

    private function generatePhaseExcel($data)
    {
        $filename = 'phase_availability_' . date('Y-m-d') . '.xlsx';
        
        $exportData = collect([]);
        $exportData->push([
            'Report Type' => 'Phase Availability Report',
            'Generated' => date('Y-m-d H:i:s'),
        ]);
        $exportData->push([]);
        $exportData->push([
            'Phase Name' => 'Phase Name',
            'Total Clusters' => 'Total Clusters',
            'Total Lots' => 'Total Lots',
            'Total Occupants' => 'Total Occupants',
            'Available Lots' => 'Available Lots',
            'Occupancy Rate (%)' => 'Occupancy Rate (%)',
        ]);
        
        foreach ($data as $phase) {
            $exportData->push([
                'Phase Name' => $phase['phase_name'],
                'Total Clusters' => $phase['total_clusters'],
                'Total Lots' => $phase['total_lots'],
                'Total Occupants' => $phase['total_occupants'],
                'Available Lots' => $phase['available_lots'],
                'Occupancy Rate (%)' => $phase['occupancy_rate'],
            ]);
        }

        return (new FastExcel($exportData))->download($filename);
    }
}
