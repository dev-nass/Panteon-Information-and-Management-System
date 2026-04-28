<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use App\Models\ImportedLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Lot; //Noel

class ImportingController extends Controller
{
    public function index()
    {
        $logs = ImportedLog::orderBy('created_at', 'desc')->limit(50)->get();
        return Inertia::render('Clerk/ImportRecord/IndexView', [
            'importLogs' => $logs,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', 'Invalid file format or size');
        }

        try {

            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            array_shift($rows);

            \Log::info('Importing file:', ['file_name' => $fileName]);
            \Log::info('First row data:', ['row' => $rows[0] ?? 'no rows']);

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            $importLog = ImportedLog::create([
                'file_name' => $fileName,
                'imported_by' => auth()->id(),
                'status' => 'processing',
            ]);

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed

                try {
                    // Expected columns: NO., BURIAL DATE, NAME OF DECEASED, APPLICANT, PHASE, CLUSTER, APT. NUMBER, BRGY/ADDRESS
                    // Column indices: 0=NO., 1=BURIAL DATE, 2=NAME OF DECEASED, 3=APPLICANT, 4=PHASE, 5=CLUSTER, 6=APT. NUMBER, 7=BRGY/ADDRESS
                    // Skip completely empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    if (empty($row[1]) || empty($row[2])) {
                        $errors[] = "Row {$rowNumber}: Missing required fields (burial date or name of deceased)";
                        continue;
                    }

                    // Parse the full name from "NAME OF DECEASED" column (index 2)
                    $fullName = trim($row[2]);
                    $nameParts = $this->parseFullName($fullName);
                    $burialDate = $this->parseDate($row[1]); // BURIAL DATE (index 1)

                    // Check if deceased record already exists
                    $existingRecord = DeceasedRecord::where('first_name', $nameParts['first_name'])
                        ->where('last_name', $nameParts['last_name'])
                        ->where('date_of_depository', $burialDate)
                        ->first();

                    if ($existingRecord) {
                        $errors[] = "Row {$rowNumber}: Deceased record already exists (ID: {$existingRecord->id})";
                        continue;
                    }

                    // NOEL - Find lot based on PHASE, CLUSTER, and APT. NUMBER BEFORE creating records
                    $phaseName = trim($row[4] ?? '');
                    $clusterName = trim($row[5] ?? '');
                    $aptNumber = trim($row[6] ?? ''); // e.g. 12A or 2B

                    // NOEL - Extract column number and row letter from APT. NUMBER
                    $column = preg_replace('/\D/', '', $aptNumber);
                    $rowLetter = preg_replace('/\d/', '', $aptNumber);

                    // NOEL - Find the lot based on the provided phase, cluster, column, and row
                    $lot = Lot::where('column', $column)
                        ->where('row', $rowLetter)
                        ->whereHas('cluster', function ($query) use ($clusterName, $phaseName) {
                            $query->where('cluster_name', $clusterName)
                                ->whereHas('phase', function ($phaseQuery) use ($phaseName) {
                                    $phaseQuery->where('phase_name', $phaseName);
                                });
                        })
                        ->whereDoesntHave('burialRecords')
                        ->first();

                    if (!$lot) {
                        $errors[] = "Row {$rowNumber}: Lot not found or already occupied (Phase: {$phaseName}, Cluster: {$clusterName}, Apt: {$aptNumber}) Unssagined";
                    }

                    // Create applicant if data exists (index 3)
                    $applicantId = null;
                    $applicantName = trim($row[3] ?? ''); // APPLICANT (index 3)
                    if (!empty($applicantName)) {
                        $applicantParts = $this->parseFullName($applicantName);
                        $applicant = Applicant::create([
                            'first_name' => $applicantParts['first_name'],
                            'middle_name' => $applicantParts['middle_name'],
                            'last_name' => $applicantParts['last_name'],
                            'contact_number' => '',
                        ]);
                        $applicantId = $applicant->id;
                    }

                    // Create deceased record
                    $deceased = DeceasedRecord::create([
                        'applicant_id' => $applicantId,
                        'first_name' => $nameParts['first_name'],
                        'middle_name' => $nameParts['middle_name'],
                        'last_name' => $nameParts['last_name'],
                        'address' => $row[7] ?? null, // BRGY/ADDRESS (index 7)
                        'date_of_depository' => $burialDate,
                    ]);

                    // Create burial record with lot_id and user_id
                    BurialRecord::create([
                        'deceased_record_id' => $deceased->id,
                        'lot_id' => $lot?->id,
                        'user_id' => auth()->id(),
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                    \Log::error("Import error on row {$rowNumber}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'row_data' => $row,
                    ]);
                }
            }


            DB::commit();

            if ($imported === 0) {
                $importLog->update([
                    'status' => 'failed',
                ]);
                return back()->with('error', 'No records were imported')->with('importErrors', $errors);
            }

            $message = "Successfully imported {$imported} records";
            if (!empty($errors)) {
                $message .= " with " . count($errors) . " skipped";
            }


            $importLog->update([
                'status' => 'successful',
            ]);
            return back()->with('success', $message)->with('importErrors', $errors);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to process file')->with('importErrors', [$e->getMessage()]);
        }
    }

    private function parseFullName($fullName)
    {
        $parts = preg_split('/\s+/', trim($fullName));
        $count = count($parts);

        if ($count === 0) {
            return ['first_name' => null, 'middle_name' => null, 'last_name' => null];
        } elseif ($count === 1) {
            return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => null];
        } else {
            // First name and last name only, disregard middle name
            $firstName = array_shift($parts);
            $lastName = array_pop($parts);
            return ['first_name' => $firstName, 'middle_name' => null, 'last_name' => $lastName];
        }
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Try to parse Excel date format
            if (is_numeric($date)) {
                $unixDate = ($date - 25569) * 86400;
                return date('Y-m-d', $unixDate);
            }

            // Try standard date formats
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return null;
            }
            return date('Y-m-d', $timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }
}