<?php

namespace App\Models\main;

use App\Models\contract_legal\Kontrak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kontak extends Model
{
    use HasFactory;

    protected $table = 'kontak';

    protected $primaryKey = 'id_kontak';

    protected $fillable = [
        'id_kontrak',
        'id_perusahaan',
        'id_kontak_jenis',
        'name',
        'npwp',
        'phone',
        'email',
        'website',
        'address',
        'postal_code',
        'is_company'
    ];

    public function toKontrak()
    {
        return $this->setConnection('contract_legal')->belongsTo(Kontrak::class, 'id_kontrak', 'id_kontrak');
    }

    public function toKontakJenis()
    {
        return $this->belongsTo(KontakJenis::class, 'id_kontak_jenis', 'id_kontak_jenis');
    }

    protected static function booted()
    {
        static::creating(function ($row) {
            $row->created_by = auth('api')->user()->id_users;
        });

        static::updating(function ($row) {
            $row->updated_by = auth('api')->user()->id_users;
        });
    }
}
