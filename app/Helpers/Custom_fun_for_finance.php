<?php

use App\Helpers\ActivityLogHelper;
use Carbon\Carbon;
use App\Models\finance\Coa;
use App\Models\finance\Expenditure;
use App\Models\finance\GeneralLedger;
use App\Models\finance\Journal;
use App\Models\finance\Receipts;
use App\Models\finance\ReportFormula;
use App\Models\finance\ReportTitle;
use App\Models\finance\TaxRate;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;
use App\Models\finance\TransactionTax;

if (!function_exists('_count_report_title_formula')) {
    function _count_report_title_formula($id_report_title, $start_date, $end_date)
    {
        $report_formula = ReportFormula::where('id_report_title', $id_report_title)->get();

        $total = 0;

        foreach ($report_formula as $key => $value) {
            $result = _count_report_title_total($value->id_report_title_select, $start_date, $end_date);

            if ($value->operation === '+') {
                $total = $total + $result;
            } else if ($value->operation === '-') {
                $total = $total - $result;
            } else if ($value->operation === '*') {
                $total = $total * $result;
            } else if ($value->operation === '/') {
                $total = $total / $result;
            }
        }

        return $total;
    }
}

if (!function_exists('_count_report_title_total')) {
    function _count_report_title_total($id_report_title, $start_date, $end_date)
    {
        $report_title = ReportTitle::with(['toReportBody'])->find($id_report_title);
        $coa_labarugi_berjalan = Coa::find(get_arrangement('equity_coa'))->coa;

        $total = 0;
        if ($report_title->type === 'formula') {
            $total = _count_report_title_formula($id_report_title, $start_date, $end_date);
        } else if ($report_title->type === 'input') {
            $total = $report_title->value;
        } else {
            // default
            $report_body = $report_title->toReportBody;

            foreach ($report_body as $key => $value2) {
                $balance = 0;

                if ($value2->method === 'coa') {
                    if ($value2->toCoa->coa == $coa_labarugi_berjalan) {
                        $balance = _sum_pendapatan_beban($start_date, $end_date, ['opr', 'int', 'acm', 'tax']);
                    } else {
                        $balance = _sum_account_saldo($value2, $start_date, $end_date, ['opr', 'int', 'acm', 'tax']);
                    }
                }

                if ($value2->method === 'subcoa') {
                    $balance = _count_coa_body($value2->id_coa_body, $start_date, $end_date);
                }

                if ($value2->method === 'range') {
                    $balance = _sum_account_saldo($value2, $start_date, $start_date, ['opr', 'int', 'acm', 'tax']);
                }

                if ($value2->method === 'report') {
                    $balance = _count_report_menu_total($value2->id_report_menu, $start_date, $end_date);
                }

                if ($value2->operation === '+') {
                    $total = $total + $balance;
                } else if ($value2->operation === '-') {
                    $total = $total - $balance;
                } else if ($value2->operation === '*') {
                    $total = $total * $balance;
                } else if ($value2->operation === '/') {
                    $total = $total / $balance;
                }
            }
        }

        return $total;
    }
}

if (!function_exists('_count_report_menu_total')) {
    function _count_report_menu_total($id_report_menu, $start_date, $end_date)
    {
        $report_title = ReportTitle::whereIdReportMenu($id_report_menu)->get();

        $total = [];
        foreach ($report_title as $key => $value) {
            if ($value->type === 'formula') {
                $nilai = _count_report_title_formula($value->id_report_title, $start_date, $end_date);
            } else if ($value->type === 'input') {
                $nilai = $value->value;
            } else {
                // default
                $nilai = _count_report_title_total($value->id_report_title, $start_date, $end_date);
            }

            $total[] =  $nilai;
        }

        end($total);

        $key = key($total);

        return $total[$key] ?? 0;
    }
}

if (!function_exists('_count_coa_body')) {
    function _count_coa_body($id_coa_body, $start_date, $end_date)
    {
        $coa = Coa::whereIdCoaBody($id_coa_body)->get();

        $total = 0;

        foreach ($coa as $key => $value) {
            $total += _sum_coa_saldo($value, $start_date, $end_date);
        }

        return $total;
    }
}

if (!function_exists('_sum_account_saldo')) {
    function _sum_account_saldo($coa, $start_date, $end_date, $phase)
    {
        $balance     = 0;
        $totalDebit  = 0;
        $totalCredit = 0;

        $totalDebit  = $coa->toCoa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'D')->whereIn('phase', $phase)->sum('value');
        $totalCredit = $coa->toCoa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'K')->whereIn('phase', $phase)->sum('value');

        if ($coa->toCoa->toCoaBody->toCoaClasification->normal_balance == 'D') {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }

        return $balance;
    }
}

// menghitung saldo akhir berdasarkan coa
if (!function_exists('_sum_coa_saldo')) {
    function _sum_coa_saldo($coa, $start_date, $end_date)
    {
        $balance     = 0;
        $totalDebit  = 0;
        $totalCredit = 0;

        $totalDebit  = $coa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'D')->whereIn('phase', ['opr', 'int', 'acm'])->sum('value');
        $totalCredit = $coa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'K')->whereIn('phase', ['opr', 'int', 'acm'])->sum('value');

        if ($coa->toCoaBody->toCoaClasification->normal_balance == 'D') {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }

        return $balance;
    }
}

