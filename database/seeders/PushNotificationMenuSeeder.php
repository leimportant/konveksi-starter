<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PushNotificationMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Insert the Push Notification Demo menu item
        $menuId = DB::table('menus')->insertGetId([
            'title' => 'Push Notification Demo',
            'href' => '/push/demo',
            'icon' => 'Bell',
            'parent_id' => null,
            'order' => 20,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign the menu to all roles
        $roles = DB::table('roles')->select('id')->get();
        
        foreach ($roles as $role) {
            DB::table('menus_role')->insert([
                'menu_id' => $menuId,
                'role_id' => $role->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}