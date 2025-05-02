<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelSizeSeeder extends Seeder
{
    public function run(): void
    {
        $modelSizes = [
            ['model_id' => 1, 'size_id' => 1],
            ['model_id' => 1, 'size_id' => 2],
            ['model_id' => 1, 'size_id' => 3],
            ['model_id' => 2, 'size_id' => 2],
            ['model_id' => 2, 'size_id' => 3],
            ['model_id' => 2, 'size_id' => 4],
            ['model_id' => 3, 'size_id' => 3],
            ['model_id' => 3, 'size_id' => 4],
            ['model_id' => 3, 'size_id' => 5],
        ];

        foreach ($modelSizes as $modelSize) {
            DB::table('tr_model_size')->insert($modelSize);
        }
    }
}