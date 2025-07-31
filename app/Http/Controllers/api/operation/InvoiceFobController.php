<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\InvoiceFobRequest;
use App\Http\Resources\operation\InvoiceFobResource;
use App\Models\finance\DownPayment;
use App\Models\finance\DownPaymentDetails;
use App\Models\finance\Journal;
use App\Models\operation\InvoiceFob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class InvoiceFobController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/invoice_fob",
     *  summary="Get the list of invoice_fob",
     *  tags={"Operation - Invoice Fob"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $query = InvoiceFob::query();

        $query->with(['toPlanBarging', 'toJournal']);

        $query->orderBy('id_invoice_fob', 'asc');

        $data = $query->get();

        return ApiResponseClass::sendResponse(InvoiceFobResource::collection($data), 'Invoice Fob Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/invoice_fob",
     *  summary="Update a invoice_fob",
     *  tags={"Operation - Invoice Fob"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_plan_barging",
     *                  type="integer",
     *                  description="ID Plan Barging"
     *              ),
     *              @OA\Property(
     *                  property="id_journal",
     *                  type="integer",
     *                  description="Journal ID"
     *              ),
     *              @OA\Property(
     *                  property="id_kontak",
     *                  type="string",
     *                  description="ID Kontak"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="string",
     *                  format="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="buyer_name",
     *                  type="string",
     *                  description="Buyer Name"
     *              ),
     *              @OA\Property(
     *                  property="hpm",
     *                  type="integer",
     *                  description="Hpm"
     *              ),
     *              @OA\Property(
     *                  property="hma",
     *                  type="integer",
     *                  description="Hma"
     *              ),
     *              @OA\Property(
     *                  property="kurs",
     *                  type="integer",
     *                  description="Kurs"
     *              ),
     *              @OA\Property(
     *                  property="price",
     *                  type="integer",
     *                  description="Price"
     *              ),
     *              @OA\Property(
     *                  property="mc",
     *                  type="integer",
     *                  description="Mc"
     *              ),
     *              @OA\Property(
     *                  property="tonage",
     *                  type="integer",
     *                  description="Tonage"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference Number"
     *              ),
     *              @OA\Property(
     *                  property="ni",
     *                  type="string",
     *                  description="NI"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(InvoiceFobRequest $request)
    {
        $transaction_number  = generateFinNumber('transaction', 'transaction_number', 'INV');

        $request->total               = $request->price;
        $request->from_or_to          = $request->buyer_name;
        $request->id_transaction_name = 2;
        $request->value               = $request->total;

        // if ($request->adv_pay) {
        //     $this->_journal_receipt($request->adv_pay_journal, $request->id_kontak, $request->adv_pay_total, $request->description, $request->reference_number);
        // }

        return $this->_journal_invoice($request, $transaction_number);
    }

    /**
     * @OA\Get(
     *  path="/invoice_fob/check/{transaction_number}",
     *  summary="Get the list of invoice_fob",
     *  tags={"Operation - Invoice Fob"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function check($transaction_number)
    {
        $query = InvoiceFob::query();

        $query->with(['toPlanBarging.toPlanBargingDetail', 'toPlanBarging.toKontraktor', 'toTransaction']);

        $query->where('transaction_number', $transaction_number);

        $data = $query->first();

        return ApiResponseClass::sendResponse(InvoiceFobResource::make($data), 'Invoice Fob Retrieved Successfully');
    }

    private function _insert($request, $transaction_number)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $data = InvoiceFob::create([
                'id_plan_barging'    => $request->id_plan_barging,
                'id_journal'         => $request->id_journal,
                'id_kontak'          => $request->id_kontak,
                'transaction_number' => $transaction_number,
                'date'               => $request->date,
                'buyer_name'         => $request->buyer_name,
                'hpm'                => $request->hpm,
                'hma'                => $request->hma,
                'kurs'               => $request->kurs,
                'price'              => $request->price,
                'mc'                 => $request->mc,
                'tonage'             => $request->tonage,
                'ni'                 => $request->ni,
                'description'        => $request->description,
                'reference_number'   => $request->reference_number
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Invoice Fob Created Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    private function _journal_invoice($request, $transaction_number)
    {
        $result = _count_journal($request, $transaction_number);

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

            insert_transaction($request, $transaction_number, $request->reference_number);

            insert_general_ledger($general_ledger, $transaction_number, $request->reference_number);

            ActivityLogHelper::log('finance:invoice_fob_create', 1, [
                'finance:transaction_number' => $transaction_number
            ]);

            return $this->_insert($request, $transaction_number);
        } else {
            ActivityLogHelper::log('finance:invoice_fob_create', 0, [
                'finance:transaction_number' => $transaction_number,
            ]);
            return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
        }
    }

    // private function _journal_receipt($id_journal, $id_kontak, $total, $description, $reference_number)
    // {
    //     $transaction_number = generateFinNumber('receipts', 'transaction_number', 'PNM');

    //     $receipt_request = (object)[
    //         'id_journal'       => $id_journal,
    //         'id_kontak'        => $id_kontak,
    //         'total'            => $total,
    //         'date'             => date('Y-m-d'),
    //         'description'      => 'Pembayaran Awal DP - ' . $description,
    //         'reference_number' => $reference_number,
    //         'from_or_to'       => null,
    //         'pay_type'         => 'bt',
    //         'record_type'      => Journal::find($id_journal)->alocation,
    //         'in_ex_tax'        => 'o',
    //     ];

    //     $result = _count_journal($receipt_request);

    //     if ($result) {
    //         $general_ledger = [];

    //         foreach ($result as $key => $val) {
    //             $general_ledger[] = [
    //                 'id_journal'         => $id_journal,
    //                 'transaction_number' => $transaction_number,
    //                 'date'               => $receipt_request->date,
    //                 'coa'                => $val['coa'],
    //                 'type'               => $val['type'],
    //                 'value'              => $val['value'],
    //                 'description'        => $receipt_request->description,
    //                 'reference_number'   => $transaction_number,
    //                 'phase'              => 'opr',
    //                 'calculated'         => $val['calculated'],
    //             ];
    //         }

    //         // mengecek apa bila id_kontak sudah didaftar
    //         $check_down_payment = DownPayment::where('id_kontak', $receipt_request->id_kontak)->first();

    //         $id_down_payment = $check_down_payment->id_down_payment;

    //         // untuk insert down payment details
    //         $down_payment_details                     = new DownPaymentDetails();
    //         $down_payment_details->id_down_payment    = $id_down_payment;
    //         $down_payment_details->id_journal         = $receipt_request->id_journal;
    //         $down_payment_details->category           = 'pengeluaran';
    //         $down_payment_details->transaction_number = $transaction_number;
    //         $down_payment_details->date               = $receipt_request->date;
    //         $down_payment_details->value              = $receipt_request->total;
    //         $down_payment_details->description        = $receipt_request->description;
    //         $down_payment_details->save();

    //         insert_receipt($receipt_request, $transaction_number, $receipt_request->reference_number);

    //         insert_general_ledger($general_ledger, $transaction_number, $receipt_request->reference_number);
    //     }
    // }
}