if (!function_exists('_sum_coa_saldo_real')) {
    function _sum_coa_saldo_real($coa, $start_date, $end_date)
    {
        $balance     = 0;
        $totalDebit  = 0;
        $totalCredit = 0;

        $totalDebit  = $coa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'D')->whereIn('phase', ['opr', 'int', 'acm'])->sum('value');
        $totalCredit = $coa->toGeneralLedger->whereBetween('date', [$start_date, $end_date])->where('type', 'K')->whereIn('phase', ['opr', 'int', 'acm'])->sum('value');

        if ($coa->toCoaBody->toCoaClasification->normal_balance == 'D') {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }

        return $balance;
    }
}

if (!function_exists('_sum_pendapatan_beban')) {
    function _sum_pendapatan_beban($start_date, $end_date, $phase)
    {
        // Ambil semua CoA dengan klasifikasi 'beban' dan 'pendapatan'
        $coas = Coa::whereHas('toCoaBody.toCoaClasification', function ($query) {
            $query->whereIn('group', ['beban', 'pendapatan']);
        })
            ->with([
                'toGeneralLedger' => function ($query) use ($start_date, $end_date) {
                    $query->whereBetween('date', [$start_date, $end_date])->whereIn('phase', ['opr', 'int', 'acm']);
                },
                'toCoaBody.toCoaClasification'
            ])
            ->get();

        // Pisahkan saldo beban dan pendapatan dalam satu loop
        $saldo_beban = 0;
        $saldo_pendapatan = 0;

        foreach ($coas as $coa) {
            $generalLedgers = $coa->toGeneralLedger;
            $normal_balance = $coa->toCoaBody->toCoaClasification->normal_balance ?? 'D';
            $group = $coa->toCoaBody->toCoaClasification->group;

            $debit = $generalLedgers->where('type', 'D')->sum('value');
            $credit = $generalLedgers->where('type', 'K')->sum('value');

            $saldo = $normal_balance === 'D' ? $debit - $credit : $credit - $debit;

            if ($group === 'beban') {
                $saldo_beban += $saldo;
            } elseif ($group === 'pendapatan') {
                $saldo_pendapatan += $saldo;
            }
        }

        // Hitung balance akhir
        $balance = $saldo_pendapatan - $saldo_beban;

        return $balance;
    }
}

if (!function_exists('_numberToWords')) {
    function _numberToWords($lang, $number)
    {
        $number = abs($number);

        if ($lang == 'id') {
            return _terbilang($number);
        } else {
            return _terbilang_en($number);
        }
    }
}

if (!function_exists('_terbilang')) {
    function _terbilang($number)
    {
        $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

        if ($number < 12) {
            return $huruf[$number];
        } elseif ($number < 20) {
            return _terbilang($number - 10) . " Belas";
        } elseif ($number < 100) {
            return _terbilang(floor($number / 10)) . " Puluh " . _terbilang($number % 10);
        } elseif ($number < 200) {
            return "Seratus " . _terbilang($number - 100);
        } elseif ($number < 1000) {
            return _terbilang(floor($number / 100)) . " Ratus " . _terbilang($number % 100);
        } elseif ($number < 2000) {
            return "Seribu " . _terbilang($number - 1000);
        } elseif ($number < 1000000) {
            return _terbilang(floor($number / 1000)) . " Ribu " . _terbilang($number % 1000);
        } elseif ($number < 1000000000) {
            return _terbilang(floor($number / 1000000)) . " Juta " . _terbilang($number % 1000000);
        } elseif ($number < 1000000000000) {
            return _terbilang(floor($number / 1000000000)) . " Miliar " . _terbilang($number % 1000000000);
        } elseif ($number < 1000000000000000) {
            return _terbilang(floor($number / 1000000000000)) . " Triliun " . _terbilang($number % 1000000000000);
        } else {
            return "angka terlalu besar";
        }
    }
}

if (!function_exists('_terbilang_en')) {
    function _terbilang_en($number)
    {
        $words = [
            "",
            "One",
            "Two",
            "Three",
            "Four",
            "Five",
            "Six",
            "Seven",
            "Eight",
            "Nine",
            "Ten",
            "Eleven",
            "Twelve",
            "Thirteen",
            "Fourteen",
            "Fifteen",
            "Sixteen",
            "Seventeen",
            "Eighteen",
            "Nineteen"
        ];

        $tens = [
            "",
            "",
            "Twenty",
            "Thirty",
            "Forty",
            "Fifty",
            "Sixty",
            "Seventy",
            "Eighty",
            "Ninety"
        ];

        if ($number < 20) {
            return $words[$number];
        } elseif ($number < 100) {
            return $tens[intval($number / 10)] . ($number % 10 ? "-" . strtolower($words[$number % 10]) : "");
        } elseif ($number < 1000) {
            return $words[intval($number / 100)] . " Hundred" . ($number % 100 ? " and " . _terbilang_en($number % 100) : "");
        } elseif ($number < 1000000) {
            return _terbilang_en(intval($number / 1000)) . " Thousand" . ($number % 1000 ? " " . _terbilang_en($number % 1000) : "");
        } elseif ($number < 1000000000) {
            return _terbilang_en(intval($number / 1000000)) . " Million" . ($number % 1000000 ? " " . _terbilang_en($number % 1000000) : "");
        } elseif ($number < 1000000000000) {
            return _terbilang_en(intval($number / 1000000000)) . " Billion" . ($number % 1000000000 ? " " . _terbilang_en($number % 1000000000) : "");
        } elseif ($number < 1000000000000000) {
            return _terbilang_en(intval($number / 1000000000000)) . " Trillion" . ($number % 1000000000000 ? " " . _terbilang_en($number % 1000000000000) : "");
        } else {
            return "number too large";
        }
    }
}

