<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use App\Models\ImportedExcelLog;
use App\Models\Lot;
use App\Services\RecordNormalizationService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportingController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected RecordNormalizationService $normalizer
    ) {}

    public function index()
    {
        $logs = ImportedExcelLog::orderBy('created_at', 'desc')->limit(50)->get();

        return Inertia::render('Admin/ImportRecord/IndexView', [
            'importLogs' => $logs,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
            'import_type' => 'required|in:normal,muslim,columbarium',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', 'Invalid file format or size');
        }

        $validated = $validator->validate();
        $importType = $validated['import_type'];

        try {

            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            array_shift($rows);

            \Log::info('Importing file:', ['file_name' => $fileName, 'import_type' => $importType]);
            \Log::info('First row data:', ['row' => $rows[0] ?? 'no rows']);

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            $importLog = ImportedExcelLog::create([
                'file_name' => $fileName,
                'imported_by' => auth()->id(),
                'status' => 'processing',
            ]);

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed

                try {
                    // Skip completely empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    $rowData = match ($importType) {
                        'normal' => $this->parseNormalRow($row),
                        'muslim' => $this->parseMuslimRow($row),
                        'columbarium' => $this->parseColumbariumRow($row),
                    };

                    $deceasedData = $rowData['deceased'];
                    $applicantData = $rowData['applicant'];
                    $lotData = $rowData['lot'];

                    // Required fields check
                    if (empty($deceasedData['first_name']) || empty($deceasedData['last_name'])) {
                        $errors[] = "Row {$rowNumber}: Missing name of deceased";

                        continue;
                    }

                    if ($importType === 'normal' && empty($deceasedData['date_of_depository'])) {
                        $errors[] = "Row {$rowNumber}: Missing burial date";

                        continue;
                    }

                    // Check if deceased record already exists
                    $existingRecord = $this->normalizer->findDuplicateDeceased(
                        $deceasedData['first_name'],
                        $deceasedData['last_name'],
                        $deceasedData['date_of_birth'],
                        $deceasedData['date_of_death']
                    );

                    if ($existingRecord) {
                        $errors[] = "Row {$rowNumber}: Deceased record already exists (ID: {$existingRecord->id})";

                        continue;
                    }

                    // Find lot based on PHASE, CLUSTER, and APT. NUMBER BEFORE creating records
                    $phaseName = $lotData['phase_name'];
                    $clusterName = $lotData['cluster_name'];
                    $aptNumber = $lotData['apt_number']; // e.g. 12A or 2B

                    // Extract column number and row letter from APT. NUMBER
                    $column = preg_replace('/\D/', '', $aptNumber);
                    $rowLetter = preg_replace('/\d/', '', $aptNumber);

                    // Find the lot based on the provided phase, cluster, column, and row
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

                    if (! $lot) {
                        $errors[] = "Row {$rowNumber}: Lot not found or already occupied (Phase: {$phaseName}, Cluster: {$clusterName}, Apt: {$aptNumber}) Unssagined";
                    }

                    // Create applicant if data exists
                    $applicantId = null;
                    if (! empty($applicantData['first_name']) || ! empty($applicantData['last_name'])) {
                        $applicant = Applicant::create([
                            'first_name' => $applicantData['first_name'],
                            'middle_name' => $applicantData['middle_name'],
                            'last_name' => $applicantData['last_name'],
                            'contact_number' => $applicantData['contact_number'] ?? '',
                            'relationship' => $applicantData['relationship'],
                        ]);
                        $applicantId = $applicant->id;
                    }

                    $birthDate = $deceasedData['date_of_birth'];
                    $deathDate = $deceasedData['date_of_death'];
                    $explicitAge = $deceasedData['age'] ?? null;

                    // Create deceased record
                    $deceased = DeceasedRecord::create([
                        'applicant_id' => $applicantId,
                        'first_name' => $deceasedData['first_name'],
                        'middle_name' => $deceasedData['middle_name'],
                        'last_name' => $deceasedData['last_name'],
                        'address' => $this->normalizer->normalizeAddress($deceasedData['address']),
                        'date_of_birth' => $birthDate,
                        'date_of_death' => $deathDate,
                        'date_of_depository' => $deceasedData['date_of_depository'],
                        'cremation_date' => $deceasedData['cremation_date'],
                        'cremation_place' => $deceasedData['cremation_place'],
                        'age' => $this->normalizer->computeAge($birthDate, $deathDate, $explicitAge),
                        'precinct_num' => $deceasedData['precinct_num'] ?? null,
                        'corpse_disposal' => match ($importType) {
                            'normal' => 'burial',
                            'muslim' => 'muslim',
                            'columbarium' => 'cremation',
                        },
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

                $this->logActivity(
                    'imported',
                    $importLog,
                    "Import failed for {$fileName} — no records imported",
                    null,
                    ['status' => 'failed', 'errors' => count($errors)],
                );

                return back()->with('error', 'No records were imported')->with('importErrors', $errors);
            }

            $message = "Successfully imported {$imported} records";
            if (! empty($errors)) {
                $message .= ' with '.count($errors).' skipped';
            }

            $importLog->update([
                'status' => 'successful',
            ]);

            $this->logActivity(
                'imported',
                $importLog,
                "Imported {$imported} records from {$fileName} ({$importType})",
                null,
                ['status' => 'successful', 'imported' => $imported, 'skipped' => count($errors)],
            );

            return back()->with('success', $message)->with('importErrors', $errors);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            if (isset($importLog)) {
                $this->logActivity(
                    'imported',
                    $importLog,
                    "Import failed for {$fileName}: {$e->getMessage()}",
                );
            }

            return back()->with('error', 'Failed to process file')->with('importErrors', [$e->getMessage()]);
        }
    }

    /**
     * @param  array  $row  spreadsheet row (0-indexed)
     * @return array{deceased: array, applicant: array, lot: array}
     */
    private function parseNormalRow(array $row): array
    {
        $deceasedName = $this->normalizer->parseFullName(trim($row[2] ?? ''));
        $applicantName = $this->normalizer->parseFullName(trim($row[3] ?? ''));

        return [
            'deceased' => [
                'first_name' => $deceasedName['first_name'],
                'middle_name' => $deceasedName['middle_name'],
                'last_name' => $deceasedName['last_name'],
                'address' => $this->normalizer->normalizeAddress($row[7] ?? null),
                'date_of_birth' => null,
                'date_of_death' => null,
                'date_of_depository' => $this->normalizer->parseDate($row[1] ?? null),
                'cremation_date' => null,
                'cremation_place' => null,
            ],
            'applicant' => [
                'first_name' => $applicantName['first_name'],
                'middle_name' => $applicantName['middle_name'],
                'last_name' => $applicantName['last_name'],
                'contact_number' => null,
                'relationship' => null,
            ],
            'lot' => [
                'phase_name' => trim($row[4] ?? ''),
                'cluster_name' => trim($row[5] ?? ''),
                'apt_number' => trim($row[6] ?? ''),
            ],
        ];
    }

    /**
     * Phase is hardcoded to "clbm" for columbarium.
     *
     * @param  array  $row  spreadsheet row (0-indexed)
     * @return array{deceased: array, applicant: array, lot: array}
     */
    private function parseColumbariumRow(array $row): array
    {
        $deceasedName = $this->normalizer->parseFullName(trim($row[2] ?? ''));
        $applicantName = $this->normalizer->parseFullName(trim($row[9] ?? ''));

        $precinctNum = trim($row[1] ?? '');
        $age = trim($row[14] ?? '');

        return [
            'deceased' => [
                'first_name' => $deceasedName['first_name'],
                'middle_name' => $deceasedName['middle_name'],
                'last_name' => $deceasedName['last_name'],
                'address' => $this->normalizer->normalizeAddress($row[3] ?? null),
                'date_of_birth' => $this->normalizer->parseDate($row[4] ?? null),
                'date_of_death' => $this->normalizer->parseDate($row[5] ?? null),
                'date_of_depository' => $this->normalizer->parseDate($row[7] ?? null),
                'cremation_date' => $this->normalizer->parseDate($row[6] ?? null),
                'cremation_place' => $this->normalizer->normalizeAddress($row[8] ?? null),
                'age' => is_numeric($age) ? (int) $age : null,
                'precinct_num' => is_numeric($precinctNum) ? (int) $precinctNum : null,
            ],
            'applicant' => [
                'first_name' => $applicantName['first_name'],
                'middle_name' => $applicantName['middle_name'],
                'last_name' => $applicantName['last_name'],
                'contact_number' => trim($row[11] ?? '') ?: null,
                'relationship' => $this->normalizer->normalizeName($row[10] ?? null),
            ],
            'lot' => [
                'phase_name' => 'clbm',
                'cluster_name' => trim($row[12] ?? ''),
                'apt_number' => trim($row[13] ?? ''),
            ],
        ];
    }

    /**
     * @param  array  $row  spreadsheet row (0-indexed)
     * @return array{deceased: array, applicant: array, lot: array}
     */
    private function parseMuslimRow(array $row): array
    {
        $deceasedName = $this->normalizer->parseFullName(trim($row[2] ?? ''));
        $applicantName = $this->normalizer->parseFullName(trim($row[6] ?? ''));

        return [
            'deceased' => [
                'first_name' => $deceasedName['first_name'],
                'middle_name' => $deceasedName['middle_name'],
                'last_name' => $deceasedName['last_name'],
                'address' => null,
                'date_of_birth' => null,
                'date_of_death' => null,
                'date_of_depository' => null,
                'cremation_date' => null,
                'cremation_place' => null,
            ],
            'applicant' => [
                'first_name' => $applicantName['first_name'],
                'middle_name' => $applicantName['middle_name'],
                'last_name' => $applicantName['last_name'],
                'contact_number' => null,
                'relationship' => null,
            ],
            'lot' => [
                'phase_name' => trim($row[10] ?? ''),
                'cluster_name' => trim($row[11] ?? ''),
                'apt_number' => trim($row[12] ?? ''),
            ],
        ];
    }
}
