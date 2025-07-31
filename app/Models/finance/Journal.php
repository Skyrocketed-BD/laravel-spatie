<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'journal';

    protected $primaryKey = 'id_journal';

    protected $fillable = [
        'id_journal_set',
        'name',
        'category',
        'alocation',
        'is_outstanding',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id_journal_set' => 'integer',
        ];
    }

    // relasi ke tabel journal_set
    public function toJournalSet()
    {
        return $this->hasMany(JournalSet::class, 'id_journal', 'id_journal');
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
