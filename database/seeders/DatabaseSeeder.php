<?php

namespace Database\Seeders;

use App\Models\DeceasedRecord;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


        // Seed deceased record and applicants
        // $chunkSize = 1000;
        // $total = 21000;

        // for ($i = 0; $i < $total; $i += $chunkSize) {
        //     $deceasedBatch = DeceasedRecord::factory()
        //         ->count($chunkSize)
        //         ->make()
        //         ->toArray();

        //     DeceasedRecord::insert($deceasedBatch);
        // }


        $this->call(PanteonDataSeeder::class);
        $this->call(PathfinderSeeder::class);
    }
}
