<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ExpenseTypeSeeder::class,
            UomSeeder::class,
            SizeSeeder::class,
            ModelSeeder::class,
            ModelSizeSeeder::class,
        ]);
    }
}