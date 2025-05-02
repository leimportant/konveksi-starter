<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        $uoms = [
            ['name' => 'PCS'],
            ['name' => 'DOZ'],
            ['name' => 'BOX'],
            ['name' => 'KG'],
            ['name' => 'M'],
        ];

        foreach ($uoms as $uom) {
            DB::table('mst_uom')->insert($uom);
        }
    }
}