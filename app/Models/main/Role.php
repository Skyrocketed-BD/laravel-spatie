<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as ModelsRole;

class Role extends ModelsRole
{
    use HasFactory;
    // untuk default tabel
    protected $table = 'roles';
    // untuk default primary key
    protected $primaryKey = 'id';
    // untuk fillable
    protected $fillable = [
        'name',
        'guard_name',
    ];

    // relasi ke tabel role_access
    public function toRoleAccess()  {
        return $this->hasMany(RoleAccess::class, 'id_role');
    }

    // relasi ke tabel role_has_permission
    public function toRoleHasPermission()  {
        return $this->hasMany(RoleHasPermission::class, 'role_id');
    }
}