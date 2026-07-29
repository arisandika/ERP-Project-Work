<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // tolong disini hanya khusus seeder yang berkaitan dengan role & permission saja, untuk data lain buat seeder terpisah
        $this->call([
            ShieldSeeder::class,
        ]);
    }
}