if (!function_exists('generateFinNumber')) {
    function generateFinNumber($table, $key, $kode): string
    {
        $year   = Carbon::now()->format('Y');
        $month  = angka_romawi(Carbon::now()->format('m'));
        $company_initial = get_arrangement('company_initial');

        // 0001/INV/SSP/KEU/III/2025
        // "{$formattedGlobal}/INV/{$company_initial}/FIN/{$month}/{$year}";
        $full_prefix = "/{$kode}/{$company_initial}/FIN/{$month}/{$year}";

        $last_global = DB::connection('finance')
            ->table($table)
            ->select(DB::raw("MAX(CAST(SUBSTRING_INDEX($key, '/', 1) AS UNSIGNED)) AS max_global"))
            ->whereRaw("RIGHT($key, LENGTH('$full_prefix')) = '$full_prefix'")
            ->whereYear('created_at', $year)
            ->first();
        $globalNumber = ($last_global->max_global ?? 0) + 1;

        $formattedGlobal = str_pad($globalNumber, 4, '0', STR_PAD_LEFT);

        return "{$formattedGlobal}/{$kode}/{$company_initial}/FIN/{$month}/{$year}";
    }
}

// untuk hitung nilai journal
if (!function_exists('_count_journal')) {
    function _count_journal($request, $transaction_number = null)
    {
        $journal = Journal::with([
            'toJournalSet.toCoa.toTaxCoa',
            'toJournalSet.toTaxRate',
            'toJournalSet' => function ($query) {
                $query->orderBy('serial_number', 'asc');
            },
        ])->find($request->id_journal);

        $count_journal = $journal->toJournalSet->count();

        $result = [];

        $transaction_tax = [];

        if ($count_journal > 2) {
            $get_journal = [];

            $another_journal = [];

            $journal_credit = [];

            $journal_debit = [];

            $journal_beban = [];

            $tax_default = TaxRate::find(get_arrangement('default_ppn'))->rate;

            // begin:: beban / biaya
            if (isset($request->dataBeban)) {
                if (count($request->dataBeban) > 0) {
                    foreach ($request->dataBeban as $key => $value) {
                        if (is_array($value)) {
                            $value = (object) $value; // Ubah array menjadi objek
                        }

                        /**
                         * diubah untuk kebutuhan data beban jika lebih dari 1, untuk menghitung dpp secara otomatis dan yang diinput adalah total invoice.
                         */
                        if ($request->in_ex_tax === 'n') {
                            $journal_beban[$value->coa] = [
                                'amount' => round($value->amount / (1 + ($tax_default / 100))),
                                'posisi' => $value->posisi
                            ];
                        } else {
                            $journal_beban[$value->coa] = [
                                'amount' => $value->amount,
                                'posisi' => $value->posisi
                            ];
                        }
                    }
                }
            }
            // begin:: beban / biaya

            // begin:: journal discount
            if (isset($request->discount)) {
                if ($request->discount > 0) {
                    if ($journal->category === 'penerimaan') {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => Coa::whereIdCoa(get_arrangement('receive_coa_discount'))->first()->coa,
                            'type'   => "D",
                            'piece'  => "y",
                            'ppn'    => "n",
                            'amount' => $request->discount
                        ];
                    } else {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => Coa::whereIdCoa(get_arrangement('expense_coa_discount'))->first()->coa,
                            'type'   => "K",
                            'piece'  => "y",
                            'ppn'    => "n",
                            'amount' => $request->discount
                        ];
                    }
                }
            }
            // end:: journal discount

            // begin:: journal deposit liability
            if (isset($request->deposit)) {
                if ($request->deposit == 'advance_payment') {
                    $get_journal[] = [
                        'rate'   => null,
                        'coa'    => Coa::whereIdCoa(get_arrangement('advance_liability_coa'))->first()->coa,
                        'type'   => "D",
                        'piece'  => "y",
                        'ppn'    => "n",
                        'amount' => $request->deposit_total
                    ];
                }
            }
            // end:: journal deposit liability

            // begin:: journal interface
            foreach ($journal->toJournalSet as $key => $value) {
                if ($value->toCoa->toTaxCoa) {
                    $transaction_tax[] = [
                        'id_coa'      => $value->toCoa->id_coa,
                        'id_tax'      => $value->toTaxRate->id_tax,
                        'id_tax_rate' => $value->toTaxRate->id_tax_rate,
                        'rate'        => $value->toTaxRate->rate
                    ];

                    if ($value->toCoa->toTaxCoa->toTax->category === 'ppn') {
                        if ($value->toTaxRate->rate != 0) {
                            $get_journal[] = [
                                'rate'   => ($value->toTaxRate->rate / 100),
                                'type'   => $value->type,
                                'coa'    => $value->toCoa->coa,
                                'piece'  => 'y',
                                'ppn'    => 'y',
                                'amount' => 0
                            ];
                        } else {
                            if ($value->toTaxRate->count === 'y') {
                                // jika category === ppn dan tax rate 0%  dan count === y / ppn dibebaskan
                                $another_journal[] = [
                                    'rate'   => ($value->toTaxRate->rate / 100),
                                    'type'   => $value->type,
                                    'coa'    => $value->toCoa->coa,
                                    'ppn'    => 'y',
                                    'amount' => 0
                                ];
                            } else {
                                // jika category === ppn dan tax rate 0%  dan count === n / ppn nol (Bu Cici)
                                $another_journal[] = [
                                    'rate'   => ($tax_default / 100),
                                    'type'   => $value->type,
                                    'coa'    => $value->toCoa->coa,
                                    'ppn'    => 'y',
                                    'amount' => 0
                                ];
                            }
                        }
                    } else {
                        $get_journal[] = [
                            'rate'   => ($value->toTaxRate->rate / 100),
                            'type'   => $value->type,
                            'coa'    => $value->toCoa->coa,
                            'piece'  => 'y',
                            'ppn'    => 'n',
                            'amount' => 0
                        ];
                    }
                } else {
                    if (isset($journal_beban[$value->toCoa->coa])) {
                        $get_journal[] = [
                            'rate'   => null,
                            'type'   => $value->type,
                            'coa'    => $value->toCoa->coa,
                            'piece'  => 'y',
                            'ppn'    => 'n',
                            'amount' => $journal_beban[$value->toCoa->coa]['amount']
                        ];
                    } else {
                        $get_journal[] = [
                            'rate'   => null,
                            'type'   => $value->type,
                            'coa'    => $value->toCoa->coa,
                            'piece'  => "n",
                            'ppn'    => "n",
                            'amount' => 0
                        ];
                    }
                }
            }
            // end:: journal interface

            $dpp   = 0;
            $total = 0;

            // begin:: untuk check include, exclude or no (y, n, o)
            if (isset($request->in_ex_tax)) {
                if ($request->in_ex_tax === 'y') {
                    $total += $request->total;

                    $dpp += $request->total;

                    foreach ($get_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $total += $request->total * $value['rate'];
                        }
                    }

                    foreach ($another_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $total += $request->total * $value['rate'];
                        }
                    }

                    /**
                     * terus terang saya tidak diberi tahu saya tidak tahu dan saya bahkan bertanya tanya kenapa kok saya tidak diberi tahu.
                     */
                    // if (isset($request->dataBeban)) {
                    //     if (count($request->dataBeban) > 0) {
                    //         foreach ($request->dataBeban as $key => $value) {
                    //             if (is_array($value)) {
                    //                 $value = (object) $value; // Ubah array menjadi objek
                    //             }

                    //             $total += $value->amount;
                    //         }
                    //     }
                    // }
                } else if ($request->in_ex_tax === 'n') {
                    foreach ($get_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $dpp += round($request->total / (1 + $value['rate']), 2);

                            $total += round($request->total / (1 + $value['rate']), 2);
                        }
                    }

                    foreach ($another_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $dpp += round($request->total / (1 + $value['rate']), 2);

                            $total += round($request->total / (1 + $value['rate']), 2);
                        }
                    }

                    foreach ($get_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $total += $total * $value['rate'];
                        }
                    }

                    foreach ($another_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $total += $total * $value['rate'];
                        }
                    }

                    /**
                     * terus terang saya tidak diberi tahu saya tidak tahu dan saya bahkan bertanya tanya kenapa kok saya tidak diberi tahu.
                     */
                    // if (isset($request->dataBeban)) {
                    //     if (count($request->dataBeban) > 0) {
                    //         foreach ($request->dataBeban as $key => $value) {
                    //             if (is_array($value)) {
                    //                 $value = (object) $value; // Ubah array menjadi objek
                    //             }

                    //             $total += $value->amount;
                    //         }
                    //     }
                    // }
                } else {
                    $total += $request->total;

                    $dpp += $request->total;
                }
            }

            $total = round($total);

            $debit  = [];
            $credit = [];

            foreach ($get_journal as $key => $value) {
                $amount = ($value['amount'] <= 0) ? floor(($dpp * $value['rate'])) : $value['amount'];

                if ($value['type'] === 'K') {
                    $journal_credit[] = [
                        'type'  => $value['type'],
                        'coa'   => $value['coa'],
                    ];

                    if ($value['piece'] === 'y') {
                        $credit[$key] = $amount;
                    } else {
                        $credit[$key] = $value['amount'];
                    }
                } else {
                    $journal_debit[] = [
                        'type'  => $value['type'],
                        'coa'   => $value['coa'],
                    ];

                    if ($value['piece'] === 'y') {
                        $debit[$key] = $amount;
                    } else {
                        $debit[$key] = $value['amount'];
                    }
                }
            }

            $get_debit  = [];
            $get_credit = [];

            foreach ($debit as $key => $value) {
                if (count($debit) > 1) {
                    if ($value <= 0) {
                        $value = remainder($debit, $total);

                        $calculated = '1';
                    } else {
                        $value = $value;

                        $calculated = '0';
                    }
                } else {
                    if ($request->in_ex_tax === 'y') {
                        $value = $total;
                    } else {
                        $value = $request->total;
                    }

                    $calculated = '1';
                }

                $get_debit[$key] = [
                    'value'      => $value,
                    'calculated' => $calculated
                ];
            }

            foreach ($credit as $key => $value) {
                if (count($credit) > 1) {
                    if ($value <= 0) {
                        $value = remainder($credit, $total);

                        $calculated = '1';
                    } else {
                        $value = $value;

                        $calculated = '0';
                    }
                } else {
                    if ($request->in_ex_tax === 'y') {
                        $value = $total;
                    } else {
                        $value = $request->total;
                    }

                    $calculated = '1';
                }

                $get_credit[$key] = [
                    'value'      => $value,
                    'calculated' => $calculated
                ];
            }

            $another_credit = [];
            $another_debit = [];

            foreach ($another_journal as $key => $val) {
                if ($val['type'] === 'K') {
                    $journal_credit[] = [
                        'type'  => $val['type'],
                        'coa'   => $val['coa'],
                    ];

                    $another_credit[] = [
                        'value'      => $val['amount'],
                        'calculated' => '0'
                    ];
                } else {
                    $journal_debit[] = [
                        'type'  => $val['type'],
                        'coa'   => $val['coa'],
                    ];

                    $another_debit[] = [
                        'value'      => $val['amount'],
                        'calculated' => '0'
                    ];
                }
            }

            $last_debit  = array_merge($get_debit, $another_debit);
            $last_credit = array_merge($get_credit, $another_credit);

            $journal_debit_sort  = array_values($journal_debit);
            $journal_credit_sort = array_values($journal_credit);

            foreach ($journal_debit_sort as $key => $val) {
                $result[] = [
                    'coa'        => $val['coa'],
                    'type'       => $val['type'],
                    'value'      => $last_debit[$key]['value'],
                    'calculated' => $last_debit[$key]['calculated'],
                ];
            }

            foreach ($journal_credit_sort as $key => $val) {
                $result[] = [
                    'coa'        => $val['coa'],
                    'type'       => $val['type'],
                    'value'      => $last_credit[$key]['value'],
                    'calculated' => $last_credit[$key]['calculated'],
                ];
            }
        } else {
            foreach ($journal->toJournalSet as $key => $val) {
                $result[] = [
                    'coa'        => $val->toCoa->coa,
                    'type'       => $val->type,
                    'value'      => $request->total,
                    'calculated' => '0',
                ];
            }
        }

        $list_debit = [];
        $list_credit = [];

        foreach ($result as $key => $val) {
            if ($val['type'] === 'D') {
                $list_debit[] = $val['value'];
            } else {
                $list_credit[] = $val['value'];
            }
        }

        $sum_debit  = array_sum($list_debit);
        $sum_credit = array_sum($list_credit);
        $balance    = ($sum_debit - $sum_credit);

        if ($balance != 0) {
            return false;
        } else {
            if (count($transaction_tax) != 0) {
                $check_transaction_tax = TransactionTax::whereTransactionNumber($transaction_number)->get()->count();

                if ($check_transaction_tax > 0) {
                    TransactionTax::whereTransactionNumber($transaction_number)->delete();
                }

                foreach ($transaction_tax as $key => $val) {
                    $transaction_tax[$key]['transaction_number'] = $transaction_number;
                }

                TransactionTax::insert($transaction_tax);
            }

            return $result;
        }
    }
}

