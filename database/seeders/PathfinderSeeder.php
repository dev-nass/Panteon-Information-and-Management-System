<?php

namespace Database\Seeders;

use App\Models\Junction;
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

        if (! file_exists($geoJsonPath)) {
            $this->command->error("GeoJSON file for junctions not found at path: {$geoJsonPath}");

            return;
        }

        $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

        if (! $geoJsonData || ! isset($geoJsonData['features'])) {
            $this->command->error("Invalid GeoJSON format: 'features' key not found");

            return;
        }

        // Idempotency guard: skip already-seeded junctions (matches PanteonDataSeeder pattern)
        $existingJunctions = DB::table('junctions')->pluck('junction_number')->flip();
        $imported = 0;
        $skipped = 0;

        foreach ($geoJsonData['features'] as $feature) {
            $coordinates = $feature['geometry']['coordinates'];
            $properties = $feature['properties'];

            $junctionNumber = 'J'.str_pad($properties['id'], 3, '0', STR_PAD_LEFT);

            if (isset($existingJunctions[$junctionNumber])) {
                $skipped++;

                continue;
            }

            // Determine type based on label
            $type = 'intersection';
            if (isset($properties['label']) && $properties['label'] === 'entrance') {
                $type = 'entrance';
            }

            // Use GeoJSON (ST_GeomFromGeoJSON) so [lon,lat] order is handled correctly
            // on both MariaDB (local) and MySQL 8 (Railway) - fixes Latitude 120 out-of-range
            $pointGeoJson = json_encode([
                'type' => 'Point',
                'coordinates' => [(float) $coordinates[0], (float) $coordinates[1]],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            DB::statement(
                'INSERT INTO junctions (junction_number, type, coordinates, label, created_at, updated_at) VALUES (?, ?, ST_GeomFromGeoJSON(?), ?, NOW(), NOW())',
                [$junctionNumber, $type, $pointGeoJson, $properties['label'] ?? null]
            );

            $existingJunctions[$junctionNumber] = true;
            $imported++;
        }

        $this->command->info("Junctions imported: {$imported}".($skipped > 0 ? " ({$skipped} skipped - already exists)" : ''));
    }

    private function seedPathways(): void
    {
        $geoJsonPath = public_path('data/pathways/pathways.geojson');

        if (! file_exists($geoJsonPath)) {
            $this->command->error("GeoJSON file for pathways not found at path: {$geoJsonPath}");

            return;
        }

        $geoJsonData = json_decode(file_get_contents($geoJsonPath), true);

        if (! $geoJsonData || ! isset($geoJsonData['features'])) {
            $this->command->error("Invalid GeoJSON format: 'features' key not found");

            return;
        }

        // Idempotency guard: preload existing pathways (from->to) and junction lookup
        $existingPathways = DB::table('pathways')->select('from_junction_id', 'to_junction_id')->get()
            ->mapWithKeys(fn ($p) => ["{$p->from_junction_id}|{$p->to_junction_id}" => true])->toArray();
        $junctionMap = Junction::pluck('id', 'junction_number')->toArray();

        $imported = 0;
        $skipped = 0;

        foreach ($geoJsonData['features'] as $feature) {
            $properties = $feature['properties'];
            // LineString coordinates are directly in the coordinates array
            $coordinates = $feature['geometry']['coordinates'];

            // Get junction IDs from preloaded map (avoids N+1 query)
            $fromNumber = 'J'.str_pad($properties['f_id'], 3, '0', STR_PAD_LEFT);
            $toNumber = 'J'.str_pad($properties['t_id'], 3, '0', STR_PAD_LEFT);
            $fromId = $junctionMap[$fromNumber] ?? null;
            $toId = $junctionMap[$toNumber] ?? null;

            if (! $fromId || ! $toId) {
                $this->command->warn("Skipping pathway {$properties['id']}: Junction not found (f_id: {$properties['f_id']}, t_id: {$properties['t_id']})");

                continue;
            }

            $pathwayKey = "{$fromId}|{$toId}";
            if (isset($existingPathways[$pathwayKey])) {
                $skipped++;

                continue;
            }

            // Calculate distance between points (Haversine expects lat,lon)
            $distance = $this->calculateDistance(
                $coordinates[0][1],
                $coordinates[0][0],
                $coordinates[1][1],
                $coordinates[1][0]
            );

            // Use GeoJSON for LineString so [lon,lat] order works on both MariaDB and MySQL 8
            $lineGeoJson = json_encode([
                'type' => 'LineString',
                'coordinates' => array_map(fn ($c) => [(float) $c[0], (float) $c[1]], $coordinates),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            DB::statement(
                'INSERT INTO pathways (from_junction_id, to_junction_id, distance_meters, coordinates, created_at, updated_at) VALUES (?, ?, ?, ST_GeomFromGeoJSON(?), NOW(), NOW())',
                [$fromId, $toId, $distance, $lineGeoJson]
            );

            $existingPathways[$pathwayKey] = true;
            $imported++;
        }

        $this->command->info("Pathways imported: {$imported}".($skipped > 0 ? " ({$skipped} skipped - already exists)" : ''));
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
