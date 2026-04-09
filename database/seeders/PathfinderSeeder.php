<?php

namespace Database\Seeders;

use App\Models\Junction;
use App\Models\Pathway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PathfinderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedJunctions();
        $this->seedPathways();
    }

    private function seedJunctions(): void
    {
        $geoJsonPath = public_path('data/pathways/junctions.geojson');

        if (!file_exists($geoJsonPath)) {
            $this->command->error("GeoJSON file for junctions not found at path: {$geoJsonPath}");
            return;
        }

        $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

        if (!$geoJsonData || !isset($geoJsonData['features'])) {
            $this->command->error("Invalid GeoJSON format: 'features' key not found");
            return;
        }

        foreach ($geoJsonData['features'] as $feature) {
            $coordinates = $feature['geometry']['coordinates'];
            $properties = $feature['properties'];

            // Determine type based on label
            $type = 'intersection';
            if (isset($properties['label']) && $properties['label'] === 'entrance') {
                $type = 'entrance';
            }

            // Create POINT geometry from coordinates
            $point = DB::raw("ST_GeomFromText('POINT({$coordinates[0]} {$coordinates[1]})', 4326)");

            Junction::insert([
                'junction_number' => 'J' . str_pad($properties['id'], 3, '0', STR_PAD_LEFT),
                'type' => $type,
                'coordinates' => $point,
                'label' => $properties['label'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("Junctions imported: " . count($geoJsonData['features']));
    }

    private function seedPathways(): void
    {
        $geoJsonPath = public_path('data/pathways/pathways.geojson');

        if (!file_exists($geoJsonPath)) {
            $this->command->error("GeoJSON file for pathways not found at path: {$geoJsonPath}");
            return;
        }

        $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

        if (!$geoJsonData || !isset($geoJsonData['features'])) {
            $this->command->error("Invalid GeoJSON format: 'features' key not found");
            return;
        }

        foreach ($geoJsonData['features'] as $feature) {
            $properties = $feature['properties'];
            // LineString coordinates are directly in the coordinates array
            $coordinates = $feature['geometry']['coordinates'];

            // Get junction IDs from database
            $fromJunction = Junction::where('junction_number', 'J' . str_pad($properties['f_id'], 3, '0', STR_PAD_LEFT))->first();
            $toJunction = Junction::where('junction_number', 'J' . str_pad($properties['t_id'], 3, '0', STR_PAD_LEFT))->first();

            if (!$fromJunction || !$toJunction) {
                $this->command->warn("Skipping pathway {$properties['id']}: Junction not found (f_id: {$properties['f_id']}, t_id: {$properties['t_id']})");
                continue;
            }

            // Calculate distance between points
            $distance = $this->calculateDistance(
                $coordinates[0][1],
                $coordinates[0][0],
                $coordinates[1][1],
                $coordinates[1][0]
            );

            // Create LINESTRING geometry from coordinates
            $lineString = 'LINESTRING(';
            foreach ($coordinates as $coord) {
                $lineString .= "{$coord[0]} {$coord[1]},";
            }
            $lineString = rtrim($lineString, ',') . ')';

            Pathway::insert([
                'from_junction_id' => $fromJunction->id,
                'to_junction_id' => $toJunction->id,
                'distance_meters' => $distance,
                'coordinates' => DB::raw("ST_GeomFromText('{$lineString}', 4326)"),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("Pathways imported: " . count($geoJsonData['features']));
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2); // Distance in meters
    }
}