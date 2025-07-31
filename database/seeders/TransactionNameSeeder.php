<?php

namespace Database\Seeders;

use App\Models\finance\TransactionName;
use Illuminate\Database\Seeder;

class TransactionNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transantion_name = [
            [
                'id_transaction_name'   => 1,
                'name'                  => 'Pengeluaran',
                'category'              => 'pengeluaran',
            ],
            [
                'id_transaction_name'   => 2,
                'name'                  => 'Penerimaan',
                'category'              => 'penerimaan',
            ]
        ];

        foreach ($transantion_name as $key => $value) {
            TransactionName::insert($value);
        }
    }
}
