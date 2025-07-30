<?php

namespace App\Models;

use Spatie\Permission\Models\Role as RoleModel;

class Role extends RoleModel
{
    public $guarded = [];

    protected $table = 'roles';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'guard_name',
    ];

    public function toRoleAccess()
    {
        return $this->hasMany(RoleAccess::class, 'id_role');
    }

    public function toRoleHasPermission()
    {
        return $this->hasMany(RoleHasPermission::class, 'role_id');
    }
}