// untuk insert general ledger
if (!function_exists('insert_general_ledger')) {
    function insert_general_ledger($data, $transaction_number, $reference_number)
    {
        foreach ($data as $key => $value) {
            $generalLedger                      = new GeneralLedger();
            $generalLedger->id_journal          = $value['id_journal'];
            $generalLedger->transaction_number  = $transaction_number;
            $generalLedger->date                = $value['date'];
            $generalLedger->coa                 = $value['coa'];
            $generalLedger->type                = $value['type'];
            $generalLedger->value               = $value['value'];
            $generalLedger->description         = $value['description'] . ' - ' . $reference_number;
            $generalLedger->reference_number    = $reference_number;
            $generalLedger->phase               = $value['phase'];
            $generalLedger->calculated          = $value['calculated'];
            $generalLedger->save();
        }
    }
}

// untuk insert transaction
if (!function_exists('insert_transaction')) {
    function insert_transaction($data, $transaction_number, $reference_number)
    {
        $transaction                      = new Transaction();
        $transaction->id_kontak           = $data->id_kontak;
        $transaction->id_journal          = $data->id_journal;
        $transaction->id_transaction_name = $data->id_transaction_name;
        $transaction->transaction_number  = $transaction_number;
        $transaction->from_or_to          = $data->from_or_to;
        $transaction->description         = $data->description . ' - ' . $reference_number;
        $transaction->date                = $data->date;
        $transaction->reference_number    = $reference_number;
        $transaction->value               = $data->value;
        $transaction->save();

        return $transaction;
    }
}

