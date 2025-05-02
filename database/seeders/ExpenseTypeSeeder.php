<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Material'],
            ['name' => 'Labor'],
            ['name' => 'Overhead'],
            ['name' => 'Transportation'],
            ['name' => 'Other'],
        ];

        foreach ($types as $type) {
            DB::table('mst_expense_type')->insert($type);
        }
    }
}