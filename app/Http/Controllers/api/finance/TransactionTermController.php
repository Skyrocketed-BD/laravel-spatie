<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\TransactionTermRequest;
use App\Http\Resources\finance\TransactionTermResource;
use App\Models\finance\TransactionTerm;
use Illuminate\Support\Facades\DB;

class TransactionTermController extends Controller
{
    /**
     * @OA\Get(
     *  path="/transaction-terms",
     *  summary="Get the list of transaction terms",
     *  tags={"Finance - Transaction Term"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $query = TransactionTerm::query();

        $data = $query->orderBy('id_transaction_term', 'asc')->get();

        return ApiResponseClass::sendResponse(TransactionTermResource::collection($data), 'Transaction Term Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/transaction-terms",
     *  summary="Create a transaction term",
     *  tags={"Finance - Transaction Term"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_transaction",
     *                  type="string",
     *                  description="Transaction ID"
     *              ),
     *              @OA\Property(
     *                  property="nama",
     *                  type="string",
     *                  description="Name"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="percent",
     *                  type="integer",
     *                  description="Percent"
     *              ),
     *              @OA\Property(
     *                  property="value_ppn",
     *                  type="integer",
     *                  description="Value PPN"
     *              ),
     *              @OA\Property(
     *                  property="value_pph",
     *                  type="integer",
     *                  description="Value PPH"
     *              ),
     *              @OA\Property(
     *                  property="value_percent",
     *                  type="integer",
     *                  description="Value PPH"
     *              ),
     *              @OA\Property(
     *                  property="value_deposit",
     *                  type="integer",
     *                  description="Value Deposit"
     *              ),
     *              @OA\Property(
     *                  property="deposit",
     *                  type="string",
     *                  description="Deposit"
     *              ),
     *              required={"id_transaction", "nama", "date", "percent", "value_ppn", "value_pph", "value_percent", "value_deposit", "deposit"},
     *              example={
     *                  "id_transaction": 1,
     *                  "nama": "Termin 1",
     *                  "date": "2022-01-01",
     *                  "percent": 10,
     *                  "value_ppn": 100000,
     *                  "value_pph": 100000,
     *                  "value_percent": 100000,
     *                  "value_deposit": 100000,
     *                  "deposit": "down_payment"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TransactionTermRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_term                 = new TransactionTerm();
            $transaction_term->id_transaction = $request->id_transaction;
            $transaction_term->nama           = $request->nama;
            $transaction_term->date           = $request->date;
            $transaction_term->percent        = $request->percent;
            $transaction_term->value_ppn      = $request->value_ppn;
            $transaction_term->value_pph      = $request->value_pph;
            $transaction_term->value_percent  = $request->value_percent;
            $transaction_term->value_deposit  = $request->value_deposit;
            $transaction_term->deposit        = $request->deposit;
            $transaction_term->final          = $request->final ?? '0';
            $transaction_term->save();

            ActivityLogHelper::log('finance:transaction_term_create', 1, [
                'finance:term_name' => $transaction_term->nama,
                'date'              => $transaction_term->date,
                'final'             => $transaction_term->final
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($transaction_term, 'Transaction Term Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_term_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/transaction-terms/details/{id}",
     *  summary="Get transaction term details",
     *  tags={"Finance - Transaction Term"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="Transaction Term ID",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function detail($id)
    {
        $data = TransactionTerm::with(['toTransaction'])->whereIdTransaction($id)->get();

        return ApiResponseClass::sendResponse(TransactionTermResource::collection($data), 'Transaction Term Detail Retrieved Successfully');
    }
}
