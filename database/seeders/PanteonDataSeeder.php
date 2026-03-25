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

            // holds the coordinates from QGIS
            $phase_coords = [
                'coordinates' => json_encode($feature['geometry']),
            ];

            // insert the other attributes using the factory definition & phase variable data
            Phase::factory()->create($phase_coords);
        }

        $this->command->info("Phases imported: " . count($geoJsonData['features']));
    }

    private function seedLots(): void
    {
        $geoJsonPath_Underground = public_path('data/lots_underground.geojson');
        $geoJsonPath_Appartment = public_path('data/lots_appartment.geojson');

        if (!file_exists($geoJsonPath_Underground) || !file_exists($geoJsonPath_Appartment)) {
            $this->command->error("GeoJSON files not found");
            return;
        }

        $geoJsonData_underground = json_decode(file_get_contents($geoJsonPath_Underground), true);
        $geoJsonData_appartment = json_decode(file_get_contents($geoJsonPath_Appartment), true);

        $allFeatures = [];

        foreach ($geoJsonData_underground['features'] as $feature) {
            $feature['properties']['lot_type'] = 'underground';
            $feature['properties']['total_capacity'] = 1;
            $allFeatures[] = $feature;
        }

        foreach ($geoJsonData_appartment['features'] as $feature) {
            $feature['properties']['lot_type'] = 'apartment';
            $feature['properties']['total_capacity'] = 371;
            $allFeatures[] = $feature;
        }

        $this->command->info("Seeding lots from GeoJSON...");

        $counter = 0;

        foreach ($allFeatures as $index => $feature) {
            if (
                !isset($feature['geometry'])
                || !isset($feature['geometry']['coordinates'])
                || empty($feature['geometry']['coordinates'])
            ) {
                $this->command->warn("Skipping LOT-{$index}: empty geometry");
                continue;
            }

            $attributes = $feature['properties'];
            $geometryJson = json_encode($feature['geometry'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Use PDO binding instead of DB::raw string interpolation to avoid SQL injection / quote issues
            DB::statement("
            INSERT INTO lots (lot_number, phase_id, lot_type, total_capacity, coordinates, created_at, updated_at)
            VALUES (?, ?, ?, ?, ST_GeomFromGeoJSON(?), NOW(), NOW())
        ", [
                $attributes['lot_number'] ?? 'LOT-' . ($index + 1),
                $attributes['phase_id'],
                $attributes['lot_type'],
                $attributes['total_capacity'],
                $geometryJson,
            ]);

            $counter++;
        }

        $this->command->info("Total lots imported: {$counter}");
    }

    /**
     * Description: Only use this function for testing (without the actual data from Panteon);
     *   Mainly responsible for assigining each deceased record to teir own lot
     */
    // FIX: Optimize this further, it take 2mins at least seed
    private function deceasedRecords(): void
    {

        $deceasedCollection = DeceasedRecord::all();

        $deceasedIndex = 0;
        $totalDeceased = $deceasedCollection->count();

        // eager load burialRecords count to track usage
        $lots = Lot::withCount('burialRecords')->get();

        foreach ($lots as $lot) {
            if ($deceasedIndex >= $totalDeceased) {
                break; // stop if all deceased assigned
            }

            $lot_capacity = $lot->total_capacity;
            $lot_current_occupants = $lot->burial_records_count;
            $lot_remaining_slots = $lot_capacity - $lot_current_occupants;

            if ($lot_remaining_slots <= 0) {
                continue; // lot already full, continue to next lot
            }

            for ($i = 0; $i < $lot_remaining_slots; $i++) {
                if ($deceasedIndex >= $totalDeceased) {
                    break; // stop if all deceased assigned
                }

                $deceased = $deceasedCollection[$deceasedIndex];

                $lot->burialRecords()->create(
                    BurialRecord::factory()->make([
                        'deceased_record_id' => $deceased->id,
                    ])->toArray()
                );

                $deceasedIndex++;
            }

            // If lot is full, mark occupied
            if ($lot_remaining_slots > 0) {
                $lot->update(['status' => 'Occupied']);
            }
        }
    }
}
