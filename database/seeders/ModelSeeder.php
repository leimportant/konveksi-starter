<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            ['name' => 'Model A', 'description' => 'Basic model type A'],
            ['name' => 'Model B', 'description' => 'Premium model type B'],
            ['name' => 'Model C', 'description' => 'Deluxe model type C'],
        ];

        foreach ($models as $model) {
            DB::table('tr_model')->insert($model);
        }
    }
}