<?php

namespace Database\Seeders;

use App\Models\main\Arrangement;
use Illuminate\Database\Seeder;

class ArrangementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'key'  => 'company_name',
                'type' => 'text',
            ],
            [
                'key'  => 'address',
                'type' => 'textarea',
            ],
            [
                'key'  => 'zip',
                'type' => 'text',
            ],
            [
                'key'  => 'npwp',
                'type' => 'text',
            ],
            [
                'key'  => 'phone',
                'type' => 'text',
            ],
            [
                'key'  => 'email',
                'type' => 'text',
            ],
            [
                'key'  => 'pic',
                'type' => 'text',
            ],
            [
                'key'  => 'pic_phone',
                'type' => 'text',
            ],
            [
                'key'  => 'pic2',
                'type' => 'text',
            ],
            [
                'key'  => 'phone_pic2',
                'type' => 'text',
            ],
            [
                'key'  => 'logo',
                'type' => 'file',
            ],
            [
                'key'  => 'coa_digit',
                'type' => 'integer',
            ],
            [
                'key'  => 'est_date',
                'type' => 'text',
            ],
            [
                'key'  => 'address_opt',
                'type' => 'text',
            ],
            [
                'key'  => 'province',
                'type' => 'text',
            ],
            [
                'key'  => 'city',
                'type' => 'text',
            ],
            [
                'key'  => 'estdate',
                'type' => 'text',
            ],
            [
                'key'  => 'cutoff_date',
                'type' => 'integer',
            ],
            [
                'key'  => 'lifespan',
                'type' => 'integer',
            ],
            [
                'key'  => 'receive_coa_discount',
                'type' => 'integer',
            ],
            [
                'key'  => 'expense_coa_discount',
                'type' => 'integer',
            ],
            [
                'key'  => 'pkp',
                'type' => 'text',
            ],
            [
                'key'  => 'equity_coa',
                'type' => 'integer',
            ],
            [
                'key'  => 'income_summary_coa',
                'type' => 'integer',
            ],
            [
                'key'  => 'bank_fee_coa',
                'type' => 'integer',
            ],
            [
                'key'  => 'bank_interest_coa',
                'type' => 'integer',
            ],
            [
                'key'  => 'is_setup',
                'type' => 'integer',
            ],
            [
                'key'  => 'retained_earnings_coa',
                'type' => 'integer',
            ],
            [
                'key'  => 'coa_pph_badan',
                'type' => 'integer',
            ],
            [
                'key'  => 'coa_pph_pasal_22',
                'type' => 'integer',
            ],
            [
                'key'  => 'coa_pph_pasal_23',
                'type' => 'integer',
            ],
            [
                'key'  => 'coa_utang_pajak_29',
                'type' => 'integer',
            ],
            [
                'key'  => 'coa_pph_pasal_25',
                'type' => 'integer',
            ],
            [
                'key'  => 'company_category',
                'type' => 'text',
            ],
            [
                'key'  => 'company_initial',
                'type' => 'text',
            ],
            [
                'key'  => 'default_ppn',
                'type' => 'integer',
            ],
            [
                'key'  => 'coa_vat',
                'type' => 'integer',
            ],
            [
                'key'  => 'coa_advance_payment',
                'type' => 'integer',
            ],
            [
                'key'  => 'coa_advance_payment_bank',
                'type' => 'integer',
            ],
        ];

        foreach ($data as $key => $value) {
            Arrangement::insert($value);
        }
    }
}
