<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The tax tables are not sample data: without them the calculator has nothing to compute
     * with, so they are seeded in every environment. Places come second, they need the year.
     */
    public function run(): void
    {
        $this->call([
            TaxYear2026Seeder::class,
            TaxPlaces2026Seeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
