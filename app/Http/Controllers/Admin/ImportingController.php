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
use Illuminate\Database\QueryException;
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
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();

            // Use data bounds only (avoid 16k phantom columns/1M rows from formatting)
            $highestDataRow = $worksheet->getHighestDataRow();
            $highestDataColumn = $worksheet->getHighestDataColumn();
            $rows = $worksheet->rangeToArray("A1:{$highestDataColumn}{$highestDataRow}", null, true, false, false);

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

            // Preload lots to avoid N+1 query per row (was 21k queries for large imports)
            $lotsMap = Lot::with('cluster.phase')->get()->keyBy(function (Lot $lot) {
                return $lot->cluster->phase->phase_name.'|'.$lot->cluster->cluster_name.'|'.$lot->row.'|'.$lot->column;
            });
            $usedLotIds = Lot::whereHas('burialRecords')->pluck('id')->flip()->toArray();
            // Merge already occupied + in-batch used
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
                        $deceasedData['date_of_death'],
                        $deceasedData['date_of_depository']
                    );

                    if ($existingRecord) {
                        $errors[] = "Row {$rowNumber}: Deceased record already exists (ID: {$existingRecord->id})";

                        continue;
                    }

                    // Find lot via preloaded map (avoids N+1 per row)
                    $phaseName = $lotData['phase_name'];
                    $clusterName = $lotData['cluster_name'];
                    $aptNumber = $lotData['apt_number']; // e.g. 12A or 2B

                    $column = preg_replace('/\D/', '', $aptNumber);
                    $rowLetter = strtoupper(preg_replace('/\d/', '', $aptNumber));

                    $lot = null;
                    if (! empty($column) && ! empty($rowLetter)) {
                        $key = $phaseName.'|'.$clusterName.'|'.$rowLetter.'|'.$column;
                        $candidate = $lotsMap[$key] ?? null;
                        if ($candidate && ! isset($usedLotIds[$candidate->id])) {
                            $lot = $candidate;
                            $usedLotIds[$lot->id] = true;
                        }
                    }

                    if (! $lot) {
                        $lotLabel = $aptNumber !== '' ? $aptNumber : '—';
                        $phaseLabel = $phaseName !== '' ? $phaseName : '—';
                        $clusterLabel = $clusterName !== '' ? $clusterName : '—';
                        $errors[] = "Row {$rowNumber}: Lot '{$lotLabel}' (Phase {$phaseLabel}, Cluster {$clusterLabel}) was not found or is already occupied — burial saved without a lot. Please assign a lot later.";
                    }

                    // Create applicant if data exists - handle single-word names gracefully
                    $applicantId = null;
                    $hasApplicant = ! empty($applicantData['first_name']) || ! empty($applicantData['last_name']);
                    if ($hasApplicant) {
                        // Validate applicant has both first and last name to satisfy DB constraints
                        if (empty($applicantData['first_name']) || empty($applicantData['last_name'])) {
                            $rawApplicant = match ($importType) {
                                'normal' => trim($row[3] ?? ''),
                                'muslim' => trim($row[6] ?? ''),
                                'columbarium' => trim($row[9] ?? ''),
                                default => trim($applicantData['first_name'] ?? '').' '.trim($applicantData['last_name'] ?? ''),
                            };
                            $rawApplicant = $rawApplicant !== '' ? $rawApplicant : trim(($applicantData['first_name'] ?? '').' '.($applicantData['last_name'] ?? ''));
                            $errors[] = "Row {$rowNumber}: Applicant name '{$rawApplicant}' is incomplete — both first and last names are required. Use 'First Last' format (e.g., 'Juan Dela Cruz'). Applicant not linked, but the deceased record will still be imported.";
                            \Log::warning("Import applicant incomplete on row {$rowNumber}", ['raw' => $rawApplicant, 'parsed' => $applicantData]);
                        } else {
                            try {
                                $applicant = Applicant::create([
                                    'first_name' => $applicantData['first_name'],
                                    'middle_name' => $applicantData['middle_name'],
                                    'last_name' => $applicantData['last_name'],
                                    'contact_number' => $applicantData['contact_number'] ?? '',
                                    'relationship' => $applicantData['relationship'],
                                ]);
                                $applicantId = $applicant->id;
                            } catch (QueryException $qe) {
                                \Log::warning("Import applicant DB error on row {$rowNumber}", ['error' => $qe->getMessage(), 'data' => $applicantData]);
                                $errors[] = "Row {$rowNumber}: Applicant could not be saved — please check the name and contact details. The deceased record will still be imported without an applicant.";
                            }
                        }
                    }

                    $birthDate = $deceasedData['date_of_birth'];
                    $deathDate = $deceasedData['date_of_death'];

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
                    $errors[] = $this->friendlyRowError($e, $rowNumber, $row);
                    \Log::error("Import error on row {$rowNumber}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'row_data' => $row,
                    ]);
                }
            }

            DB::commit();

            // Free spreadsheet memory (avoid 6GB hold for 16k phantom cols)
            if (isset($spreadsheet)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $worksheet, $rows, $lotsMap, $usedLotIds);
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

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
            if (isset($spreadsheet)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $worksheet, $rows, $lotsMap, $usedLotIds);
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
            \Log::error('Import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            $friendly = $this->friendlyImportError($e);

            if (isset($importLog)) {
                $this->logActivity(
                    'imported',
                    $importLog,
                    "Import failed for {$fileName}: {$e->getMessage()}",
                );
            }

            return back()->with('error', $friendly)->with('importErrors', [$friendly]);
        }
    }

    /**
     * Convert a technical exception into a friendly, non-technical message for end users.
     */
    private function friendlyRowError(\Exception $e, int $rowNumber, array $row): string
    {
        $msg = $e->getMessage();

        // Hide SQLSTATE / Integrity constraint details from users, log keeps full trace
        if (str_contains($msg, "Column 'last_name'") && str_contains($msg, 'applicants')) {
            $raw = trim($row[3] ?? $row[6] ?? $row[9] ?? '');
            $raw = $raw !== '' ? $raw : 'applicant';

            return "Row {$rowNumber}: Applicant name '{$raw}' is incomplete — missing last name. Please use 'First Last' format. The deceased record was saved without an applicant.";
        }

        if (str_contains($msg, "Column 'first_name'") && str_contains($msg, 'applicants')) {
            return "Row {$rowNumber}: Applicant name is missing a first name. Please check the Applicant column.";
        }

        if (str_contains($msg, "Column 'contact_number'") && str_contains($msg, 'applicants')) {
            return "Row {$rowNumber}: Applicant contact number is missing. Please provide a contact number or leave the applicant blank.";
        }

        if (str_contains($msg, 'SQLSTATE') || str_contains($msg, 'Integrity constraint') || str_contains($msg, 'SQL:')) {
            if (preg_match("/Column '([^']+)' cannot be null/", $msg, $m)) {
                $col = str_replace('_', ' ', $m[1]);

                return "Row {$rowNumber}: Missing required field '{$col}'. Please check the row and fill in the {$col}.";
            }

            if (str_contains($msg, 'Duplicate entry')) {
                return "Row {$rowNumber}: This record already exists and was skipped.";
            }

            return "Row {$rowNumber}: Could not save — please check the row data for missing or incorrect fields.";
        }

        // Strip any remaining SQL traces
        $clean = preg_replace('/\s*\(Connection:.*$/s', '', $msg);
        $clean = preg_replace('/SQL:.*$/s', '', $clean);
        $clean = trim($clean);

        if ($clean === '' || $clean === $msg && str_contains($clean, 'SQLSTATE')) {
            return "Row {$rowNumber}: Could not save — please check the row data.";
        }

        // Ensure row prefix
        if (! str_starts_with($clean, "Row {$rowNumber}")) {
            return "Row {$rowNumber}: {$clean}";
        }

        return $clean;
    }

    /**
     * Friendly message for whole-file failures (no row context).
     */
    private function friendlyImportError(\Exception $e): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, 'SQLSTATE') || str_contains($msg, 'Integrity constraint')) {
            return 'The file could not be processed due to invalid data. Please check the file format and that all required columns are filled.';
        }

        $clean = preg_replace('/\s*\(Connection:.*$/s', '', $msg);
        $clean = trim($clean);

        return $clean !== '' ? $clean : 'The file could not be processed. Please check the file format.';
    }

    /**
     * @param  array  $row  spreadsheet row (0-indexed)
     * @return array{deceased: array, applicant: array, lot: array}
     */
    private function parseNormalRow(array $row): array
    {
        $deceasedName = $this->normalizer->parseFullName(trim($row[2] ?? ''));
        $applicantName = $this->normalizer->parseFullName(trim($row[3] ?? ''));

        // Robust address handling: try primary column 7, then fallback to 3,8,9 if empty (handles variant templates)
        $rawAddress = $row[7] ?? null;
        if (empty(trim((string) $rawAddress)) && !empty(trim((string) ($row[3] ?? ''))) && trim((string) ($row[3] ?? '')) !== trim((string) ($row[2] ?? ''))) {
            // Some templates put barangay at 3 if applicant column is shifted
            $maybe = trim((string) ($row[8] ?? $row[9] ?? ''));
            if (!empty($maybe)) $rawAddress = $maybe;
        }
        if (empty(trim((string) $rawAddress))) {
            // Try next columns as fallback (covers 8-col vs 9-col variants)
            foreach ([8, 9, 10, 3] as $idx) {
                if (!empty(trim((string) ($row[$idx] ?? '')))) {
                    $rawAddress = $row[$idx];
                    break;
                }
            }
        }

        return [
            'deceased' => [
                'first_name' => $deceasedName['first_name'],
                'middle_name' => $deceasedName['middle_name'],
                'last_name' => $deceasedName['last_name'],
                'address' => $this->normalizer->normalizeAddress($rawAddress),
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

        // Robust address: primary 3, fallback to 7,8 if empty (covers template variations)
        $rawAddress = $row[3] ?? null;
        if (empty(trim((string) $rawAddress))) {
            foreach ([7, 8, 10] as $idx) {
                if (!empty(trim((string) ($row[$idx] ?? '')))) {
                    // Avoid picking date columns - check if it looks like an address (not a date)
                    $val = trim((string) $row[$idx]);
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $val) && !is_numeric($val)) {
                        $rawAddress = $val;
                        break;
                    }
                }
            }
        }

        return [
            'deceased' => [
                'first_name' => $deceasedName['first_name'],
                'middle_name' => $deceasedName['middle_name'],
                'last_name' => $deceasedName['last_name'],
                'address' => $this->normalizer->normalizeAddress($rawAddress),
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

        // Try to capture address if present (some muslim templates include it at 3,7,8)
        $rawAddress = null;
        foreach ([3, 7, 8, 9] as $idx) {
            if (!empty(trim((string) ($row[$idx] ?? '')))) {
                $val = trim((string) $row[$idx]);
                // Avoid name/deceased duplicate and lot fields
                if ($val !== trim((string) ($row[2] ?? '')) && $val !== trim((string) ($row[6] ?? ''))) {
                    $rawAddress = $val;
                    break;
                }
            }
        }

        return [
            'deceased' => [
                'first_name' => $deceasedName['first_name'],
                'middle_name' => $deceasedName['middle_name'],
                'last_name' => $deceasedName['last_name'],
                'address' => $this->normalizer->normalizeAddress($rawAddress),
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
