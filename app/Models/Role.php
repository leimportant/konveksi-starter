<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menus_role')->withTimestamps();
    }
}
