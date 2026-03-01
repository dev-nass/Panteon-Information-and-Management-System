<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Lot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PanteonDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
    }

    private function seedSections(): void
    {
        $geoJsonPath = public_path('data/sectiion.geojson');

        if (!$geoJsonPath) {
            $this->command->error("GeoJSON file for section not found at path $geoJsonPath");
            return;
        }

        $this->command->info("Seeding sections from GeoJSON...");

        foreach ($geoJsonPath['features'] as $feature) {
            // $section_attributes = $feature['properties'];

            $section_coords = [
                'coordinates' => $feature['geometry'],
            ];

            Section::factory()->create($section_coords);
        }

        $this->command->info("Sections imported: " . count($geoJsonPath['features']));
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
        $geoJsonData_appartment = json_decode(file_get_contents($geoJsonPath_Appartment), true); // FIX: Was using Underground path

        if (!isset($geoJsonData_underground['features']) || !isset($geoJsonData_appartment['features'])) {
            $this->command->error("Invalid GeoJSON format: 'features' key not found.");
            return;
        }

        $this->command->info("Seeding lots from GeoJSON...");

        /**
        * If I remember correctly, foreach create a shallow coppy of the array,
        * meaning that if I states $features['properties']['lot_type'] = 'underground'
        * It won't change the actual value just the shallow copy
        * */
        // Add lot_type to each feature's properties
        // REFERENCE OPERATOR
        foreach ($geoJsonData_underground['features'] as &$feature) {
            $feature['properties']['lot_type'] = 'underground';
            unset($feature); // Break reference
        }

        foreach ($geoJsonData_appartment['features'] as &$feature) {
            $feature['properties']['lot_type'] = 'apartment';
            unset($feature); // Break reference
        }

        $allFeatures = array_merge(
            $geoJsonData_underground['features'],
            $geoJsonData_appartment['features']
        );

        foreach ($allFeatures as $feature) {
            $attributes  = $feature['properties'];

            $lot = [
                'section_id' => $attributes['section_id'],
                'lot_type' => $attributes['lot_type'],
                'coordinates' => $feature['geometry'],
            ];

            Lot::factory()->create($lot);
        }

        $this->command->info("Underground lots imported: " . count($geoJsonData_underground['features']));
        $this->command->info("Appartment lots imported: " . count($geoJsonData_appartment['features']));
        $this->command->info("Total lots imported: " . count($allFeatures));
    }
}
