<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Find and delete the Push Notification Demo menu
        $menu = DB::table('menus')
            ->where('title', 'Push Notification Demo')
            ->where('href', '/push/demo')
            ->first();

        if ($menu) {
            // Delete menu-role associations
            DB::table('menus_role')->where('menu_id', $menu->id)->delete();
            
            // Delete the menu
            DB::table('menus')->where('id', $menu->id)->delete();
        }
    }
};