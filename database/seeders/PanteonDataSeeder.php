<?php

namespace Database\Seeders;

use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use App\Models\Section;
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
        $this->seedSections();
        $this->seedLots();
        $this->deceasedRecords();
    }

    private function seedSections(): void
    {
        $geoJsonPath = public_path('data/sections.geojson');

        if (!$geoJsonPath) {
            $this->command->error("GeoJSON file for section not found at path $geoJsonPath");
            return;
        }

        $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

        if (!$geoJsonData['features']) {
            $this->command->error("Invalid GeoJSON format: 'features' key not found.");
            return;
        }

        $this->command->info("Seeding sections from GeoJSON...");

        foreach ($geoJsonData['features'] as $feature) {
            // $section_attributes = $feature['properties'];

            // holds the coordinates from QGIS
            $section_coords = [
                'coordinates' => json_encode($feature['geometry']),
            ];

            // insert the other attributes using the factory definition & section variable data
            Section::factory()->create($section_coords);
        }

        $this->command->info("Sections imported: " . count($geoJsonData['features']));
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

        $counter = 1;

        foreach ($allFeatures as $index => $feature) {
            // skip if geometry is missing or coordinates are empty
            if (
                !isset($feature['geometry'])
                || !isset($feature['geometry']['coordinates'])
                || empty($feature['geometry']['coordinates'])
            ) {
                $this->command->warn("Skipping LOT-{$index}: empty geometry");
                continue;
            }

            $attributes  = $feature['properties'];
            $geometryJson = json_encode($feature['geometry'], JSON_UNESCAPED_SLASHES);

            Lot::create([
                'lot_number'    => $attributes['lot_number'] ?? 'LOT-' . ($index + 1),
                'section_id'    => $attributes['section_id'],
                'lot_type'      => $attributes['lot_type'],
                'total_capacity' => $attributes['total_capacity'],
                'coordinates'   => DB::raw("ST_GeomFromGeoJSON('{$geometryJson}')"),
            ]);
        }

        $this->command->info("Total lots imported: " . ($counter - 1));
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
