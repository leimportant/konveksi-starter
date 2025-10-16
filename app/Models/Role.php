<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'all_employee',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    /**
     * Get the users for the role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menus_role', 'role_id', 'menu_id');
    }

    public function activityGroups()
    {
        return $this->hasMany(ActivityRoleRef::class, 'role_id', 'id');
    }
}
