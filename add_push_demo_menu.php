<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

// Create the menu item
$menu = new Menu();
$menu->title = 'Push Notification Demo';
$menu->href = '/push/demo';
$menu->icon = 'Bell';
$menu->parent_id = null;
$menu->order = 20;
$menu->is_active = true;
$menu->save();

echo "Menu item created with ID: {$menu->id}\n";

// Assign the menu to all roles
$roles = Role::all();
foreach ($roles as $role) {
    DB::table('menus_role')->insert([
        'menu_id' => $menu->id,
        'role_id' => $role->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Menu assigned to role: {$role->name}\n";
}

echo "Done!\n";