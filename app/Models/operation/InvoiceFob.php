<?php

namespace App\Models\operation;

use App\Models\finance\Journal;
use App\Models\finance\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceFob extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'invoice_fob';

    // primary key
    protected $primaryKey = 'id_invoice_fob';

    // fillable
    protected $fillable = [
        'id_plan_barging',
        'id_journal',
        'id_kontak',
        'transaction_number',
        'date',
        'hma',
        'hpm',
        'kurs',
        'ni',
        'mc',
        'tonage',
        'price',
        'description',
        'reference_number',
        'created_by',
        'updated_by'
    ];

    // relasi ke tabel plan_barging
    public function toPlanBarging()
    {
        return $this->belongsTo(PlanBarging::class, 'id_plan_barging', 'id_plan_barging');
    }

    // relasi ke tabel journal
    public function toJournal()
    {
        return $this->setConnection('finance')->belongsTo(Journal::class, 'id_journal', 'id_journal');
    }

    // relasi ke tabel transaction
    public function toTransaction()
    {
        return $this->setConnection('finance')->belongsTo(Transaction::class, 'transaction_number', 'transaction_number');
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
