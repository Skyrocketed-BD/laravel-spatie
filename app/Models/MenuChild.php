<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuChild extends Model
{
    use HasFactory;
    // untuk default tabel
    protected $table = 'menu_child';
    // untuk default primary key
    protected $primaryKey = 'id_menu_child';
    // untuk fillable
    protected $fillable = [
        'id_menu_child',
        'id_menu_body',
        'name',
        'url',
    ];

    // relasi ke tabel menu_body
    public function toMenuBody()  {
        return $this->belongsTo(MenuBody::class, 'id_menu_body', 'id_menu_body');
    }
}
