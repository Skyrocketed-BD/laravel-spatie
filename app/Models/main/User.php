<?php

namespace App\Models\main;

use App\Models\operation\Kontraktor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    use SoftDeletes;

    // untuk default tabel
    protected $table = 'users';

    // untuk default id
    protected $primaryKey = 'id_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_users',
        'id_kontraktor',
        'name',
        'email',
        'username',
        'password',
        'gender',
        'birth_date',
        'phone',
        'address',
        'avatar',
        'is_active',
        'is_logged_in',
        'count_logged_in',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'id_role'           => 'integer',
            'id_users'          => 'integer',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // untuk relasi
    protected $with = [
        'toUserNotifications',
        'toModelHasRole.toRoleAccess.toMenuBody.toMenuCategory.toMenuModule',
    ];

    public function toKontraktor()
    {
        return $this->setConnection('operation')->belongsTo(Kontraktor::class, 'id_kontraktor', 'id_kontraktor');
    }

    public function toModelHasRole()
    {
        return $this->hasOne(ModelHasRole::class, 'model_id', 'id_users');
    }

    public function toUserNotifications()
    {
        return $this->hasMany(UserNotification::class, 'id_users', 'id_users');
    }
}
