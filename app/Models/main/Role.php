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

    // protected $with = ['toRoleAccess'];

    // relasi ke tabel role_access
    public function toRoleAccess()  {
        return $this->hasMany(RoleAccess::class, 'id_role');
    }

    // protected function casts(): array
    // {
    //     return [
    //         'id' => 'integer',
    //     ];
    // }
}
