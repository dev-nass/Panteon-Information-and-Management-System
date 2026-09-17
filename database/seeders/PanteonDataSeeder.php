<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Cluster;
use App\Models\DeceasedRecord;
use App\Models\Lot;
use App\Models\Phase;
use App\Services\RecordNormalizationService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PanteonDataSeeder extends Seeder
{
    public function __construct(
        protected RecordNormalizationService $normalizer
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedPhases();
        $this->seedClusters();
        $this->seedLots();
        $this->deceasedRecordsBurial();
    }

    private function seedPhases(): void
    {
        $geoJsonPath = public_path('data/phases-w-col.geojson');

        if (! $geoJsonPath) {
            $this->command->error("GeoJSON file for phase not found at path $geoJsonPath");

            return;
        }

        $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

        if (! $geoJsonData['features']) {
            $this->command->error("Invalid GeoJSON format: 'features' key not found.");

            return;
        }

        $this->command->info('Seeding phases from GeoJSON...');

        foreach ($geoJsonData['features'] as $feature) {
            $geometryJson = json_encode($feature['geometry'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            DB::statement('INSERT INTO phases(phase_name, coordinates, created_at, updated_at) VALUES (?, ST_GeomFromGeoJSON(?), NOW(), NOW())', [
                $feature['properties']['phase_name'],
                $geometryJson,
            ]);
        }

        $this->command->info('Total phases imported: '.count($geoJsonData['features']));
    }

    // modified by ai
    // check this again after the lots plotting
    // modify this to suite the plotting made on QGIS
    private function seedClusters(): void
    {
        $clusterFiles = [
            'data/clusters/cluster_phase1a.geojson',
            'data/clusters/cluster_phase1b.geojson',
            'data/clusters/cluster_phase2.geojson',
            'data/clusters/cluster_phase3.geojson',
            'data/clusters/cluster_phase4.geojson',
            'data/clusters/cluster_phase5.geojson',
            'data/clusters/cluster_phase6.geojson',
            'data/clusters/cluster_phase7.geojson',
            'data/clusters/cluster_columbarium.geojson',
        ];

        $this->command->info('Seeding clusters from GeoJSON...');

        $counter = 0;

        foreach ($clusterFiles as $file) {
            $geoJsonPath = public_path($file);

            if (! file_exists($geoJsonPath)) {
                $this->command->warn("File not found: {$file}");

                continue;
            }

            $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

            if (! isset($geoJsonData['features'])) {
                $this->command->warn("Invalid GeoJSON format in {$file}");

                continue;
            }

            foreach ($geoJsonData['features'] as $index => $feature) {
                if (
                    ! isset($feature['geometry'])
                    || ! isset($feature['geometry']['coordinates'])
                    || empty($feature['geometry']['coordinates'])
                ) {
                    $this->command->warn("Skipping cluster in {$file}: empty geometry");

                    continue;
                }

                $attributes = $feature['properties'];
                $geometryJson = json_encode($feature['geometry'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                DB::statement('
                    INSERT INTO clusters (id, phase_id, cluster_name, cluster_type, coordinates, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ST_GeomFromGeoJSON(?), NOW(), NOW())
                ', [
                    $attributes['id'],
                    $attributes['phase_id'],
                    $attributes['name'],
                    $attributes['type'],
                    $geometryJson,
                ]);

                $counter++;
            }
        }

        $this->command->info("Total clusters imported: {$counter}");
    }

    // seed lots
    private function seedLots(): void
    {
        $lotsDirectory = public_path('data/lots');

        if (! is_dir($lotsDirectory)) {
            $this->command->error("Lots directory not found at {$lotsDirectory}");

            return;
        }

        $lotFiles = glob($lotsDirectory.'/*.geojson');

        if (empty($lotFiles)) {
            $this->command->error('No GeoJSON files found in lots directory');

            return;
        }

        $this->command->info('Seeding lots from GeoJSON files...');

        $counter = 0;
        // Initialize all capacity clusters to 0
        $clusterCapacities = Cluster::pluck('id')->mapWithKeys(function ($id) {
            return [$id => 0];
        })->toArray();

        foreach ($lotFiles as $file) {
            $geoJsonData = json_decode(file_get_contents($file), true);

            if (! isset($geoJsonData['features'])) {
                $this->command->warn('Invalid GeoJSON format in '.basename($file));

                continue;
            }

            foreach ($geoJsonData['features'] as $feature) {
                if (
                    ! isset($feature['geometry'])
                    || ! isset($feature['geometry']['coordinates'])
                    || empty($feature['geometry']['coordinates'])
                ) {
                    continue;
                }

                $attributes = $feature['properties'];
                $geometryJson = json_encode($feature['geometry'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $clusterId = $attributes['cluster_id'];

                DB::statement('
                    INSERT INTO lots (`row`, `column`, cluster_id, coordinates, created_at, updated_at)
                    VALUES (?, ?, ?, ST_GeomFromGeoJSON(?), NOW(), NOW())
                ', [
                    strtoupper($attributes['row'] ?? ''),
                    $attributes['id'] ?? null,
                    $clusterId,
                    $geometryJson,
                ]);

                // Increment capacity for this cluster
                $clusterCapacities[$clusterId]++;
                $counter++;
            }
        }

        // Update total_capacity for each cluster
        foreach ($clusterCapacities as $clusterId => $capacity) {
            Cluster::where('id', $clusterId)->update(['total_capacity' => $capacity]);
        }

        $this->command->info("Total lots imported: {$counter}");
        $this->command->info('Updated capacity for '.count($clusterCapacities).' clusters');
    }

    /**
     * Description: Import deceased records from Excel file and assign them to lots
     * Uses chunk processing for better performance
     * Previous issue:
     *  rows are over 100k on xlsx (fixed by deletion)
     *  column are ongoing until A to AA (fixed by the code)
     */
    private function deceasedRecordsBurial(): void
    {
        $this->command->info('Importing deceased records from Excel file...');

        $excelPath = public_path('data/pppanteon-cleaned-data.xlsx');

        if (! file_exists($excelPath)) {
            $this->command->error("Excel file not found at {$excelPath}");

            return;
        }

        try {
            $reader = IOFactory::createReaderForFile($excelPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($excelPath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Use data bounds only (avoid 16k phantom columns from formatting)
            $highestDataRow = $worksheet->getHighestDataRow();
            $highestDataColumn = $worksheet->getHighestDataColumn();
            $rows = $worksheet->rangeToArray("A1:{$highestDataColumn}{$highestDataRow}", null, true, false, false);

            // Remove header row
            array_shift($rows);

            $imported = 0;
            $skipped = 0;
            $chunkSize = 100;
            $chunk = [];

            $this->command->info('Total rows to process: '.count($rows));

            // Preload lots to avoid N+1 query per row (was 21k queries)
            // Normalize phase/cluster/row to upper for case-insensitive matching (Excel has 1a/3n vs DB 1A/3N)
            // Key includes cluster_type to disambiguate same cluster name under apartment vs underground (e.g. 8S).
            // Excel encodes underground as "UG8S", "UG 3N", "UG-3N" etc. -> normalizeClusterName() strips UG prefix and returns type hint.
            $lotsMap = [];
            $lotsMapGeneric = [];
            foreach (Lot::with('cluster.phase')->get() as $lot) {
                $phaseKey = strtoupper($lot->cluster->phase->phase_name);
                $clusterKey = strtoupper($lot->cluster->cluster_name);
                $typeKey = strtolower($lot->cluster->cluster_type ?? '');
                $rowKey = strtoupper($lot->row);
                $colKey = $lot->column;
                $typedKey = $phaseKey.'|'.$clusterKey.'|'.$typeKey.'|'.$rowKey.'|'.$colKey;
                $lotsMap[$typedKey] = $lot;
                // Generic fallback without type (first wins) for clusters without UG hint
                $genericKey = $phaseKey.'|'.$clusterKey.'|'.$rowKey.'|'.$colKey;
                if (! isset($lotsMapGeneric[$genericKey])) {
                    $lotsMapGeneric[$genericKey] = $lot;
                }
            }
            $usedLotIds = [];

            foreach ($rows as $index => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Skip if missing required fields
                if (empty($row[1]) || empty($row[2])) {
                    $skipped++;

                    continue;
                }

                try {
                    // Parse deceased name
                    $fullName = trim($row[2]);
                    $nameParts = $this->normalizer->parseFullName($fullName);
                    $burialDate = $this->normalizer->parseDate($row[1]);

                    // Find lot based on phase, cluster, and apt number
                    $phaseName = trim($row[4] ?? '');
                    $clusterName = trim($row[5] ?? '');
                    $aptNumber = trim($row[6] ?? '');

                    // Fix: Panteon de Dasma underground Phase 1A uses compound rows E1/E2 (e.g. apt "1e1" = col 1 row E1).
                    // Old logic `preg_replace('/\D/', '', '1e1') => 11, row E` incorrectly merged digits.
                    // Use leading-digits + trailing-letters+digits split so 1e1 => col 1 row E1, 10E2 => col 10 row E2.
                    [$column, $rowLetter] = $this->parseAptNumber($aptNumber);
                    // Excel encodes underground type as "UG8S" meaning 8S underground (not apartment). Normalize.
                    [$normalizedCluster, $clusterTypeHint] = $this->normalizeClusterName($clusterName);
                    // Normalize phase: Excel has variants like PH3, Ph1b, PH-2, P1A -> 3, 1B, 2, 1A
                    $normalizedPhase = $this->normalizePhaseName($phaseName);

                    $lot = null;
                    if (! empty($column) && ! empty($rowLetter) && $normalizedCluster !== '') {
                        $lot = $this->findLotByClusterAndApt($lotsMap, $lotsMapGeneric, $usedLotIds, $normalizedPhase, $normalizedCluster, $clusterTypeHint, $rowLetter, $column);
                        // Fallback to raw phase if normalized didn't match (safety for unexpected phase encodings)
                        if (! $lot) {
                            $phaseKeyRaw = strtoupper(trim($phaseName));
                            if ($phaseKeyRaw !== $normalizedPhase) {
                                $lot = $this->findLotByClusterAndApt($lotsMap, $lotsMapGeneric, $usedLotIds, $phaseKeyRaw, $normalizedCluster, $clusterTypeHint, $rowLetter, $column);
                            }
                        }
                        if ($lot) {
                            $usedLotIds[$lot->id] = true;
                        }
                    }

                    // Create applicant if exists - handle single-word names gracefully (avoid last_name null violation)
                    $applicantId = null;
                    $applicantName = trim($row[3] ?? '');
                    if (! empty($applicantName)) {
                        $applicantParts = $this->normalizer->parseFullName($applicantName);
                        // Skip incomplete applicant names (would violate DB NOT NULL) but still import deceased
                        if (empty($applicantParts['first_name']) || empty($applicantParts['last_name'])) {
                            $this->command->warn('Row '.($index + 2).": Applicant '{$applicantName}' is incomplete — both first and last names are required. Importing deceased without applicant.");
                        } else {
                            try {
                                $applicant = Applicant::create([
                                    'first_name' => $applicantParts['first_name'] ?? '',
                                    'middle_name' => $applicantParts['middle_name'],
                                    'last_name' => $applicantParts['last_name'] ?? '',
                                    'contact_number' => '',
                                ]);
                                $applicantId = $applicant->id;
                            } catch (QueryException $qe) {
                                $this->command->warn('Row '.($index + 2).": Applicant '{$applicantName}' could not be saved — check name format. Importing without applicant.");
                            }
                        }
                    }

                    $address = $this->normalizer->normalizeAddress($row[7] ?? null);

                    // Create deceased record
                    $deceased = DeceasedRecord::create([
                        'applicant_id' => $applicantId,
                        'first_name' => $nameParts['first_name'] ?? '',
                        'middle_name' => $nameParts['middle_name'],
                        'last_name' => $nameParts['last_name'] ?? '',
                        'address' => $address,
                        'date_of_birth' => null,
                        'date_of_death' => null,
                        'date_of_depository' => $burialDate,
                        'corpse_disposal' => 'burial',
                    ]);

                    // Add to chunk for bulk insert
                    $chunk[] = [
                        'deceased_record_id' => $deceased->id,
                        'lot_id' => $lot?->id,
                        'user_id' => 1, // System user
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $imported++;

                    // Bulk insert when chunk size is reached
                    if (count($chunk) >= $chunkSize) {
                        DB::table('burial_records')->insert($chunk);
                        $chunk = [];
                        $this->command->info("Imported {$imported} records...");
                    }

                } catch (\Exception $e) {
                    $skipped++;
                    $friendly = $this->friendlyRowError($e, $index + 2);
                    $this->command->warn($friendly);
                }
            }

            // Insert remaining records
            if (! empty($chunk)) {
                DB::table('burial_records')->insert($chunk);
            }

            // Free spreadsheet memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $worksheet, $rows, $lotsMap, $usedLotIds, $chunk);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            $this->command->info('Import completed!');
            $this->command->info("Total records imported: {$imported}");
            $this->command->info("Total records skipped: {$skipped}");

        } catch (\Exception $e) {
            $friendly = $this->friendlyImportError($e);
            $this->command->error($friendly);
        }
    }

    private function friendlyRowError(\Exception $e, int $rowNumber): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, "Column 'last_name'") && str_contains($msg, 'applicants')) {
            return "Row {$rowNumber}: Applicant name is incomplete — missing last name. Use 'First Last' format. Saved without applicant.";
        }

        if (str_contains($msg, 'SQLSTATE') || str_contains($msg, 'Integrity constraint') || str_contains($msg, 'SQL:')) {
            if (preg_match("/Column '([^']+)' cannot be null/", $msg, $m)) {
                $col = str_replace('_', ' ', $m[1]);

                return "Row {$rowNumber}: Missing required field '{$col}'. Check the row data.";
            }

            return "Row {$rowNumber}: Could not save — check the row for missing or incorrect fields.";
        }

        $clean = preg_replace('/\s*\(Connection:.*$/s', '', $msg);
        $clean = trim($clean);

        return $clean !== '' ? "Row {$rowNumber}: {$clean}" : "Row {$rowNumber}: Could not save — check the row data.";
    }

    private function friendlyImportError(\Exception $e): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, 'SQLSTATE') || str_contains($msg, 'Integrity constraint')) {
            return 'Failed to import: invalid data. Check that required columns are filled and try again.';
        }

        $clean = preg_replace('/\s*\(Connection:.*$/s', '', $msg);

        return trim($clean) !== '' ? trim($clean) : 'Failed to import deceased records.';
    }

    /**
     * Parse APT. number into column and row.
     * Handles Phase 1A underground compound rows E1/E2.
     * e.g. "1e1" => ["1","E1"], "10E2" => ["10","E2"], "5E" => ["5","E"], "12D" => ["12","D"]
     *
     * @return array{0: string, 1: string} [column, row]
     */
    private function parseAptNumber(string $aptNumber): array
    {
        $aptNumber = trim($aptNumber);

        if ($aptNumber === '') {
            return ['', ''];
        }

        // Primary: leading digits + letters (+ optional trailing digits) — correct for 1e1, 10E2, etc.
        if (preg_match('/^(\d+)([A-Za-z]+\d*)$/', $aptNumber, $matches)) {
            return [$matches[1], strtoupper($matches[2])];
        }

        // Fallback for unexpected formats — keep old behaviour
        $column = preg_replace('/\D/', '', $aptNumber);
        $rowLetter = strtoupper(preg_replace('/\d/', '', $aptNumber));

        return [$column, $rowLetter];
    }

    /**
     * Normalize Excel cluster encoding. Underground clusters are prefixed with UG (e.g. UG8S, UG 3N, UG-3N, ug8s)
     * and should map to cluster_name without prefix with type hint underground. Plain "8S" maps to apartment.
     *
     * @return array{0: string, 1: string|null} [normalizedClusterName, clusterTypeHint]
     */
    private function normalizeClusterName(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['', null];
        }

        // Detect UG prefix (case-insensitive, optional space/hyphen/underscore after UG) + common typo ULT for UG
        if (preg_match('/^ULT\s*[-_]?\s*(.+)$/i', $raw, $m)) {
            $name = trim($m[1]);
            $name = preg_replace('/[\s\-_]+/', '', $name);
            $name = strtoupper($name);
            $name = preg_replace('/^I(?=[0-9NS])/', '1', $name);
            $name = preg_replace('/^LT/', '1', $name);
            $name = preg_replace('/^0+(\d)/', '$1', $name);

            return [$name, 'underground'];
        }
        if (preg_match('/^UG\s*[-_]?\s*(.+)$/i', $raw, $m)) {
            $name = trim($m[1]);
            // Remove any remaining spaces/hyphens/underscores inside name (e.g. "1-N" -> "1N")
            $name = preg_replace('/[\s\-_]+/', '', $name);
            $name = strtoupper($name);
            // Fix common OCR typo: I -> 1 (e.g. UGI-N -> UG1-N, UGIN -> 1N)
            $name = preg_replace('/^I(?=[0-9NS])/', '1', $name);
            // Also handle "ULT" typo for UG (rare)
            $name = preg_replace('/^LT/', '1', $name);
            // Strip leading zeros: "03S" -> "3S"
            $name = preg_replace('/^0+(\d)/', '$1', $name);

            return [$name, 'underground'];
        }

        $name = strtoupper($raw);
        $name = preg_replace('/[\s\-_]+/', '', $name);
        $name = preg_replace('/^0+(\d)/', '$1', $name);

        return [$name, null];
    }

    /**
     * Normalize phase encoding from Excel. Handles variants like PH3, Ph1b, PH-2, P1A, 1-A, H3.
     */
    private function normalizePhaseName(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $n = strtoupper($raw);
        // Remove spaces, hyphens, dots, underscores
        $n = preg_replace('/[\s\-_\.]+/', '', $n);
        // Strip leading PH / P / H prefixes (e.g. PH1B -> 1B, P1A -> 1A, H3 -> 3)
        $n = preg_replace('/^(PH|P|H)+/', '', $n);
        // Handle "PPH" etc already stripped, keep result as is

        return $n;
    }

    /**
     * Find lot using typed map; respects UG hint and falls back gracefully.
     */
    private function findLotByClusterAndApt(array $lotsMap, array $lotsMapGeneric, array $usedLotIds, string $phaseKey, string $clusterName, ?string $typeHint, string $rowLetter, string $column): ?Lot
    {
        // Handle phase "1" ambiguity: try both 1A and 1B (covers Excel "Ph1" without suffix)
        $phaseCandidates = [$phaseKey];
        if ($phaseKey === '1') {
            $phaseCandidates = ['1A', '1B'];
        }

        foreach ($phaseCandidates as $pk) {
            // 1) If we have an explicit UG hint, try underground first
            if ($typeHint !== null) {
                $key = $pk.'|'.$clusterName.'|'.$typeHint.'|'.$rowLetter.'|'.$column;
                $candidate = $lotsMap[$key] ?? null;
                if ($candidate && ! isset($usedLotIds[$candidate->id])) {
                    return $candidate;
                }
                // Fallback to generic (e.g. columbarium or single-type phase) if typed not found
                $genericKey = $pk.'|'.$clusterName.'|'.$rowLetter.'|'.$column;
                $candidate = $lotsMapGeneric[$genericKey] ?? null;
                if ($candidate && ! isset($usedLotIds[$candidate->id])) {
                    return $candidate;
                }

                continue;
            }

            // 2) No hint (plain "8S"): prefer apartment for phases that have both types, then underground, then generic
            foreach (['apartment', 'underground'] as $type) {
                $key = $pk.'|'.$clusterName.'|'.$type.'|'.$rowLetter.'|'.$column;
                $candidate = $lotsMap[$key] ?? null;
                if ($candidate && ! isset($usedLotIds[$candidate->id])) {
                    return $candidate;
                }
            }

            // 3) Generic fallback (e.g. phase 1A which only has underground for some clusters, or columbarium)
            $genericKey = $pk.'|'.$clusterName.'|'.$rowLetter.'|'.$column;
            $candidate = $lotsMapGeneric[$genericKey] ?? null;
            if ($candidate && ! isset($usedLotIds[$candidate->id])) {
                return $candidate;
            }
        }

        return null;
    }
}
