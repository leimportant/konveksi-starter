<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Insert the parent menu manually to get its ID (if needed)
        DB::table('menus')->insert([
            'title' => 'Master Data',
            'href' => '#',
            'icon' => 'LayoutGrid',
            'parent_id' => null,
            'order' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get inserted 'Master Data' ID (if auto-increment is used)
        $masterDataId = DB::table('menus')->where('title', 'Master Data')->value('id');

        $configurationId = null;

        // Insert the rest
        $menus = [
            ['title' => 'Category', 'href' => '/categories', 'icon' => 'List', 'parent_id' => $masterDataId, 'order' => 2],
            ['title' => 'Uom', 'href' => '/uoms', 'icon' => 'Ruler', 'parent_id' => $masterDataId, 'order' => 3],
            ['title' => 'Size', 'href' => '/sizes', 'icon' => 'Maximize', 'parent_id' => $masterDataId, 'order' => 4],
            ['title' => 'Product', 'href' => '/products', 'icon' => 'Package', 'parent_id' => $masterDataId, 'order' => 5],
            ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutDashboard', 'parent_id' => null, 'order' => 0],
            ['title' => 'Apparel (Konveksi)', 'href' => '/konveksi', 'icon' => 'Shirt', 'parent_id' => null, 'order' => 6],
            ['title' => 'Sales', 'href' => '/sales', 'icon' => 'ShoppingCart', 'parent_id' => null, 'order' => 7],
            ['title' => 'Payment Method', 'href' => '/payment-methods', 'icon' => 'CreditCard', 'parent_id' => $masterDataId, 'order' => 9],
            ['title' => 'Price Type', 'href' => '/price-types', 'icon' => 'Tag', 'parent_id' => $masterDataId, 'order' => 10],
            ['title' => 'Product Price', 'href' => '/product-prices', 'icon' => 'DollarSign', 'parent_id' => $masterDataId, 'order' => 11],
            ['title' => 'Sloc', 'href' => '/slocs', 'icon' => 'Warehouse', 'parent_id' => $masterDataId, 'order' => 12],
        ];

        foreach ($menus as $menu) {
            DB::table('menus')->insert(array_merge($menu, [
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Insert 'Configuration' as another root
        DB::table('menus')->insert([
            'title' => 'Configuration',
            'href' => '#',
            'icon' => 'Settings',
            'parent_id' => null,
            'order' => 13,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $configurationId = DB::table('menus')->where('title', 'Configuration')->value('id');

        // Insert its children
        $configChildren = [
            ['title' => 'Register User', 'href' => '/register-user', 'icon' => 'UserPlus', 'parent_id' => $configurationId, 'order' => 14],
            ['title' => 'User Management', 'href' => '/user', 'icon' => 'Users', 'parent_id' => $configurationId, 'order' => 15],
            ['title' => 'Role', 'href' => '/role', 'icon' => 'Shield', 'parent_id' => $configurationId, 'order' => 16],
        ];

        foreach ($configChildren as $menu) {
            DB::table('menus')->insert(array_merge($menu, [
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
