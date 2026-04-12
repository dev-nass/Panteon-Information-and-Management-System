<?php

namespace Database\Seeders;

use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use App\Models\Phase;
use App\Models\Lot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use function GuzzleHttp\json_encode;

class PanteonDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedPhases();
        $this->seedClusters();
        $this->seedLots();
        $this->deceasedRecords();
    }

    private function seedPhases(): void
    {
        $geoJsonPath = public_path('data/phases.geojson');

        if (!$geoJsonPath) {
            $this->command->error("GeoJSON file for phase not found at path $geoJsonPath");
            return;
        }

        $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

        if (!$geoJsonData['features']) {
            $this->command->error("Invalid GeoJSON format: 'features' key not found.");
            return;
        }

        $this->command->info("Seeding phases from GeoJSON...");

        foreach ($geoJsonData['features'] as $feature) {
            // $phase_attributes = $feature['properties'];

            $geometryJson = json_encode($feature['geometry'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            DB::statement("INSERT INTO phases(phase_name, coordinates, created_at, updated_at) VALUES (?, ST_GeomFromGeoJSON(?), NOW(), NOW())", [
                $feature['properties']['phase_name'],
                $geometryJson,
            ]);
        }

        $this->command->info("Total phases imported: " . count($geoJsonData['features']));
    }

    // modified by ai
    // check this again after the lots plotting
    // modify this to suite the plotting made on QGIS
    private function seedClusters(): void
    {
        $clusterFiles = [
            'data/cluster_phase1a.geojson',
            'data/cluster_phase1b.geojson',
            'data/cluster_phase2.geojson',
            'data/cluster_phase3.geojson',
            'data/cluster_phase4.geojson',
            'data/cluster_phase5.geojson',
            'data/cluster_phase6.geojson',
            'data/cluster_phase7.geojson',
        ];

        $this->command->info("Seeding clusters from GeoJSON...");

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

                DB::statement("
                    INSERT INTO clusters (id, phase_id, cluster_name, cluster_type, coordinates, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ST_GeomFromGeoJSON(?), NOW(), NOW())
                ", [
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


    private function seedLots(): void
    {
        $lotsDirectory = public_path('data/lots');

        if (!is_dir($lotsDirectory)) {
            $this->command->error("Lots directory not found at {$lotsDirectory}");
            return;
        }

        $lotFiles = glob($lotsDirectory . '/*.geojson');

        if (empty($lotFiles)) {
            $this->command->error("No GeoJSON files found in lots directory");
            return;
        }

        $this->command->info("Seeding lots from GeoJSON files...");

        $counter = 0;

        foreach ($lotFiles as $file) {
            $geoJsonData = json_decode(file_get_contents($file), true);

            if (!isset($geoJsonData['features'])) {
                $this->command->warn("Invalid GeoJSON format in " . basename($file));
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

                DB::statement("
                    INSERT INTO lots (`row`, `column`, cluster_id, coordinates, created_at, updated_at)
                    VALUES (?, ?, ?, ST_GeomFromGeoJSON(?), NOW(), NOW())
                ", [
                    $attributes['row'] ?? null,
                    $attributes['id'] ?? null,
                    $attributes['cluster_id'],
                    $geometryJson,
                ]);

                $counter++;
            }
        }

        $this->command->info("Total lots imported: {$counter}");
    }

    /**
     * Description: Assigns one deceased record per available lot
     * Optimized version using bulk inserts
     */
    private function deceasedRecords(): void
    {
        $this->command->info("Assigning deceased records to lots...");

        $totalDeceased = DeceasedRecord::count();

        if ($totalDeceased === 0) {
            $this->command->warn("No deceased records found. Please seed deceased records first.");
            return;
        }

        // Get all available lots (not occupied)
        $availableLots = Lot::whereDoesntHave('burialRecords')
            ->orderBy('cluster_id')
            ->orderBy('row')
            ->orderBy('column')
            ->pluck('id')
            ->toArray();

        if (empty($availableLots)) {
            $this->command->warn("No available lots found.");
            return;
        }

        $this->command->info("Available lots: " . count($availableLots));
        $this->command->info("Total deceased records: {$totalDeceased}");

        $assignedCount = 0;
        $lotIndex = 0;
        $burialRecordsToInsert = [];

        // Get deceased records that don't have burial records yet
        $unassignedDeceased = DeceasedRecord::whereDoesntHave('burialRecords')
            ->orderBy('id')
            ->get();

        foreach ($unassignedDeceased as $deceased) {
            if ($lotIndex >= count($availableLots)) {
                $this->command->warn("No more available lots. Stopping assignment.");
                break;
            }

            $burialRecordsToInsert[] = [
                'deceased_record_id' => $deceased->id,
                'lot_id' => $availableLots[$lotIndex],
                'user_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $lotIndex++;
            $assignedCount++;

            // Bulk insert every 100 records for better performance
            if (count($burialRecordsToInsert) >= 100) {
                DB::table('burial_records')->insert($burialRecordsToInsert);
                $burialRecordsToInsert = [];
                $this->command->info("Assigned {$assignedCount} deceased records...");
            }
        }

        // Insert remaining records
        if (!empty($burialRecordsToInsert)) {
            DB::table('burial_records')->insert($burialRecordsToInsert);
        }

        $this->command->info("Total deceased records assigned: {$assignedCount}");
    }
}
