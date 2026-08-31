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
     * Both use updateOrCreate, so re-running this seeder on a deployed environment is how an
     * updated tax year gets published — it is not a one shot install step.
     *
     * The demo user is the opposite, and it is fenced off from anything but local. Seeded in
     * production it would be a real account with a password anyone can read: UserFactory
     * hashes the literal string "password". It would not even survive the deploy — faker is a
     * require-dev package, and production installs with --no-dev, so User::factory() is not
     * loadable there at all.
     */
    public function run(): void
    {
        $this->call([
            TaxYear2026Seeder::class,
            TaxPlaces2026Seeder::class,
        ]);

        if (! app()->environment('local')) {
            return;
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
