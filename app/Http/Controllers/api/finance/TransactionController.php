<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\TransactionRequest;
use App\Http\Resources\finance\TransactionResource;
use App\Models\finance\AssetHead;
use App\Models\finance\AssetItem;
use App\Models\finance\Coa;
use App\Models\finance\Expenditure;
use App\Models\finance\GeneralLedger;
use App\Models\finance\Journal;
use App\Models\finance\Receipts;
use App\Models\finance\TaxRate;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class TransactionController extends Controller
{
    /**
     * @OA\Get(
     *  path="/transactions",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="status",
     *      in="query",
     *      description="Status",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     *
     * @OA\Get(
     *  path="/transactions/{type}",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"penerimaan", "pengeluaran"}
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="canceled",
     *      in="query",
     *      description="Canceled",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request, $type = null)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $query = Transaction::query();

        $query->whereBetweenMonth($start_date, $end_date);

        $query->whereHas('toTransactionName', function ($query) use ($type) {
            if ($type) {
                $query->where('category', $type);
            }
        });

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('date', 'asc')->get();

        return ApiResponseClass::sendResponse(TransactionResource::collection($data), 'Transaction Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/transactions/filter/{id_transaction_name}",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Parameter(
     *      name="id_transaction_name",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function filter($id_transaction_name)
    {
        $data = Transaction::with([
            'toTransactionTerm' => function ($query) {
                $query->whereNull('id_receipt');
            },
        ])->where('id_transaction_name', $id_transaction_name)->where('status', 'valid')->orderBy('id_transaction', 'desc')->get();

        $result = [];
        foreach ($data as $key => $value) {
            $category = $value->toTransactionName->category;
            $terbayar = 0;

            if ($category === 'penerimaan') {
                $terbayar = $value->toReceipts->where('status', 'valid')->sum('value');
            }

            if ($category === 'pengeluaran') {
                $terbayar = $value->toExpenditure->where('status', 'valid')->sum('value');
            }

            $sisa = ($value->value - $terbayar);

            if ($sisa !== 0) {
                $result[] = [
                    'id_transaction'       => $value->id_transaction,
                    'id_kontak'            => $value->id_kontak,
                    'id_journal'           => $value->id_journal,
                    'id_transaction_name'  => $value->id_transaction_name,
                    'transaction_category' => $value->toTransactionName->category,
                    'journal'              => $value->toJournal->name,
                    'transaction_name'     => $value->toTransactionName->name,
                    'transaction_number'   => $value->transaction_number,
                    'reference_number'     => $value->reference_number,
                    'from_or_to'           => $value->from_or_to,
                    'description'          => $value->description,
                    'date'                 => $value->date,
                    'value'                => (int) $value->value,
                    'terbayar'             => (int) $terbayar,
                    'sisa'                 => (int) $sisa,
                    'transaction_term'     => $value->toTransactionTerm,
                ];
            }
        }

        return ApiResponseClass::sendResponse($result, 'Transaction Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/transactions/details",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Parameter(
     *      name="ref",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function details(Request $request)
    {
        $request->validate([
            'ref' => 'required|string',
        ]);

        $reference_number = $request->ref;

        if (!$reference_number) {
            return ApiResponseClass::throw('Ref is required', 400);
        }

        $transaction = Transaction::where('reference_number', $reference_number)->where('status', 'valid')->orderBy('date', 'desc')->get();

        $result = [];
        foreach ($transaction as $key => $value) {
            $detail   = [];
            $no       = 0;
            $detail[] = [
                'no'                 => $no,
                'id_transaction'     => $value->id_transaction,
                'transaction_number' => $value->transaction_number,
                'description'        => $value->description,
                'date'               => $value->date,
                'value'              => (int) $value->value,
            ];
            $no++;

            if ($value->toReceipts) {
                foreach ($value->toReceipts->where('status', 'valid')->sortBy('date') as $key => $val) {
                    $detail[] = [
                        'no'                 => $no++,
                        'id_transaction'     => '',
                        'id_receipt'         => $val->id_receipt,
                        'journal'            => $val->toJournal->name,
                        'reference_number'   => $val->reference_number,
                        'transaction_number' => $val->transaction_number,
                        'date'               => $val->date,
                        'receive_from'       => $val->receive_from,
                        'pay_type'           => Config::get('constants.pay_type')[$val->pay_type],
                        'value'              => $val->value,
                        'description'        => $val->description,
                        'canceled'           => $val->canceled,
                    ];
                }
            }

            if ($value->toExpenditure) {
                foreach ($value->toExpenditure->where('status', 'valid')->sortBy('date') as $key => $val) {
                    $detail[] = [
                        'no'                 => $no++,
                        'id_transaction'     => '',
                        'id_expenditure'     => $val->id_expenditure,
                        'journal'            => $val->toJournal->name,
                        'reference_number'   => $val->reference_number,
                        'transaction_number' => $val->transaction_number,
                        'date'               => $val->date,
                        'outgoing_to'        => $val->outgoing_to,
                        'pay_type'           => Config::get('constants.pay_type')[$val->pay_type],
                        'value'              => $val->value,
                        'description'        => $val->description,
                        'canceled'           => $val->canceled,
                    ];
                }
            }

            $result = [
                'id_transaction'      => $value->id_transaction,
                'id_kontak'           => $value->id_kontak,
                'id_journal'          => $value->id_journal,
                'id_transaction_name' => $value->id_transaction_name,
                'journal'             => $value->toJournal->name,
                'transaction_name'    => $value->toTransactionName->name,
                'transaction_number'  => $value->transaction_number,
                'reference_number'    => $value->reference_number,
                'from_or_to'          => $value->from_or_to,
                'description'         => $value->description,
                'date'                => $value->date,
                'value'               => (int) $value->value,
                'canceled'            => $value->canceled,
                'detail'              => $detail
            ];
        }

        return ApiResponseClass::sendResponse($result, 'Transaction Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/transactions",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_journal",
     *                  type="integer",
     *                  description="Journal ID"
     *              ),
     *              @OA\Property(
     *                  property="id_kontak",
     *                  type="string",
     *                  description="From or to"
     *              ),
     *              @OA\Property(
     *                  property="id_transaction_name",
     *                  type="integer",
     *                  description="Id transaction name"
     *              ),
     *              @OA\Property(
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference Number"
     *              ),
     *              @OA\Property(
     *                  property="from_or_to",
     *                  type="string",
     *                  description="From or to"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="text",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Total"
     *              ),
     *              @OA\Property(
     *                  property="in_ex_tax",
     *                  type="string",
     *                  description="In or Ex Tax"
     *              ),
     *              example={
     *                  "id_kontak": 1,
     *                  "id_journal": 1,
     *                  "id_transaction_name": 1,
     *                  "reference_number": "INV-001",
     *                  "from_or_to": "Naruto",
     *                  "description": "Transaction description",
     *                  "date": "2022-01-01",
     *                  "total": 3000,
     *                  "in_ex_tax": "n"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     *
     * @OA\Post(
     *  path="/transactions/dp",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     *
     * @OA\Post(
     *  path="/transactions/asset",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     *
     * @OA\Post(
     *  path="/transactions/dp_asset",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TransactionRequest $request, $payment = 'no')
    {
        // $transaction_number = generate_number('finance', 'transaction', 'transaction_number', 'INV');
        $transaction_number = generateFinNumber('transaction', 'transaction_number', 'INV');

        if ($payment === 'no') {
            return $this->no($request, $transaction_number);
        }

        if ($payment === 'dp') {
            $category = $request->category;

            return $this->dp($request, $transaction_number, $category);
        }

        if ($payment === 'asset') {
            return $this->asset($request, $transaction_number);
        }

        if ($payment === 'dp_asset') {
            $category = $request->category;

            return $this->dp_asset($request, $transaction_number, $category);
        }
    }

    public function no($request, $transaction_number)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $journal = Journal::with([
                'toJournalSet' => function ($query) {
                    $query->orderBy('serial_number', 'asc');
                },
                'toJournalSet.toCoa.toTaxCoa',
                'toJournalSet.toTaxRate',
            ])->find($request->id_journal);

            $get_journal = [];

            $another_journal = [];

            $transaction_tax = [];

            $journal_beban = [];

            $journal_debit = [];

            $journal_credit = [];

            if ($request->has('dataBeban') && is_array($request->dataBeban)) {
                $converted = collect($request->dataBeban)->map(fn($row) => (object) $row)->toArray();
                $request->merge(['dataBeban' => $converted]);
            }

            if (isset($request->dataBeban)) {
                if (count($request->dataBeban) > 0) {
                    foreach ($request->dataBeban as $key => $value) {
                        $journal_beban[$value->coa] = [
                            'amount' => $value->amount,
                            'posisi' => $value->posisi
                        ];
                    }
                }
            }

            // begin:: journal discount
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
            // end:: journal discount

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
                            $another_journal[] = [
                                'rate'   => (TaxRate::take(1)->first()->rate / 100),
                                'type'   => $value->type,
                                'coa'    => $value->toCoa->coa,
                                'ppn'    => 'y',
                                'amount' => 0
                            ];
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

            $count_journal = $journal->toJournalSet->count();

            $data = [];

            if ($count_journal > 2) {
                $dpp   = 0;
                $total = 0;

                if ($request->in_ex_tax === 'y') {
                    $total += $request->total;

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

                    if (count($request->dataBeban) > 0) {
                        foreach ($request->dataBeban as $key => $value) {
                            $total += $value->amount;
                        }
                    }
                } else {
                    $total += $request->total;

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

                    if (count($request->dataBeban) > 0) {
                        foreach ($request->dataBeban as $key => $value) {
                            $total += $value->amount;
                        }
                    }
                }

                $debit  = [];
                $credit = [];

                foreach ($get_journal as $key => $value) {
                    $amount = 0;
                    if ($value['ppn'] === 'y' && $request->in_ex_tax === 'n') {
                        $amount = ($value['amount'] <= 0) ? round(($dpp * $value['rate']), 2) : $value['amount'];
                    } else {
                        $amount = ($value['amount'] <= 0) ? round(($request->total * $value['rate']), 2) : $value['amount'];
                    }

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
                        $value = $total;

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
                        $value = $total;

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

                foreach ($last_debit as $key => $val) {
                    $data[] = [
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $request->date,
                        'coa'                => $journal_debit[$key]['coa'],
                        'type'               => $journal_debit[$key]['type'],
                        'value'              => $val['value'],
                        'description'        => $request->description . ' - ' . $request->reference_number,
                        'reference_number'   => $request->reference_number,
                        'phase'              => 'opr',
                        'calculated'         => $val['calculated'],
                        'created_by'         => auth('api')->user()->id_users
                    ];
                }

                foreach ($last_credit as $key => $val) {
                    $data[] = [
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $request->date,
                        'coa'                => $journal_credit[$key]['coa'],
                        'type'               => $journal_credit[$key]['type'],
                        'value'              => $val['value'],
                        'description'        => $request->description . ' - ' . $request->reference_number,
                        'reference_number'   => $request->reference_number,
                        'phase'              => 'opr',
                        'calculated'         => $val['calculated'],
                        'created_by'         => auth('api')->user()->id_users
                    ];
                }

                $sum_debit  = array_sum(array_column($get_debit, 'value'));
                $sum_credit = array_sum(array_column($get_credit, 'value'));
                $balance    = ($sum_debit - $sum_credit);
                $value      = $sum_debit;

                if ($balance != 0) {
                    return Response::json(['success ' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
                }
            } else {
                foreach ($get_journal as $key => $val) {
                    $data[] = [
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $request->date,
                        'coa'                => $val['coa'],
                        'type'               => $val['type'],
                        'value'              => $request->total,
                        'description'        => $request->description . ' - ' . $request->reference_number,
                        'reference_number'   => $request->reference_number,
                        'phase'              => 'opr',
                        'created_by'         => auth('api')->user()->id_users
                    ];

                    $value = $request->total;
                }
            }

            $transaction                      = new Transaction();
            $transaction->id_kontak           = $request->id_kontak;
            $transaction->id_journal          = $request->id_journal;
            $transaction->id_transaction_name = $request->id_transaction_name;
            $transaction->transaction_number  = $transaction_number;
            $transaction->from_or_to          = $request->from_or_to;
            $transaction->description         = $request->description;
            $transaction->date                = $request->date;
            $transaction->reference_number    = $request->reference_number;
            $transaction->value               = $value;
            $transaction->save();

            if (count($transaction_tax) != 0) {
                foreach ($transaction_tax as $key => $val) {
                    $transaction_tax[$key]['transaction_number'] = $transaction_number;
                }
                TransactionTax::insert($transaction_tax);
            }

            GeneralLedger::insert($data);

            ActivityLogHelper::log('finance:transaction_create', 1, [
                'date'                     => $transaction->date,
                'finance:reference_number' => $transaction->reference_number,
                'total'                    => $transaction->value,
                'finance:from_or_to'       => $transaction->from_or_to
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Transaction Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function dp($request, $transaction_number, $category)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction = (object) $request->transaction[0];
            $payment     = (object) $request->payment[0];

            if (is_array($transaction->dataBeban)) {
                $converted = collect($transaction->dataBeban)->map(fn($row) => (object) $row)->toArray();
                $transaction->dataBeban = $converted;
            }

            if ($payment->total === 0) {
                return Response::json(['success ' => false, 'message' => 'Invalid Amount'], 400);
            } else {
                $journal_transaction = Journal::with([
                    'toJournalSet' => function ($query) {
                        $query->orderBy('serial_number', 'asc');
                    },
                    'toJournalSet.toCoa.toTaxCoa',
                    'toJournalSet.toTaxRate',
                ])->find($transaction->id_journal);

                $journal_payment = Journal::with([
                    'toJournalSet' => function ($query) {
                        $query->orderBy('serial_number', 'asc');
                    },
                ])->find($payment->id_journal);

                $get_journal = [];

                $another_journal = [];

                $fil_journal = [];

                $journal_beban = [];

                $transaction_tax = [];

                $journal_credit = [];

                $journal_debit = [];

                if (count($transaction->dataBeban) > 0) {
                    foreach ($transaction->dataBeban as $key => $value) {
                        $journal_beban[$value->coa] = [
                            'amount' => $value->amount,
                            'posisi' => $value->posisi
                        ];
                    }
                }

                // begin:: journal discount
                if ($transaction->discount > 0) {
                    if ($journal_transaction->category === 'penerimaan') {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => Coa::whereIdCoa(get_arrangement('receive_coa_discount'))->first()->coa,
                            'type'   => "D",
                            'piece'  => "y",
                            'ppn'    => "n",
                            'amount' => $transaction->discount
                        ];
                    } else {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => Coa::whereIdCoa(get_arrangement('expense_coa_discount'))->first()->coa,
                            'type'   => "K",
                            'piece'  => "y",
                            'ppn'    => "n",
                            'amount' => $transaction->discount
                        ];
                    }
                }
                // end:: journal discount

                // begin:: journal transaction
                foreach ($journal_transaction->toJournalSet as $key => $value) {
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
                                    'coa'    => $value->toCoa->coa,
                                    'type'   => $value->type,
                                    'piece'  => 'y',
                                    'ppn'    => 'y',
                                    'amount' => 0
                                ];
                            } else {
                                $another_journal[] = [
                                    'rate'   => (TaxRate::take(1)->first()->rate / 100),
                                    'type'   => $value->type,
                                    'coa'    => $value->toCoa->coa,
                                    'ppn'    => 'y',
                                    'amount' => 0
                                ];
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

                    $fil_journal[] = [
                        'coa'    => $value->toCoa->coa,
                        'type'   => $value->type,
                        'amount' => $transaction->total
                    ];
                }
                // end:: journal transaction

                // begin:: journal payment
                foreach ($journal_payment->toJournalSet as $key => $value) {
                    $fil_journal[] = [
                        'coa'    => $value->toCoa->coa,
                        'type'   => $value->type,
                        'amount' => $payment->total
                    ];

                    $get_journal[] = [
                        'rate'   => null,
                        'coa'    => $value->toCoa->coa,
                        'type'   => $value->type,
                        'piece'  => "n",
                        'ppn'    => "n",
                        'amount' => $payment->total,
                    ];
                }
                // end:: journal payment

                $dpp   = 0;
                $total = 0;
                if ($transaction->in_ex_tax === 'y') {
                    $total += $transaction->total;

                    foreach ($get_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $total += $transaction->total * $value['rate'];
                        }
                    }

                    foreach ($another_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $total += $transaction->total * $value['rate'];
                        }
                    }

                    if (count($transaction->dataBeban) > 0) {
                        foreach ($transaction->dataBeban as $key => $value) {
                            $total += $value->amount;
                        }
                    }
                } else {
                    foreach ($get_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $dpp += round($transaction->total / (1 + $value['rate']), 2);

                            $total += round($transaction->total / (1 + $value['rate']), 2);
                        }
                    }

                    foreach ($another_journal as $key => $value) {
                        if ($value['ppn'] === 'y') {
                            $dpp += round($transaction->total / (1 + $value['rate']), 2);

                            $total += round($transaction->total / (1 + $value['rate']), 2);
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

                    if (count($transaction->dataBeban) > 0) {
                        foreach ($transaction->dataBeban as $key => $value) {
                            $total += $value->amount;
                        }
                    }
                }

                $debit  = [];
                $credit = [];
                foreach ($get_journal as $key => $value) {
                    if ($value['type'] === 'K') {
                        $journal_credit[] = [
                            'type'  => $value['type'],
                            'coa'   => $value['coa'],
                        ];

                        $credit[$key] = [
                            'rate'   => $value['rate'],
                            'coa'    => $value['coa'],
                            'type'   => $value['type'],
                            'piece'  => $value['piece'],
                            'ppn'    => $value['ppn'],
                            'amount' => $value['amount'],
                        ];
                    } else {
                        $journal_debit[] = [
                            'type'  => $value['type'],
                            'coa'   => $value['coa'],
                        ];

                        $debit[$key] = [
                            'rate'   => $value['rate'],
                            'coa'    => $value['coa'],
                            'type'   => $value['type'],
                            'piece'  => $value['piece'],
                            'ppn'    => $value['ppn'],
                            'amount' => $value['amount'],
                        ];
                    }
                }

                $f_debit  = [];
                $f_credit = [];
                foreach ($fil_journal as $key => $value) {
                    if ($value['type'] === 'K') {
                        $f_credit[$key] = [
                            'coa'    => $value['coa'],
                            'type'   => $value['type'],
                            'amount' => $value['amount'],
                        ];
                    } else {
                        $f_debit[$key] = [
                            'coa'    => $value['coa'],
                            'type'   => $value['type'],
                            'amount' => $value['amount'],
                        ];
                    }
                }

                $arr1 = array_column($f_debit, 'coa', 'coa');
                $arr2 = array_column($f_credit, 'coa', 'coa');

                foreach ($arr1 as $key => $val) {
                    $find = array_search($val, $arr2);
                    if ($find !== false)
                        $result[$find] = $find;
                }

                if (empty($result)) {
                    return Response::json(['success' => false, 'message' => 'Coa Pair Mismatch!'], 400);
                } else {
                    $g_debit  = [];
                    $g_credit = [];

                    foreach ($result as $key => $val) {
                        $r_debit = array_filter($f_debit, function ($subarray) use ($val) {
                            return isset($subarray['coa']) && $subarray['coa'] == $val;
                        });

                        $r_credit = array_filter($f_credit, function ($subarray) use ($val) {
                            return isset($subarray['coa']) && $subarray['coa'] == $val;
                        });

                        $g_debit[]  = array_shift($r_debit);

                        $g_credit[] = array_shift($r_credit);
                    }

                    $filter = [];
                    for ($i = 0; $i < count($result); $i++) {
                        if ($g_debit[$i]['coa'] == $g_credit[$i]['coa']) {
                            $value = $g_debit[$i]['amount'] - $g_credit[$i]['amount'];

                            if ($value < 0) {
                                $filter[] = [
                                    'rate'  => null,
                                    'coa'   => $g_debit[$i]['coa'],
                                    'type'  => 'K',
                                    'piece' => 'n',
                                    'ppn'   => "n",
                                    'value' => 0,
                                ];
                            } else {
                                $filter[] = [
                                    'rate'  => null,
                                    'coa'   => $g_debit[$i]['coa'],
                                    'type'  => 'D',
                                    'piece' => 'n',
                                    'ppn'   => "n",
                                    'value' => 0,
                                ];
                            }
                        }
                    }

                    foreach ($filter as $key => $value) {
                        foreach ($debit as $key2 => $value2) {
                            if ($value['coa'] == $value2['coa']) {
                                unset($debit[$key2]);
                            }
                        }

                        foreach ($journal_debit as $key2 => $value2) {
                            if ($value['coa'] == $value2['coa']) {
                                unset($journal_debit[$key2]);
                            }
                        }

                        foreach ($credit as $key2 => $value2) {
                            if ($value['coa'] == $value2['coa']) {
                                unset($credit[$key2]);
                            }
                        }

                        foreach ($journal_credit as $key2 => $value2) {
                            if ($value['coa'] == $value2['coa']) {
                                unset($journal_credit[$key2]);
                            }
                        }
                    }

                    $q_debit  = [];
                    $q_credit = [];

                    foreach ($debit as $key => $value) {
                        $amount = 0;
                        if ($value['ppn'] === 'y' && $transaction->in_ex_tax === 'n') {
                            $amount = ($value['amount'] <= 0) ? round(($dpp * $value['rate']), 2) : $value['amount'];
                        } else {
                            $amount = ($value['amount'] <= 0) ? round(($transaction->total * $value['rate']), 2) : $value['amount'];
                        }

                        if ($value['piece'] === 'y') {
                            $q_debit[$key] = $amount;
                        } else {
                            $q_debit[$key] = $value['amount'];
                        }
                    }

                    foreach ($credit as $key => $value) {
                        $amount = 0;
                        if ($value['ppn'] === 'y' && $transaction->in_ex_tax === 'n') {
                            $amount = ($value['amount'] <= 0) ? round(($dpp * $value['rate']), 2) : $value['amount'];
                        } else {
                            $amount = ($value['amount'] <= 0) ? round(($transaction->total * $value['rate']), 2) : $value['amount'];
                        }

                        if ($value['piece'] === 'y') {
                            $q_credit[$key] = $amount;
                        } else {
                            $q_credit[$key] = $value['amount'];
                        }
                    }

                    foreach ($filter as $key => $value) {
                        if ($value['type'] == 'K') {
                            $journal_credit[] = [
                                'type'  => $value['type'],
                                'coa'   => $value['coa'],
                            ];

                            $q_credit[] = $value['value'];
                        } else {
                            $journal_debit[] = [
                                'type'  => $value['type'],
                                'coa'   => $value['coa'],
                            ];

                            $q_debit[] = $value['value'];
                        }
                    }

                    $get_debit  = [];
                    $get_credit = [];

                    foreach ($q_debit as $key => $value) {
                        if (count($q_debit) > 1) {
                            if ($value <= 0) {
                                $value = remainder($q_debit, $total);

                                $calculated = '1';
                            } else {
                                $value = $value;

                                $calculated = '0';
                            }
                        } else {
                            if ($transaction->in_ex_tax === 'y') {
                                $value = $total;
                            } else {
                                $value = $transaction->total;
                            }

                            $calculated = '1';
                        }

                        $get_debit[$key] = [
                            'value'      => $value,
                            'calculated' => $calculated
                        ];
                    }

                    foreach ($q_credit as $key => $value) {
                        if (count($q_credit) > 1) {
                            if ($value <= 0) {
                                $value = remainder($q_credit, $total);

                                $calculated = '1';
                            } else {
                                $value = $value;

                                $calculated = '0';
                            }
                        } else {
                            if ($transaction->in_ex_tax === 'y') {
                                $value = $total;
                            } else {
                                $value = $transaction->total;
                            }

                            $calculated = '1';
                        }

                        $get_credit[$key] = [
                            'value'      => $value,
                            'calculated' => $calculated
                        ];
                    }

                    $another_credit = [];
                    $another_debit  = [];

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

                    foreach (array_values($journal_debit) as $key => $val) {
                        $data[] = [
                            'transaction_number' => $transaction_number,
                            'date'               => $transaction->date,
                            'coa'                => $val['coa'],
                            'type'               => $val['type'],
                            'value'              => $last_debit[$key]['value'],
                            'description'        => $transaction->description . ' - ' . $transaction_number,
                            'reference_number'   => $transaction->reference_number,
                            'phase'              => 'opr',
                            'calculated'         => $last_debit[$key]['calculated'],
                            'created_by'         => auth('api')->user()->id_users
                        ];
                    }

                    foreach (array_values($journal_credit) as $key => $val) {
                        $data[] = [
                            'transaction_number' => $transaction_number,
                            'date'               => $transaction->date,
                            'coa'                => $val['coa'],
                            'type'               => $val['type'],
                            'value'              => $last_credit[$key]['value'],
                            'description'        => $transaction->description . ' - ' . $transaction_number,
                            'reference_number'   => $transaction->reference_number,
                            'phase'              => 'opr',
                            'calculated'         => $last_credit[$key]['calculated'],
                            'created_by'         => auth('api')->user()->id_users
                        ];
                    }

                    $sum_debit  = array_sum(array_column($get_debit, 'value'));
                    $sum_credit = array_sum(array_column($get_credit, 'value'));
                    $balance    = ($sum_debit - $sum_credit);
                    $value      = $sum_debit;

                    if ($balance != 0) {
                        return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
                    } else {
                        $transaksi                      = new Transaction();
                        $transaksi->id_kontak           = $transaction->id_kontak;
                        $transaksi->id_journal          = $transaction->id_journal;
                        $transaksi->id_transaction_name = $transaction->id_transaction_name;
                        $transaksi->transaction_number  = $transaction_number;
                        $transaksi->from_or_to          = $transaction->from_or_to;
                        $transaksi->description         = $transaction->description;
                        $transaksi->date                = $transaction->date;
                        $transaksi->reference_number    = $transaction->reference_number;
                        $transaksi->value               = $value;
                        $transaksi->save();

                        if (count($transaction_tax) != 0) {
                            foreach ($transaction_tax as $key => $val) {
                                $transaction_tax[$key]['transaction_number'] = $transaction_number;
                            }
                            TransactionTax::insert($transaction_tax);
                        }

                        if ($category === 'receipt') {
                            $transaction_number = generate_number('finance', 'receipts', 'transaction_number', 'PNM');

                            $receipt                     = new Receipts();
                            $receipt->id_kontak          = $payment->id_kontak;
                            $receipt->id_journal         = $payment->id_journal;
                            $receipt->transaction_number = $transaction_number;
                            $receipt->date               = $payment->date;
                            $receipt->receive_from       = $payment->receive_from;
                            $receipt->pay_type           = $payment->pay_type;
                            $receipt->record_type        = $payment->record_type;
                            $receipt->description        = "DP " . $payment->reference_number . ' || ' . $payment->description;
                            $receipt->reference_number   = $payment->reference_number;
                            $receipt->value              = $payment->total;
                            $receipt->save();
                        } else {
                            $transaction_number = generate_number('finance', 'expenditures', 'transaction_number', 'BKU');

                            $expenditure = new Expenditure();
                            $expenditure->id_kontak          = $payment->id_kontak;
                            $expenditure->id_journal         = $payment->id_journal;
                            $expenditure->transaction_number = $transaction_number;
                            $expenditure->date               = $payment->date;
                            $expenditure->outgoing_to        = $payment->outgoing_to;
                            $expenditure->pay_type           = $payment->pay_type;
                            $expenditure->record_type        = $payment->record_type;
                            $expenditure->description        = "DP " . $payment->reference_number . ' || ' . $payment->description;
                            $expenditure->reference_number   = $payment->reference_number;
                            $expenditure->value              = $payment->total;
                            $expenditure->save();
                        }

                        GeneralLedger::insert($data);
                    }

                    ActivityLogHelper::log('finance:transaction_create', 1, [
                        'date'                     => $transaction->date,
                        'finance:reference_number' => $transaction->reference_number,
                        'total'                    => $transaction->value,
                        'finance:from_or_to'       => $transaction->from_or_to
                    ]);

                    DB::connection('finance')->commit();
                }

                return ApiResponseClass::sendResponse($data, 'Transaction Created Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function asset($request, $transaction_number)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $journal = Journal::with([
                'toJournalSet' => function ($query) {
                    $query->orderBy('serial_number', 'asc');
                },
                'toJournalSet.toCoa.toTaxCoa',
                'toJournalSet.toTaxRate',
            ])->find($request->id_journal);

            $get_journal     = [];
            $transaction_tax = [];

            // begin:: journal discount
            if ($request->discount > 0) {
                if ($journal->category === 'penerimaan') {
                    $get_journal[] = [
                        'rate'   => null,
                        'coa'    => Coa::whereIdCoa(get_arrangement('receive_coa_discount'))->first()->coa,
                        'type'   => "D",
                        'piece'  => "y",
                        'amount' => $request->discount
                    ];
                } else {
                    $get_journal[] = [
                        'rate'   => null,
                        'coa'    => Coa::whereIdCoa(get_arrangement('expense_coa_discount'))->first()->coa,
                        'type'   => "K",
                        'piece'  => "y",
                        'amount' => $request->discount
                    ];
                }
            }
            // begin:: journal discount

            // begin:: journal asset
            $asset = AssetHead::with(['toAssetCoa.toCoa'])->where('id_asset_category', $request->id_asset_category)->first();
            if ($asset) {
                if ($journal->category === 'penerimaan') {
                    $get_journal[] = [
                        'rate'   => null,
                        'coa'    => $asset->toAssetCoa->toCoa->coa,
                        'type'   => "K",
                        'piece'  => "y",
                        'amount' => $request->asset_value
                    ];
                } else {
                    $get_journal[] = [
                        'rate'   => null,
                        'coa'    => $asset->toAssetCoa->toCoa->coa,
                        'type'   => "D",
                        'piece'  => "y",
                        'amount' => $request->asset_value
                    ];
                }
            }
            // begin:: journal asset

            // begin:: journal interface
            foreach ($journal->toJournalSet as $key => $value) {
                if ($value->toCoa->toTaxCoa) {
                    $transaction_tax[] = [
                        'id_coa'      => $value->toCoa->id_coa,
                        'id_tax'      => $value->toTaxRate->id_tax,
                        'id_tax_rate' => $value->toTaxRate->id_tax_rate,
                        'rate'        => $value->toTaxRate->rate
                    ];

                    $get_journal[] = [
                        'rate'   => ($value->toTaxRate->rate / 100),
                        'coa'    => $value->toCoa->coa,
                        'type'   => $value->type,
                        'piece'  => 'y',
                        'amount' => 0
                    ];
                } else {
                    $get_journal[] = [
                        'rate'   => null,
                        'coa'    => $value->toCoa->coa,
                        'type'   => $value->type,
                        'piece'  => "n",
                        'amount' => 0
                    ];
                }
            }
            // end:: journal interface

            $count_journal = count($get_journal);

            $data = [];

            if ($count_journal > 2) {
                $debit       = [];
                $credit      = [];

                foreach ($get_journal as $key => $value) {
                    if ($value['type'] === 'K') {
                        if ($value['piece'] === 'y') {
                            $amount = ($value['amount'] <= 0) ? round(($request->total * $value['rate']), 2) : $value['amount'];

                            $credit[$key] = $amount;
                        } else {
                            $credit[$key] = $value['amount'];
                        }
                    } else {
                        if ($value['piece'] === 'y') {
                            $amount = ($value['amount'] <= 0) ? round(($request->total * $value['rate']), 2) : $value['amount'];

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
                            $value = remainder($debit, $request->total);

                            $calculated = '1';
                        } else {
                            $value = $value;

                            $calculated = '0';
                        }
                    } else {
                        $value = $request->total;

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
                            $value = remainder($credit, $request->total);

                            $calculated = '1';
                        } else {
                            $value = $value;

                            $calculated = '0';
                        }
                    } else {
                        $value = $request->total;

                        $calculated = '1';
                    }

                    $get_credit[$key] = [
                        'value'      => $value,
                        'calculated' => $calculated
                    ];
                }

                foreach ($get_debit as $key => $val) {
                    $data[] = [
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $request->date,
                        'coa'                => $get_journal[$key]['coa'],
                        'type'               => $get_journal[$key]['type'],
                        'value'              => $val['value'],
                        'description'        => $request->description . ' - ' . $request->reference_number,
                        'reference_number'   => $transaction_number,
                        'phase'              => 'opr',
                        'calculated'         => $val['calculated'],
                        'created_by'         => auth('api')->user()->id_users
                    ];
                }

                foreach ($get_credit as $key => $val) {
                    $data[] = [
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $request->date,
                        'coa'                => $get_journal[$key]['coa'],
                        'type'               => $get_journal[$key]['type'],
                        'value'              => $val['value'],
                        'description'        => $request->description . ' - ' . $request->reference_number,
                        'reference_number'   => $transaction_number,
                        'phase'              => 'opr',
                        'calculated'         => $val['calculated'],
                        'created_by'         => auth('api')->user()->id_users
                    ];
                }

                $sum_debit  = array_sum(array_column($debit, 'value'));
                $sum_credit = array_sum(array_column($credit, 'value'));
                $balance    = ($sum_debit - $sum_credit);
                $value      = $request->total;

                if ($balance != 0) {
                    return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
                }
            } else {
                foreach ($get_journal as $key => $val) {
                    $data[] = [
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $request->date,
                        'coa'                => $val['coa'],
                        'type'               => $val['type'],
                        'value'              => $request->total,
                        'description'        => $request->description . ' - ' . $request->reference_number,
                        'reference_number'   => $transaction_number,
                        'phase'              => 'opr',
                        'created_by'         => auth('api')->user()->id_users
                    ];

                    $value = $request->total;
                }
            }

            $transaction                      = new Transaction();
            $transaction->id_kontak           = $request->id_kontak;
            $transaction->id_journal          = $request->id_journal;
            $transaction->id_transaction_name = $request->id_transaction_name;
            $transaction->transaction_number  = $transaction_number;
            $transaction->from_or_to          = $request->from_or_to;
            $transaction->description         = $request->description;
            $transaction->date                = $request->date;
            $transaction->reference_number    = $request->reference_number;
            $transaction->value               = $value;
            $transaction->save();

            if (count($transaction_tax) != 0) {
                foreach ($transaction_tax as $key => $val) {
                    $transaction_tax[$key]['transaction_number'] = $transaction_number;
                }
                TransactionTax::insert($transaction_tax);
            }

            GeneralLedger::insert($data);

            if ($journal->category === 'penerimaan') {
                AssetItem::where('id_asset_item', $request->id_asset_item)->update([
                    'disposal' => '1'
                ]);
            }

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Transaction Created Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    public function dp_asset($request, $transaction_number, $category)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction = $request->transaction[0];
            $payment     = $request->payment[0];

            if ($payment->total === 0) {
                return Response::json(['success ' => false, 'message' => 'Invalid Amount'], 400);
            } else {
                $journal_transaction = Journal::with([
                    'toJournalSet' => function ($query) {
                        $query->orderBy('serial_number', 'asc');
                    },
                    'toJournalSet.toCoa.toTaxCoa',
                    'toJournalSet.toTaxRate',
                ])->find($transaction->id_journal);

                $journal_payment = Journal::with([
                    'toJournalSet' => function ($query) {
                        $query->orderBy('serial_number', 'asc');
                    },
                ])->find($payment->id_journal);

                $get_journal     = [];
                $fil_journal     = [];
                $transaction_tax = [];

                // begin:: journal discount
                if ($transaction->discount > 0) {
                    if ($journal_transaction->category === 'penerimaan') {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => Coa::whereIdCoa(get_arrangement('receive_coa_discount'))->first()->coa,
                            'type'   => "D",
                            'piece'  => "y",
                            'amount' => $transaction->discount
                        ];
                    } else {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => Coa::whereIdCoa(get_arrangement('expense_coa_discount'))->first()->coa,
                            'type'   => "K",
                            'piece'  => "y",
                            'amount' => $transaction->discount
                        ];
                    }
                }
                // end:: journal discount

                // begin:: journal asset
                $asset = AssetHead::with(['toAssetCoa.toCoa'])->where('id_asset_category', $transaction->id_asset_category)->first();
                if ($asset) {
                    if ($journal_transaction->category === 'penerimaan') {
                        $transaction_tax[] = [
                            'id_coa'      => $value->toCoa->id_coa,
                            'id_tax'      => $value->toTaxRate->id_tax,
                            'id_tax_rate' => $value->toTaxRate->id_tax_rate,
                            'rate'        => $value->toTaxRate->rate
                        ];

                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => $asset->toAssetCoa->toCoa->coa,
                            'type'   => "K",
                            'piece'  => "y",
                            'amount' => $transaction->asset_value
                        ];
                    } else {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => $asset->toAssetCoa->toCoa->coa,
                            'type'   => "D",
                            'piece'  => "y",
                            'amount' => $transaction->asset_value
                        ];
                    }
                }
                // end:: journal asset

                // begin:: journal transaction
                foreach ($journal_transaction->toJournalSet as $key => $value) {
                    $fil_journal[] = [
                        'coa'    => $value->toCoa->coa,
                        'type'   => $value->type,
                        'amount' => $transaction->total
                    ];

                    if ($value->toCoa->toTaxCoa) {
                        $get_journal[] = [
                            'rate'   => ($value->toTaxRate->rate / 100),
                            'coa'    => $value->toCoa->coa,
                            'type'   => $value->type,
                            'piece'  => 'y',
                            'amount' => 0
                        ];
                    } else {
                        $get_journal[] = [
                            'rate'   => null,
                            'coa'    => $value->toCoa->coa,
                            'type'   => $value->type,
                            'piece'  => "n",
                            'amount' => 0
                        ];
                    }
                }
                // end:: journal transaction

                // begin:: journal payment
                foreach ($journal_payment->toJournalSet as $key => $value) {
                    $fil_journal[] = [
                        'coa'    => $value->toCoa->coa,
                        'type'   => $value->type,
                        'amount' => $payment->total
                    ];

                    $get_journal[] = [
                        'rate'   => null,
                        'coa'    => $value->toCoa->coa,
                        'type'   => $value->type,
                        'piece'  => "n",
                        'amount' => $payment->total,
                    ];
                }
                // begin:: journal payment

                $debit  = [];
                $credit = [];

                foreach ($get_journal as $key => $value) {
                    if ($value['type'] === 'K') {
                        $credit[$key] = [
                            'rate'   => $value['rate'],
                            'coa'    => $value['coa'],
                            'type'   => $value['type'],
                            'piece'  => $value['piece'],
                            'amount' => $value['amount'],
                        ];
                    } else {
                        $debit[$key] = [
                            'rate'   => $value['rate'],
                            'coa'    => $value['coa'],
                            'type'   => $value['type'],
                            'piece'  => $value['piece'],
                            'amount' => $value['amount'],
                        ];
                    }
                }

                $f_debit  = [];
                $f_credit = [];
                foreach ($fil_journal as $key => $value) {
                    if ($value['type'] === 'K') {
                        $f_credit[$key] = [
                            'coa'    => $value['coa'],
                            'type'   => $value['type'],
                            'amount' => $value['amount'],
                        ];
                    } else {
                        $f_debit[$key] = [
                            'coa'    => $value['coa'],
                            'type'   => $value['type'],
                            'amount' => $value['amount'],
                        ];
                    }
                }

                $arr1 = array_column($f_debit, 'coa', 'coa');
                $arr2 = array_column($f_credit, 'coa', 'coa');

                foreach ($arr1 as $key => $val) {
                    $find = array_search($val, $arr2);
                    if ($find !== false)
                        $result[$find] = $find;
                }

                if (empty($result)) {
                    return Response::json(['success ' => false, 'message' => 'Coa Pair Mismatch!'], 400);
                } else {
                    $g_debit  = [];
                    $g_credit = [];

                    foreach ($result as $key => $val) {
                        $r_debit = array_filter($f_debit, function ($subarray) use ($val) {
                            return isset($subarray['coa']) && $subarray['coa'] == $val;
                        });

                        $r_credit = array_filter($f_credit, function ($subarray) use ($val) {
                            return isset($subarray['coa']) && $subarray['coa'] == $val;
                        });

                        $g_debit[]  = array_shift($r_debit);

                        $g_credit[] = array_shift($r_credit);
                    }

                    $filter = [];
                    for ($i = 0; $i < count($result); $i++) {
                        if ($g_debit[$i]['coa'] == $g_credit[$i]['coa']) {
                            $value = $g_debit[$i]['amount'] - $g_credit[$i]['amount'];

                            if ($value < 0) {
                                $filter[] = [
                                    'rate'  => null,
                                    'coa'   => $g_debit[$i]['coa'],
                                    'type'  => 'K',
                                    'piece' => 'n',
                                    'value' => 0,
                                ];
                            } else {
                                $filter[] = [
                                    'rate'  => null,
                                    'coa'   => $g_debit[$i]['coa'],
                                    'type'  => 'D',
                                    'piece' => 'n',
                                    'value' => 0,
                                ];
                            }
                        }
                    }

                    foreach ($filter as $key => $value) {
                        foreach ($debit as $key2 => $value2) {
                            if ($value['coa'] == $value2['coa']) {
                                unset($debit[$key2]);
                            }
                        }

                        foreach ($credit as $key2 => $value2) {
                            if ($value['coa'] == $value2['coa']) {
                                unset($credit[$key2]);
                            }
                        }
                    }

                    $q_debit = [];
                    $q_credit = [];

                    foreach ($debit as $key => $value) {
                        if ($value['piece'] === 'y') {
                            $amount = ($value['amount'] <= 0) ? round(($transaction->total * $value['rate']), 2) : $value['amount'];

                            $q_debit[$key] = $amount;
                        } else {
                            $q_debit[$key] = $value['amount'];
                        }
                    }

                    foreach ($credit as $key => $value) {
                        if ($value['piece'] === 'y') {
                            $amount = ($value['amount'] <= 0) ? round(($transaction->total * $value['rate']), 2) : $value['amount'];

                            $q_credit[$key] = $amount;
                        } else {
                            $q_credit[$key] = $value['amount'];
                        }
                    }

                    foreach ($filter as $key => $value) {
                        if ($value['type'] == 'K') {
                            $q_credit[] = $value['value'];
                        } else {
                            $q_debit[] = $value['value'];
                        }
                    }

                    $get_debit  = [];
                    $get_credit = [];

                    foreach ($q_debit as $key => $value) {
                        if (count($q_debit) > 1) {
                            if ($value <= 0) {
                                $value = remainder($q_debit, $transaction->total);

                                $calculated = '1';
                            } else {
                                $value = $value;

                                $calculated = '0';
                            }
                        } else {
                            $value = $transaction->total;

                            $calculated = '1';
                        }

                        $get_debit[$key] = [
                            'value'      => $value,
                            'calculated' => $calculated
                        ];
                    }

                    foreach ($q_credit as $key => $value) {
                        if (count($q_credit) > 1) {
                            if ($value <= 0) {
                                $value = remainder($q_credit, $transaction->total);

                                $calculated = '1';
                            } else {
                                $value = $value;

                                $calculated = '0';
                            }
                        } else {
                            $value = $transaction->total;

                            $calculated = '1';
                        }

                        $get_credit[$key] = [
                            'value'      => $value,
                            'calculated' => $calculated
                        ];
                    }

                    foreach ($get_debit as $key => $val) {
                        if (empty($debit[$key])) {
                            foreach ($filter as $key2 => $val2) {
                                $data[] = [
                                    'transaction_number' => $transaction_number,
                                    'date'               => $transaction->date,
                                    'coa'                => $val2['coa'],
                                    'type'               => $val2['type'],
                                    'value'              => $val['value'],
                                    'description'        => $transaction->description . ' - ' . $transaction_number,
                                    'reference_number'   => $transaction->reference_number,
                                    'phase'              => 'opr',
                                    'calculated'         => $val['calculated'],
                                    'created_by'         => auth('api')->user()->id_users
                                ];
                            }
                        } else {
                            $data[] = [
                                'transaction_number' => $transaction_number,
                                'date'               => $transaction->date,
                                'coa'                => $debit[$key]['coa'],
                                'type'               => $debit[$key]['type'],
                                'value'              => $val['value'],
                                'description'        => $transaction->description . ' - ' . $transaction_number,
                                'reference_number'   => $transaction->reference_number,
                                'phase'              => 'opr',
                                'calculated'         => $val['calculated'],
                                'created_by'         => auth('api')->user()->id_users
                            ];
                        }
                    }

                    foreach ($get_credit as $key => $val) {
                        if (empty($credit[$key])) {
                            foreach ($filter as $key2 => $val2) {
                                $data[] = [
                                    'transaction_number' => $transaction_number,
                                    'date'               => $transaction->date,
                                    'coa'                => $val2['coa'],
                                    'type'               => $val2['type'],
                                    'value'              => $val['value'],
                                    'description'        => $transaction->description . ' - ' . $transaction_number,
                                    'reference_number'   => $transaction->reference_number,
                                    'phase'              => 'opr',
                                    'calculated'         => $val['calculated'],
                                    'created_by'         => auth('api')->user()->id_users
                                ];
                            }
                        } else {
                            $data[] = [
                                'transaction_number' => $transaction_number,
                                'date'               => $transaction->date,
                                'coa'                => $credit[$key]['coa'],
                                'type'               => $credit[$key]['type'],
                                'value'              => $val['value'],
                                'description'        => $transaction->description . ' - ' . $transaction_number,
                                'reference_number'   => $transaction->reference_number,
                                'phase'              => 'opr',
                                'calculated'         => $val['calculated'],
                                'created_by'         => auth('api')->user()->id_users
                            ];
                        }
                    }

                    $sum_debit  = array_sum(array_column($get_debit, 'value'));
                    $sum_credit = array_sum(array_column($get_credit, 'value'));
                    $balance    = ($sum_debit - $sum_credit);
                    $value      = $transaction->total;

                    if ($balance != 0) {
                        return ApiResponseClass::throw('Invalid Amount, Not Enough Balance', 400);
                    } else {
                        $transaksi                      = new Transaction();
                        $transaksi->id_kontak           = $transaction->id_kontak;
                        $transaksi->id_journal          = $transaction->id_journal;
                        $transaksi->id_transaction_name = $transaction->id_transaction_name;
                        $transaksi->transaction_number  = $transaction_number;
                        $transaksi->from_or_to          = $transaction->from_or_to;
                        $transaksi->description         = $transaction->description;
                        $transaksi->date                = $transaction->date;
                        $transaksi->reference_number    = $transaction->reference_number;
                        $transaksi->value               = $transaction->total;
                        $transaksi->save();

                        if (count($transaction_tax) != 0) {
                            foreach ($transaction_tax as $key => $val) {
                                $transaction_tax[$key]['transaction_number'] = $transaction_number;
                            }
                            TransactionTax::insert($transaction_tax);
                        }

                        if ($category === 'receipt') {
                            $transaction_number = generate_number('finance', 'receipts', 'transaction_number', 'PNM');

                            $receipt                     = new Receipts();
                            $receipt->id_kontak          = $payment->id_kontak;
                            $receipt->id_journal         = $payment->id_journal;
                            $receipt->transaction_number = $transaction_number;
                            $receipt->date               = $payment->date;
                            $receipt->receive_from       = $payment->receive_from;
                            $receipt->pay_type           = $payment->pay_type;
                            $receipt->record_type        = $payment->record_type;
                            $receipt->description        = "DP " . $payment->reference_number . ' || ' . $payment->description;
                            $receipt->reference_number   = $payment->reference_number;
                            $receipt->value              = $payment->total;
                            $receipt->save();

                            AssetItem::where('id_asset_item', $transaction->id_asset_item)->update([
                                'disposal' => '1'
                            ]);
                        } else {
                            $transaction_number = generate_number('finance', 'expenditures', 'transaction_number', 'BKU');

                            $expenditure = new Expenditure();
                            $expenditure->id_kontak          = $payment->id_kontak;
                            $expenditure->id_journal         = $payment->id_journal;
                            $expenditure->transaction_number = $transaction_number;
                            $expenditure->date               = $payment->date;
                            $expenditure->outgoing_to        = $payment->outgoing_to;
                            $expenditure->pay_type           = $payment->pay_type;
                            $expenditure->record_type        = $payment->record_type;
                            $expenditure->description        = "DP " . $payment->reference_number . ' || ' . $payment->description;
                            $expenditure->reference_number   = $payment->reference_number;
                            $expenditure->value              = $payment->total;
                            $expenditure->save();
                        }

                        GeneralLedger::insert($data);
                    }

                    DB::connection('finance')->commit();
                }

                return ApiResponseClass::sendResponse($data, 'Transaction Created Successfully');
            }
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/transactions",
     *  summary="Delete transaction",
     *  tags={"Finance - Transaction"},
     *  @OA\Parameter(
     *      name="transaction_number",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */

    public function destroy(Request $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = $request->transaction_number;

            $check_gl = GeneralLedger::where('transaction_number', $transaction_number)->where('closed', '1')->first();

            if ($check_gl) {
                return ApiResponseClass::throw('Transaction Already Closed !', 400);
            } else {
                $check_transaction = Transaction::where('transaction_number', $transaction_number)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                GeneralLedger::where('transaction_number', $transaction_number)->delete();

                ActivityLogHelper::log('finance:transaction_delete', 1, ['transaction_number' => $transaction_number]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_delete', 0, ['transaction_number' => $transaction_number]);
            return ApiResponseClass::rollback($e);
        }
    }
}
