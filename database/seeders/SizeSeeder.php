<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['id' => 'S', 'name' => 'S'],
            ['id' => 'M', 'name' => 'M'],
            ['id' => 'L', 'name' => 'L'],
            ['id' => 'XL', 'name' => 'XL'],
            ['id' => 'XXL', 'name' => 'XXL'],
        ];

        foreach ($sizes as $size) {
            DB::table('mst_size')->insert($size);
        }
    }
}