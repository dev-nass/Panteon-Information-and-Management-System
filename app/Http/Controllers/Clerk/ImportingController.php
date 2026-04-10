<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportingController extends Controller
{
    public function index()
    {
        return Inertia::render('Clerk/ImportRecord/IndexView');
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
            // if (!auth()->check()) {
            //     return back()->with('error', 'You must be logged in to import records');
            // }

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            array_shift($rows);

            \Log::info('First row data:', ['row' => $rows[0] ?? 'no rows']);

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed

                try {
                    // Expected columns: NO., BURIAL DATE, NAME OF DECEASED, APPLICANT, BRGY/ADDRESS
                    // Column indices: 0=NO., 1=BURIAL DATE, 2=NAME OF DECEASED, 3=APPLICANT, 4=BRGY/ADDRESS
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

                    // Create deceased record
                    $deceased = DeceasedRecord::create([
                        'first_name' => $nameParts['first_name'],
                        'middle_name' => $nameParts['middle_name'],
                        'last_name' => $nameParts['last_name'],
                        'address' => $row[4] ?? null, // BRGY/ADDRESS (index 4)
                        'date_of_depository' => $this->parseDate($row[1]), // BURIAL DATE (index 1)
                    ]);

                    // Create applicant if data exists (index 3)
                    $applicantName = trim($row[3] ?? '');
                    if (!empty($applicantName)) {
                        $applicantParts = $this->parseFullName($applicantName);
                        Applicant::create([
                            'deceased_record_id' => $deceased->id,
                            'first_name' => $applicantParts['first_name'],
                            'middle_name' => $applicantParts['middle_name'],
                            'last_name' => $applicantParts['last_name'],
                            'contact_number' => '',
                        ]);
                    }

                    // Create burial record with null lot and current user
                    BurialRecord::create([
                        'deceased_record_id' => $deceased->id,
                        'lot_id' => null,
                        'user_id' => null,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                    \Log::error("Import error on row {$rowNumber}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'row_data' => $row
                    ]);
                }
            }

            DB::commit();

            $message = "Successfully imported {$imported} records";
            if (!empty($errors)) {
                $message .= " with " . count($errors) . " errors";
                \Log::info('Import errors:', $errors);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to process file: ' . $e->getMessage());
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
        } elseif ($count === 2) {
            return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => $parts[1]];
        } else {
            // First name, middle name(s), last name
            $firstName = array_shift($parts);
            $lastName = array_pop($parts);
            $middleName = implode(' ', $parts);
            return ['first_name' => $firstName, 'middle_name' => $middleName, 'last_name' => $lastName];
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
