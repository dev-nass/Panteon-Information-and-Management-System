<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Cluster;
use App\Models\DeceasedRecord;
use App\Models\Lot;
use App\Models\Phase;
use App\Services\RecordNormalizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PanteonDataSeeder extends Seeder
{
    public function __construct(
        protected RecordNormalizationService $normalizer
    ) {
    }

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

        if (!$geoJsonPath) {
            $this->command->error("GeoJSON file for phase not found at path $geoJsonPath");

            return;
        }

        $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

        if (!$geoJsonData['features']) {
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

        $this->command->info('Total phases imported: ' . count($geoJsonData['features']));
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

            if (!file_exists($geoJsonPath)) {
                $this->command->warn("File not found: {$file}");

                continue;
            }

            $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

            if (!isset($geoJsonData['features'])) {
                $this->command->warn("Invalid GeoJSON format in {$file}");

                continue;
            }

            foreach ($geoJsonData['features'] as $index => $feature) {
                if (
                    !isset($feature['geometry'])
                    || !isset($feature['geometry']['coordinates'])
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

        if (!is_dir($lotsDirectory)) {
            $this->command->error("Lots directory not found at {$lotsDirectory}");

            return;
        }

        $lotFiles = glob($lotsDirectory . '/*.geojson');

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

            if (!isset($geoJsonData['features'])) {
                $this->command->warn('Invalid GeoJSON format in ' . basename($file));

                continue;
            }

            foreach ($geoJsonData['features'] as $feature) {
                if (
                    !isset($feature['geometry'])
                    || !isset($feature['geometry']['coordinates'])
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
        $this->command->info('Updated capacity for ' . count($clusterCapacities) . ' clusters');
    }

    /**
     * Description: Import deceased records from Excel file and assign them to lots
     * Uses chunk processing for better performance
     * Previous issue: 
     *  rows are over 100k on xlsx (fixed by deletion)
     *  column are ongoing until A to AA (fixed by the code)
     * 
     */
    private function deceasedRecordsBurial(): void
    {
        $this->command->info('Importing deceased records from Excel file...');

        $excelPath = public_path('data/pppanteon-cleaned-data.xlsx');

        if (!file_exists($excelPath)) {
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

            $this->command->info('Total rows to process: ' . count($rows));

            // Preload lots to avoid N+1 query per row (was 21k queries)
            $lotsMap = Lot::with('cluster.phase')->get()->keyBy(function (Lot $lot) {
                return $lot->cluster->phase->phase_name . '|' . $lot->cluster->cluster_name . '|' . $lot->row . '|' . $lot->column;
            });
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

                    $column = preg_replace('/\D/', '', $aptNumber);
                    $rowLetter = strtoupper(preg_replace('/\d/', '', $aptNumber));

                    $lot = null;
                    if (!empty($column) && !empty($rowLetter)) {
                        $key = $phaseName . '|' . $clusterName . '|' . $rowLetter . '|' . $column;
                        $candidate = $lotsMap[$key] ?? null;
                        if ($candidate && !isset($usedLotIds[$candidate->id])) {
                            $lot = $candidate;
                            $usedLotIds[$lot->id] = true;
                        }
                    }

                    // Create applicant if exists
                    $applicantId = null;
                    $applicantName = trim($row[3] ?? '');
                    if (!empty($applicantName)) {
                        $applicantParts = $this->normalizer->parseFullName($applicantName);
                        $applicant = Applicant::create([
                            'first_name' => $applicantParts['first_name'] ?? '',
                            'middle_name' => $applicantParts['middle_name'],
                            'last_name' => $applicantParts['last_name'] ?? '',
                            'contact_number' => '',
                        ]);
                        $applicantId = $applicant->id;
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
                    $this->command->warn('Row ' . ($index + 2) . ": {$e->getMessage()}");
                }
            }

            // Insert remaining records
            if (!empty($chunk)) {
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
            $this->command->error("Failed to import deceased records: {$e->getMessage()}");
        }
    }
}