// untuk insert transaction full
if (!function_exists('insert_transaction_full')) {
    function insert_transaction_full($data, $transaction_number)
    {
        $transaction_full                     = new TransactionFull();
        $transaction_full->id_kontak          = $data->id_kontak;
        $transaction_full->id_journal         = $data->id_journal;
        $transaction_full->transaction_number = $transaction_number;
        $transaction_full->invoice_number     = $data->invoice_number;
        $transaction_full->efaktur_number     = $data->efaktur_number;
        $transaction_full->date               = $data->date;
        $transaction_full->from_or_to         = $data->from_or_to;
        $transaction_full->description        = $data->description;
        $transaction_full->category           = $data->category;
        $transaction_full->record_type        = $data->record_type;
        $transaction_full->in_ex              = $data->in_ex_tax;
        $transaction_full->value              = $data->total;

        if (isset($data->attachment)) {
            $attachment                   = add_file($data->attachment, 'transaction_full/');
            $transaction_full->attachment = $attachment;
        }

        $transaction_full->save();

        return $transaction_full;
    }
}

// untuk insert receipt
if (!function_exists('insert_receipt')) {
    function insert_receipt($data, $transaction_number, $reference_number)
    {
        $receipt                     = new Receipts();
        $receipt->id_kontak          = $data->id_kontak;
        $receipt->id_journal         = $data->id_journal;
        $receipt->transaction_number = $transaction_number;
        $receipt->date               = $data->date;
        $receipt->receive_from       = $data->from_or_to;
        $receipt->pay_type           = $data->pay_type;
        $receipt->record_type        = $data->record_type;
        $receipt->description        = $data->description . ' - ' . $reference_number;
        $receipt->reference_number   = $reference_number;
        $receipt->in_ex              = $data->in_ex_tax;
        $receipt->value              = $data->total;
        $receipt->save();
    }
}

