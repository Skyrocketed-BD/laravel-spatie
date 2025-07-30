<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuPermission extends Model
{
    use HasFactory;
    protected $table = 'menu_permission';
    protected $primaryKey = 'id_menu_permission';
    protected $fillable = [
        'id_menu_body',
        'id_permission',
    ];

    // relasi ke tabel menu_body
    public function toMenuBody()  {
        return $this->belongsTo(MenuBody::class, 'id_menu_body', 'id_menu_body');
    }

    // relasi ke tabel permission
    public function toPermission()  {
        return $this->belongsTo(Permission::class, 'id_permission', 'id');
    }
}
