<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    protected $table = 'model_has_roles';

    protected $fillable = [
        'role_id',
        'model_type',
        'model_id'
    ];

    public function toRoleAccess()
    {
        return $this->hasMany(RoleAccess::class, 'id_role', 'role_id');
    }

    public function toRole()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