// untuk insert expenditure
if (!function_exists('insert_expenditure')) {
    function insert_expenditure($data, $transaction_number, $reference_number)
    {
        $expenditure                     = new Expenditure();
        $expenditure->id_kontak          = $data->id_kontak;
        $expenditure->id_journal         = $data->id_journal;
        $expenditure->transaction_number = $transaction_number;
        $expenditure->date               = $data->date;
        $expenditure->outgoing_to        = $data->from_or_to;
        $expenditure->pay_type           = $data->pay_type;
        $expenditure->record_type        = $data->record_type;
        $expenditure->description        = $data->description . ' - ' . $reference_number;
        $expenditure->reference_number   = $reference_number;
        $expenditure->in_ex              = $data->in_ex_tax;
        $expenditure->value              = $data->total;
        $expenditure->save();
    }
}

// melakukan journal automatis standar
// if (!function_exists('_journal_automatic')) {
//     function _journal_automatic($transaction_number, $request, $reference_number)
//     {
//         $journal = Journal::with([
//             'toJournalSet' => function ($query) {
//                 $query->orderBy('serial_number', 'asc');
//             }
//         ])->find($request->id_journal);

//         $count_journal = $journal->toJournalSet->count();

//         $data = [];

//         if ($count_journal > 2) {
//             $get_journal = [];

//             $another_journal = [];

//             $journal_credit = [];

//             $journal_debit = [];

//             $journal_beban = [];

//             $tax_default = TaxRate::find(get_arrangement('default_ppn'))->rate;

//             if (isset($request->dataBeban)) {
//                 if (count($request->dataBeban) > 0) {
//                     foreach ($request->dataBeban as $key => $value) {
//                         if (is_array($value)) {
//                             $value = (object) $value; // Ubah array menjadi objek
//                         }

//                         /**
//                          * diubah untuk kebutuhan data beban jika lebih dari 1, untuk menghitung dpp secara otomatis dan yang diinput adalah total invoice.
//                          */
//                         if ($request->in_ex_tax === 'n') {
//                             $journal_beban[$value->coa] = [
//                                 'amount' => round($value->amount / (1 + ($tax_default / 100))),
//                                 'posisi' => $value->posisi
//                             ];
//                         } else {
//                             $journal_beban[$value->coa] = [
//                                 'amount' => $value->amount,
//                                 'posisi' => $value->posisi
//                             ];
//                         }
//                     }
//                 }
//             }

