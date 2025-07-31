<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\ReceiptsRequest;
use App\Http\Resources\finance\ReceiptResource;
use App\Models\finance\Coa;
use App\Models\finance\DownPayment;
use App\Models\finance\DownPaymentDetails;
use App\Models\finance\GeneralLedger;
use App\Models\finance\Liability;
use App\Models\finance\LiabilityDetail;
use App\Models\finance\Receipts;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionTerm;
use App\Models\operation\InvoiceFob;
use App\Models\operation\ShippingInstruction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ReceiptsController extends Controller
{
    /**
     * @OA\Get(
     *  path="/receipts",
     *  summary="Get the list of receipts",
     *  tags={"Finance - Receipts"},
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
     *  path="/receipts/{type}",
     *  summary="Get the list of receipts",
     *  tags={"Finance - Receipts"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"bank", "cash", "petty_cash"}
     *      ),
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

        $query = Receipts::query();

        $query->with(['toKontak']);

        $query->whereBetweenMonth($start_date, $end_date);

        if ($type) {
            $query->whereRecordType($type);
        }

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('date', 'asc')->get();

        return ApiResponseClass::sendResponse(ReceiptResource::collection($data), 'Receipts Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/receipts",
     *  summary="Create a new receipt",
     *  tags={"Finance - Receipts"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_kontak",
     *                  type="integer",
     *                  description="Kontak ID"
     *              ),
     *              @OA\Property(
     *                  property="id_journal",
     *                  type="integer",
     *                  description="Journal ID"
     *              ),
     *              @OA\Property(
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference Number"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Receipt Date"
     *              ),
     *              @OA\Property(
     *                  property="from_or_to",
     *                  type="string",
     *                  description="From or to"
     *              ),
     *              @OA\Property(
     *                  property="pay_type",
     *                  type="string",
     *                  description="Pay Type"
     *              ),
     *              @OA\Property(
     *                  property="record_type",
     *                  type="string",
     *                  description="Record Type"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Total"
     *              ),
     *              @OA\Property(
     *                  property="in_ex_tax",
     *                  type="string",
     *                  description="In or Ex Tax (y, n, or o)"
     *              ),
     *              @OA\Property(
     *                  property="deposit",
     *                  type="string",
     *                  description="Deposit"
     *              ),
     *              @OA\Property(
     *                  property="deposit_total",
     *                  type="integer",
     *                  description="Deposit Total"
     *              ),
     *              @OA\Property(
     *                  property="dataBeban",
     *                  type="array",
     *                  description="Data Beban",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="coa",
     *                          type="string",
     *                          description="COA"
     *                      ),
     *                      @OA\Property(
     *                          property="amount",
     *                          type="integer",
     *                          description="Amount"
     *                      ),
     *                      @OA\Property(
     *                          property="posisi",
     *                          type="string",
     *                          description="Posisi"
     *                      ),
     *                  ),
     *              ),
     *              example={
     *                  "id_kontak": 1,
     *                  "id_journal": 1,
     *                  "reference_number": "12345",
     *                  "date": "2022-01-01",
     *                  "receive_from": "John Doe",
     *                  "pay_type": "c",
     *                  "record_type": "bank",
     *                  "description": "Receipt description",
     *                  "total": 3000,
     *                  "in_ex_tax": "n",
     *                  "deposit": "down_payment",
     *                  "deposit_total": 1000,
     *                  "dataBeban": {{"coa": "D", "amount": 1000, "posisi": "y" }, {"coa": "D", "amount": 1000, "posisi": "y" }, {"coa": "D", "amount": 1000, "posisi": "y" }}
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(ReceiptsRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generateFinNumber('receipts', 'transaction_number', 'PNM');
            $transaction_number_dp = $transaction_number;

            if (isset($request->deposit)) {
                if ($request->deposit == 'down_payment') {
                    $result = $this->_count_down_payment($request, get_arrangement('down_payment_adjustment_journal'));
                } else if ($request->deposit == 'advance_payment') {
                    $result = $this->_count_advance_payment($request);
                }
            } else {
                $result = _count_journal($request, $transaction_number);
            }

            if ($result) {
                $general_ledger = [];

                foreach ($result as $key => $val) {
                    $general_ledger[] = [
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $request->date,
                        'coa'                => $val['coa'],
                        'type'               => $val['type'],
                        'value'              => $val['value'],
                        'description'        => $request->description,
                        'reference_number'   => $transaction_number,
                        'phase'              => 'opr',
                        'calculated'         => $val['calculated'],
                    ];
                }

                $id_receipt = insert_receipt($request, $transaction_number, $request->reference_number);

                insert_general_ledger($general_ledger, $transaction_number, $request->reference_number);

                // untuk insert pada deposit
                if (isset($request->deposit)) {
                    if ($request->deposit == 'down_payment') {
                        $this->_insert_down_payment($request, $transaction_number_dp);
                    } else if ($request->deposit == 'advance_payment') {
                        $this->_insert_advance_payment($request, $transaction_number_dp);
                    }
                }

                // untuk update transaction term
                if (isset($request->id_transaction_term)) {
                    $transaction_term = TransactionTerm::find($request->id_transaction_term);
                    $transaction_term->id_receipt = $id_receipt;
                    $transaction_term->save();
                }

                // untuk update status pada shipping instruction
                if (connection_exist('operation')) {
                    $this->_update_shipping_instruction($request);
                }

                ActivityLogHelper::log('finance:transaction_receipt_create', 1, ['finance:transaction_number' => $transaction_number]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($general_ledger, 'Receipt Created Successfully');
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_receipt_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/receipts",
     *  summary="Delete receipt",
     *  tags={"Finance - Receipts"},
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
                $check_transaction = Receipts::where('transaction_number', $transaction_number)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                $general_ledger = GeneralLedger::where('transaction_number', $transaction_number);
                $general_ledger->delete();

                ActivityLogHelper::log('finance:transaction_receipt_delete', 1, ['finance:reference_number' => $transaction_number]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_receipt_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    // untuk update status pada shipping instruction
    private function _update_shipping_instruction($request)
    {
        $transaction = Transaction::where('reference_number', $request->reference_number)->first();
        if ($transaction) {
            $transaction_total = $transaction->value;

            $receipt = Receipts::where('reference_number', $request->reference_number)->get();
            $receipt_total = [];
            foreach ($receipt as $key => $row) {
                $receipt_total[] = $row->value;
            }

            $receipt_total = array_sum($receipt_total);
            $total = ($transaction_total - $receipt_total);

            if ($total == 0) {
                $invoice_fob = InvoiceFob::where('transaction_number', $request->transaction_number)->first();
                if ($invoice_fob) {
                    $shipping_intruction = ShippingInstruction::where('id_plan_barging', $invoice_fob->id_plan_barging)->first();
                    $shipping_intruction->status = '6';
                    $shipping_intruction->save();
                }
            }
        }
    }

    // untuk melakukan perhitungan down_payment
    private function _count_down_payment($request, $id_journal_dp)
    {
        // untuk jurnal pertama
        $request_pnm = (object) [
            'id_journal' => $request->id_journal,
            'in_ex_tax'  => $request->in_ex_tax,
            'total'      => ($request->total - $request->deposit_total),
        ];

        $result = _count_journal($request_pnm);

        // untuk jurnal kedua
        $request_dp = (object) [
            'id_journal' => $id_journal_dp,
            'in_ex_tax'  => 'y',
            'total'      => $request->deposit_total,
        ];

        $result_dp = _count_journal($request_dp);

        $merged = [];

        // Masukkan semua dari array pertama
        foreach ($result as $item) {
            $coa = $item['coa'];
            $merged[$coa] = $item;
        }

        // Gabungkan array kedua
        foreach ($result_dp as $item) {
            $coa = $item['coa'];
            if (isset($merged[$coa])) {
                // Jika COA sama, tambahkan value
                $merged[$coa]['value'] += $item['value'];
            } else {
                // Jika COA belum ada, tambahkan sebagai item baru
                $merged[$coa] = $item;
            }
        }

        // Reset indeks numerik (opsional)
        $merged = array_values($merged);

        usort($merged, function ($a, $b) {
            return $a['type'] === $b['type'] ? 0 : ($a['type'] === 'D' ? -1 : 1);
        });

        $list_debit  = [];
        $list_credit = [];

        foreach ($merged as $key => $val) {
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
            return $merged;
        }
    }

    // insert down payment
    private function _insert_down_payment($request, $transaction_number_dp)
    {
        // mengecek apa bila id_kontak sudah didaftar
        $check_down_payment = DownPayment::where('id_kontak', $request->id_kontak)->first();

        $id_down_payment = $check_down_payment->id_down_payment;

        $down_payment_details                     = new DownPaymentDetails();
        $down_payment_details->id_down_payment    = $id_down_payment;
        $down_payment_details->category           = 'pengeluaran';
        $down_payment_details->transaction_number = $transaction_number_dp;
        $down_payment_details->date               = $request->date;
        $down_payment_details->value              = $request->deposit_total;
        $down_payment_details->description        = $request->description;
        $down_payment_details->save();
    }

    // untuk melakukan perhitungan advance_payment
    private function _count_advance_payment($request)
    {
        $request_pnm = (object) [
            'id_journal'    => $request->id_journal,
            'in_ex_tax'     => $request->in_ex_tax,
            'total'         => $request->total,
            'deposit'       => $request->deposit,
            'deposit_total' => $request->deposit_total
        ];

        $result = _count_journal($request_pnm);

        // Pisahkan berdasarkan type
        $grouped = ['D' => [], 'K' => []];
        foreach ($result as $item) {
            $grouped[$item['type']][] = $item;
        }

        // Urutkan masing-masing group: calculated="1" di awal
        foreach ($grouped as &$group) {
            usort($group, function ($a, $b) {
                return $b['calculated'] <=> $a['calculated'];
            });
        }

        // Gabungkan kembali ke satu array
        $sorted = array_merge($grouped['D'], $grouped['K']);

        $list_debit  = [];
        $list_credit = [];

        foreach ($sorted as $key => $val) {
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
            return $sorted;
        }
    }

    // insert advance payment
    private function _insert_advance_payment($request, $transaction_number_dp)
    {
        // mengecek apa bila id_kontak sudah didaftar
        $check_advance_payment = Liability::where('id_kontak', $request->id_kontak)->first();

        $id_liability = $check_advance_payment->id_liability;

        $liability_details                     = new LiabilityDetail();
        $liability_details->id_liability       = $id_liability;
        $liability_details->category           = 'pengeluaran';
        $liability_details->transaction_number = $transaction_number_dp;
        $liability_details->date               = $request->date;
        $liability_details->value              = $request->deposit_total;
        $liability_details->description        = $request->description;
        $liability_details->save();
    }
}
