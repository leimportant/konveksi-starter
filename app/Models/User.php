<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPushSubscriptions;

    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public  $incrementing = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'password',
        'active',
        "phone_number",
        "location_id",
        "employee_status",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
    ];

    /**
     * Get the roles for the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!empty($model->google_id)) {
                // Untuk user dari Google: mulai dari 30001
                $lastGoogleUser = self::whereNotNull('google_id')->orderBy('id', 'desc')->first();
                $model->id = $lastGoogleUser && $lastGoogleUser->id >= 30001
                    ? $lastGoogleUser->id + 1
                    : 30001;
            } else {
                // Untuk user dari admin/manual: mulai dari 10001
                $lastManualUser = self::whereNull('google_id')->orderBy('id', 'desc')->first();
                $model->id = $lastManualUser && $lastManualUser->id >= 10001
                    ? $lastManualUser->id + 1
                    : 10001;
            }
        });
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function pushSubscription()
    {
        return $this->hasOne(PushSubscription::class);
    }


}   
