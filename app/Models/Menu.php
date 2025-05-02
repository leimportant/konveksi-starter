<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'href',
        'icon',
        'parent_id',
        'order'
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'menus_role')
            ->withTimestamps();
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }
}