//             foreach ($journal->toJournalSet as $key => $value) {
//                 if ($value->toCoa->toTaxCoa) {
//                     if ($value->toCoa->toTaxCoa->toTax->category === 'ppn') {
//                         if ($value->toTaxRate->rate != 0) {
//                             $get_journal[] = [
//                                 'rate'  => ($value->toTaxRate->rate / 100),
//                                 'type'  => $value->type,
//                                 'coa'   => $value->toCoa->coa,
//                                 'piece' => 'y',
//                                 'ppn'   => 'y',
//                                 'amount' => 0
//                             ];
//                         } else {
//                             if ($value->toTaxRate->count === 'y') {
//                                 // jika category === ppn dan tax rate 0%  dan count === y / ppn dibebaskan
//                                 $another_journal[] = [
//                                     'rate'   => ($value->toTaxRate->rate / 100),
//                                     'type'   => $value->type,
//                                     'coa'    => $value->toCoa->coa,
//                                     'ppn'    => 'y',
//                                     'amount' => 0
//                                 ];
//                             } else {
//                                 // jika category === ppn dan tax rate 0%  dan count === n / ppn nol (Bu Cici)
//                                 $another_journal[] = [
//                                     'rate'   => ($tax_default / 100),
//                                     'type'   => $value->type,
//                                     'coa'    => $value->toCoa->coa,
//                                     'ppn'    => 'y',
//                                     'amount' => 0
//                                 ];
//                             }
//                         }
//                     } else {
//                         $get_journal[] = [
//                             'rate'  => ($value->toTaxRate->rate / 100),
//                             'type'  => $value->type,
//                             'coa'   => $value->toCoa->coa,
//                             'piece' => 'y',
//                             'ppn'   => 'n',
//                             'amount' => 0
//                         ];
//                     }
//                 } else {
//                     if (isset($journal_beban[$value->toCoa->coa])) {
//                         $get_journal[] = [
//                             'rate'   => null,
//                             'type'   => $value->type,
//                             'coa'    => $value->toCoa->coa,
//                             'piece'  => 'y',
//                             'ppn'    => 'n',
//                             'amount' => $journal_beban[$value->toCoa->coa]['amount']
//                         ];
//                     } else {
//                         $get_journal[] = [
//                             'rate'   => null,
//                             'type'   => $value->type,
//                             'coa'    => $value->toCoa->coa,
//                             'piece'  => "n",
//                             'ppn'    => "n",
//                             'amount' => 0
//                         ];
//                     }
//                 }
//             }

//             // begin:: untuk check include, exclude or no (y, n, o)
//             $dpp   = 0;
//             $total = 0;

//             if ($request->in_ex_tax === 'y') {
//                 $total += $request->total;

//                 $dpp += $request->total;

//                 foreach ($get_journal as $key => $value) {
//                     if ($value['ppn'] === 'y') {
//                         $total += $request->total * $value['rate'];
//                     }
//                 }

//                 foreach ($another_journal as $key => $value) {
//                     if ($value['ppn'] === 'y') {
//                         $total += $request->total * $value['rate'];
//                     }
//                 }

//                 /**
//                  * terus terang saya tidak diberi tahu saya tidak tahu dan saya bahkan bertanya tanya kenapa kok saya tidak diberi tahu.
//                  */
//                 // if (isset($request->dataBeban)) {
//                 //     if (count($request->dataBeban) > 0) {
//                 //         foreach ($request->dataBeban as $key => $value) {
//                 //             if (is_array($value)) {
//                 //                 $value = (object) $value; // Ubah array menjadi objek
//                 //             }

//                 //             $total += $value->amount;
//                 //         }
//                 //     }
//                 // }
//             } else if ($request->in_ex_tax === 'n') {
//                 foreach ($get_journal as $key => $value) {
//                     if ($value['ppn'] === 'y') {
//                         $dpp += round($request->total / (1 + $value['rate']), 2);

//                         $total += round($request->total / (1 + $value['rate']), 2);
//                     }
//                 }

//                 foreach ($another_journal as $key => $value) {
//                     if ($value['ppn'] === 'y') {
//                         $dpp += round($request->total / (1 + $value['rate']), 2);

//                         $total += round($request->total / (1 + $value['rate']), 2);
//                     }
//                 }

//                 foreach ($get_journal as $key => $value) {
//                     if ($value['ppn'] === 'y') {
//                         $total += $total * $value['rate'];
//                     }
//                 }

//                 foreach ($another_journal as $key => $value) {
//                     if ($value['ppn'] === 'y') {
//                         $total += $total * $value['rate'];
//                     }
//                 }

//                 /**
//                  * terus terang saya tidak diberi tahu saya tidak tahu dan saya bahkan bertanya tanya kenapa kok saya tidak diberi tahu.
//                  */
//                 // if (isset($request->dataBeban)) {
//                 //     if (count($request->dataBeban) > 0) {
//                 //         foreach ($request->dataBeban as $key => $value) {
//                 //             if (is_array($value)) {
//                 //                 $value = (object) $value; // Ubah array menjadi objek
//                 //             }

//                 //             $total += $value->amount;
//                 //         }
//                 //     }
//                 // }
//             } else {
//                 $total += $request->total;

//                 $dpp += $request->total;
//             }
//             // end:: untuk check include, exclude or no (y, n, o)

//             $total = round($total);

//             $debit  = [];
//             $credit = [];

//             foreach ($get_journal as $key => $value) {
//                 $amount = ($value['amount'] <= 0) ? floor(($dpp * $value['rate'])) : $value['amount'];

