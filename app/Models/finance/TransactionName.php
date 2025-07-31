<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionName extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'transaction_name';

    protected $primaryKey = 'id_transaction_name';

    protected $fillable = [
        'name',
        'category',
        'created_by',
        'updated_by',
    ];

    // untuk relasi ke tabel transaction
    public function toTransaction()
    {
        return $this->hasMany(Transaction::class, 'id_transaction_name', 'id_transaction_name');
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
