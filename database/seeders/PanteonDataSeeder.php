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

        // Guard clause: preload existing phase names for idempotency
        $existingPhases = DB::table('phases')->pluck('phase_name')->flip();
        $imported = 0;
        $skipped = 0;

        foreach ($geoJsonData['features'] as $feature) {
            $phaseName = $feature['properties']['phase_name'];

            if (isset($existingPhases[$phaseName])) {
                $skipped++;

                continue;
            }

            $geometryJson = json_encode($feature['geometry'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            DB::statement('INSERT INTO phases(phase_name, coordinates, created_at, updated_at) VALUES (?, ST_GeomFromGeoJSON(?), NOW(), NOW())', [
                $phaseName,
                $geometryJson,
            ]);

            $existingPhases[$phaseName] = true;
            $imported++;
        }

        $this->command->info("Total phases imported: {$imported}".($skipped > 0 ? " ({$skipped} skipped - already exists)" : ''));
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

        // Guard clause: preload existing clusters for idempotency (by PK and unique composite)
        $existingClusterIds = DB::table('clusters')->pluck('id')->flip();
        $existingClusterKeys = DB::table('clusters')
            ->select('phase_id', 'cluster_name', 'cluster_type')
            ->get()
            ->mapWithKeys(fn ($c) => ["{$c->phase_id}|{$c->cluster_name}|{$c->cluster_type}" => true])
            ->toArray();

        $counter = 0;
        $skipped = 0;

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
                $clusterId = $attributes['id'];
                $compositeKey = "{$attributes['phase_id']}|{$attributes['name']}|{$attributes['type']}";

                // Guard clause: skip if this cluster already exists (by PK or unique phase+name+type)
                if (isset($existingClusterIds[$clusterId]) || isset($existingClusterKeys[$compositeKey])) {
                    $skipped++;

                    continue;
                }

                $geometryJson = json_encode($feature['geometry'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                try {
                    DB::statement('
                        INSERT INTO clusters (id, phase_id, cluster_name, cluster_type, coordinates, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ST_GeomFromGeoJSON(?), NOW(), NOW())
                    ', [
                        $clusterId,
                        $attributes['phase_id'],
                        $attributes['name'],
                        $attributes['type'],
                        $geometryJson,
                    ]);
                } catch (QueryException $e) {
                    // Race / duplicate guard: MySQL error 1062
                    if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                        $skipped++;

                        continue;
                    }
                    throw $e;
                }

                $existingClusterIds[$clusterId] = true;
                $existingClusterKeys[$compositeKey] = true;
                $counter++;
            }
        }

        $this->command->info("Total clusters imported: {$counter}".($skipped > 0 ? " ({$skipped} skipped - already exists)" : ''));
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
        $skipped = 0;

        // Guard clause: preload existing lots for idempotency (cluster_id + row + column)
        $existingLotKeys = DB::table('lots')
            ->select('cluster_id', 'row', 'column')
            ->get()
            ->mapWithKeys(fn ($lot) => ["{$lot->cluster_id}|{$lot->row}|{$lot->column}" => true])
            ->toArray();

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
                $row = strtoupper($attributes['row'] ?? '');
                $column = $attributes['id'] ?? null;
                $lotKey = "{$clusterId}|{$row}|{$column}";

                // Guard clause: skip if this lot already exists
                if (isset($existingLotKeys[$lotKey])) {
                    $skipped++;

                    continue;
                }

                try {
                    DB::statement('
                        INSERT INTO lots (`row`, `column`, cluster_id, coordinates, created_at, updated_at)
                        VALUES (?, ?, ?, ST_GeomFromGeoJSON(?), NOW(), NOW())
                    ', [
                        $row,
                        $column,
                        $clusterId,
                        $geometryJson,
                    ]);
                } catch (QueryException $e) {
                    if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                        $skipped++;

                        continue;
                    }
                    throw $e;
                }

                $existingLotKeys[$lotKey] = true;
                $counter++;
            }
        }

        // Update total_capacity for each cluster based on actual lot count (idempotent)
        $allClusterIds = Cluster::pluck('id');
        foreach ($allClusterIds as $clusterId) {
            $actualCount = DB::table('lots')->where('cluster_id', $clusterId)->count();
            Cluster::where('id', $clusterId)->update(['total_capacity' => $actualCount]);
        }

        $this->command->info("Total lots imported: {$counter}".($skipped > 0 ? " ({$skipped} skipped - already exists)" : ''));
        $this->command->info('Updated capacity for '.count($allClusterIds).' clusters');
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

            // Preload lots via shared service (typed + generic maps to handle UG disambiguation)
            [$lotsMap, $lotsMapGeneric] = $this->normalizer->buildLotMaps(Lot::with('cluster.phase')->get());
            $usedLotIds = [];
            // In-file duplicate tracking: same deceased appears twice in Excel (first+last+burial date)
            $seenDeceasedKeys = [];
            $duplicateCount = 0;

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

                    // Duplicate handler: Excel contains ~117 rows where same first+last+burial date appears twice.
                    // Use shared service to keep seeder thin and avoid duplication with ImportingController.
                    $dedupKey = $this->normalizer->buildDedupKey($nameParts['first_name'] ?? '', $nameParts['last_name'] ?? '', null, null, $burialDate);
                    $fileDuplicateRow = $this->normalizer->findFileDuplicate($seenDeceasedKeys, $nameParts['first_name'] ?? '', $nameParts['last_name'] ?? '', null, null, $burialDate);
                    if ($fileDuplicateRow !== null) {
                        $skipped++;
                        $duplicateCount++;
                        $this->command->warn('Row '.($index + 2).": Duplicate deceased '{$fullName}' on ".($burialDate ?? 'N/A')." already seen at row {$fileDuplicateRow} — skipping duplicate entry.");

                        continue;
                    }
                    $seenDeceasedKeys[$dedupKey] = $index + 2;

                    $existing = $this->normalizer->findDuplicateDeceased(
                        $nameParts['first_name'] ?? '',
                        $nameParts['last_name'] ?? '',
                        null,
                        null,
                        $burialDate
                    );
                    if ($existing) {
                        $skipped++;
                        $duplicateCount++;
                        $this->command->warn('Row '.($index + 2).": Duplicate deceased '{$fullName}' on ".($burialDate ?? 'N/A')." already exists in DB (ID: {$existing->id}) — skipping.");

                        continue;
                    }

                    // Find lot based on phase, cluster, and apt number (delegated to shared service)
                    $phaseName = trim($row[4] ?? '');
                    $clusterName = trim($row[5] ?? '');
                    $aptNumber = trim($row[6] ?? '');

                    [$column, $rowLetter] = $this->normalizer->parseAptNumber($aptNumber);
                    [$normalizedCluster, $clusterTypeHint] = $this->normalizer->normalizeClusterName($clusterName);
                    $normalizedPhase = $this->normalizer->normalizePhaseName($phaseName);

                    $lot = null;
                    if (! empty($column) && ! empty($rowLetter) && $normalizedCluster !== '') {
                        $lot = $this->normalizer->findLotByClusterAndApt($lotsMap, $lotsMapGeneric, $usedLotIds, $normalizedPhase, $normalizedCluster, $clusterTypeHint, $rowLetter, $column);
                        if (! $lot) {
                            $phaseKeyRaw = strtoupper(trim($phaseName));
                            if ($phaseKeyRaw !== $normalizedPhase) {
                                $lot = $this->normalizer->findLotByClusterAndApt($lotsMap, $lotsMapGeneric, $usedLotIds, $phaseKeyRaw, $normalizedCluster, $clusterTypeHint, $rowLetter, $column);
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
            if ($duplicateCount > 0) {
                $this->command->info("Duplicate entries skipped: {$duplicateCount}");
            }

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
}