//                 if ($value['type'] === 'K') {
//                     $journal_credit[] = [
//                         'type'  => $value['type'],
//                         'coa'   => $value['coa'],
//                     ];

//                     if ($value['piece'] === 'y') {
//                         $credit[$key] = $amount;
//                     } else {
//                         $credit[$key] = $value['amount'];
//                     }
//                 } else {
//                     $journal_debit[] = [
//                         'type'  => $value['type'],
//                         'coa'   => $value['coa'],
//                     ];

//                     if ($value['piece'] === 'y') {
//                         $debit[$key] = $amount;
//                     } else {
//                         $debit[$key] = $value['amount'];
//                     }
//                 }
//             }

//             $get_debit  = [];
//             $get_credit = [];

//             foreach ($debit as $key => $value) {
//                 if (count($debit) > 1) {
//                     if ($value <= 0) {
//                         $value = remainder($debit, $total);

//                         $calculated = '1';
//                     } else {
//                         $value = $value;

//                         $calculated = '0';
//                     }
//                 } else {
//                     if ($request->in_ex_tax === 'y') {
//                         $value = $total;
//                     } else {
//                         $value = $request->total;
//                     }

//                     $calculated = '1';
//                 }

//                 $get_debit[$key] = [
//                     'value'      => $value,
//                     'calculated' => $calculated
//                 ];
//             }

//             foreach ($credit as $key => $value) {
//                 if (count($credit) > 1) {
//                     if ($value <= 0) {
//                         $value = remainder($credit, $total);

//                         $calculated = '1';
//                     } else {
//                         $value = $value;

//                         $calculated = '0';
//                     }
//                 } else {
//                     if ($request->in_ex_tax === 'y') {
//                         $value = $total;
//                     } else {
//                         $value = $request->total;
//                     }

//                     $calculated = '1';
//                 }

//                 $get_credit[$key] = [
//                     'value'      => $value,
//                     'calculated' => $calculated
//                 ];
//             }

//             $another_credit = [];
//             $another_debit  = [];

//             foreach ($another_journal as $key => $val) {
//                 if ($val['type'] === 'K') {
//                     $journal_credit[] = [
//                         'type'  => $val['type'],
//                         'coa'   => $val['coa'],
//                     ];

//                     $another_credit[] = [
//                         'value'      => $val['amount'],
//                         'calculated' => '0'
//                     ];
//                 } else {
//                     $journal_debit[] = [
//                         'type'  => $val['type'],
//                         'coa'   => $val['coa'],
//                     ];

//                     $another_debit[] = [
//                         'value'      => $val['amount'],
//                         'calculated' => '0'
//                     ];
//                 }
//             }

//             $last_debit  = array_merge($get_debit, $another_debit);
//             $last_credit = array_merge($get_credit, $another_credit);

//             $journal_debit_sort  = array_values($journal_debit);
//             $journal_credit_sort = array_values($journal_credit);

//             foreach ($journal_debit_sort as $key => $val) {
//                 $data[] = [
//                     'id_journal'         => $request->id_journal,
//                     'transaction_number' => $transaction_number,
//                     'date'               => $request->date,
//                     'coa'                => $val['coa'],
//                     'type'               => $val['type'],
//                     'value'              => $last_debit[$key]['value'],
//                     'description'        => $request->description . ' - ' . $reference_number,
//                     'reference_number'   => $reference_number,
//                     'phase'              => 'opr',
//                     'calculated'         => $last_debit[$key]['calculated'],
//                     'created_by'         => auth('api')->user()->id_users
//                 ];
//             }

//             foreach ($journal_credit_sort as $key => $val) {
//                 $data[] = [
//                     'id_journal'         => $request->id_journal,
//                     'transaction_number' => $transaction_number,
//                     'date'               => $request->date,
//                     'coa'                => $val['coa'],
//                     'type'               => $val['type'],
//                     'value'              => $last_credit[$key]['value'],
//                     'description'        => $request->description . ' - ' . $reference_number,
//                     'reference_number'   => $reference_number,
//                     'phase'              => 'opr',
//                     'calculated'         => $last_credit[$key]['calculated'],
//                     'created_by'         => auth('api')->user()->id_users
//                 ];
//             }

//             $sum_debit  = array_sum(array_column($get_debit, 'value'));
//             $sum_credit = array_sum(array_column($get_credit, 'value'));
//             $balance    = floor($sum_debit) - floor($sum_credit);

//             if ($balance != 0) {
//                 return false;
//             } else {
//                 GeneralLedger::insert($data);

//                 return true;
//             }
//         } else {
//             foreach ($journal->toJournalSet as $key => $value) {
//                 $data[] = [
//                     'id_journal'         => $request->id_journal,
//                     'transaction_number' => $transaction_number,
//                     'date'               => $request->date,
//                     'coa'                => $value->toCoa->coa,
//                     'type'               => $value->type,
//                     'value'              => $request->total,
//                     'description'        => $request->description . ' - ' . $reference_number,
//                     'reference_number'   => $reference_number,
//                     'phase'              => 'opr',
//                     'created_by'         => auth('api')->user()->id_users
//                 ];
//             }

//             GeneralLedger::insert($data);

//             return true;
//         }
//     }
// }