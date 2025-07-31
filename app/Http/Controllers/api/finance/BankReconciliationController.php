<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\BankReconciliationRequest;
use App\Models\finance\Coa;
use App\Models\finance\GeneralLedger;
use App\Models\finance\BankReconciliation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class BankReconciliationController extends Controller
{
    /**
     * @OA\Get(
     *  path="/bank-reconciliation",
     *  summary="Get the list of bank reconciliation",
     *  tags={"Finance - Bank Reconciliation"},
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
     */
    public function index(Request $request)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $query = BankReconciliation::query();

        $query->whereBetweenMonth($start_date, $end_date);

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('date', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Bank Reconciliation Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/bank-reconciliation",
     *  summary="Create a new bank reconciliation",
     *  tags={"Finance - Bank Reconciliation"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="id_coa_bank",
     *                  type="integer",
     *                  description="ID COA Bank"
     *              ),
     *              @OA\Property(
     *                  property="bank_fee",
     *                  type="integer",
     *                  description="Ammount Bank Fee"
     *              ),
     *              @OA\Property(
     *                  property="bank_interest",
     *                  type="integer",
     *                  description="Ammount Bank Interest"
     *              ),
     *              required={"date", "description", "id_coa_bank", "bank_fee", "bank_interest"},
     *              example={
     *                  "date": "2022-01-01",
     *                  "description": "description",
     *                  "id_coa_bank": 1,
     *                  "bank_fee": 10000,
     *                  "bank_interest": 10000,
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(BankReconciliationRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $bank_fee      = Coa::whereIdCoa(get_arrangement('bank_fee_coa'))->first();
            $bank_interest = Coa::whereIdCoa(get_arrangement('bank_interest_coa'))->first();

            $transaction_number = generate_number('finance', 'bank_reconciliation', 'transaction_number', 'BR');

            $bank_reconciliation = new BankReconciliation();
            $bank_reconciliation->transaction_number = $transaction_number;
            $bank_reconciliation->id_coa_bank        = $request->id_coa_bank;
            $bank_reconciliation->date               = $request->date;
            $bank_reconciliation->description        = $request->description;
            $bank_reconciliation->bank_fee           = $request->bank_fee;
            $bank_reconciliation->bank_interest      = $request->bank_interest;

            $id_coa_bank_fee      = get_arrangement('bank_fee_coa');
            $id_coa_bank_interest = get_arrangement('bank_interest_coa');
            $id_coa_bank          = $request->id_coa_bank;

            $coa_bank_fee      = Coa::whereIdCoa($id_coa_bank_fee)->first();
            $coa_bank_interest = Coa::whereIdCoa($id_coa_bank_interest)->first();
            $coa_bank          = Coa::whereIdCoa($id_coa_bank)->first();

            $bank_interest = $request->bank_interest;
            $bank_fee      = $request->bank_fee;
            $total         = $bank_interest - $bank_fee;
            $total_type    = 'D';

            if ($total < 0) {
                $total_type = 'K';
                $total = abs($total);
            }

            $journal = [
                [
                    'coa'   => $coa_bank_fee->coa,
                    'type'  => 'D',
                    'value' => $bank_fee
                ],
                [
                    'coa'   => $coa_bank->coa,
                    'type'  => $total_type,
                    'value' => $total
                ],
                [
                    'coa'   => $coa_bank_interest->coa,
                    'type'  => 'K',
                    'value' => $bank_interest
                ],
            ];

            foreach ($journal as $key => $value) {
                $general_ledger[] = [
                    'transaction_number' => $transaction_number,
                    'date'               => $request->date,
                    'coa'                => $value['coa'],
                    'type'               => $value['type'],
                    'value'              => $value['value'],
                    'description'        => $request->description . ' - ' . $transaction_number,
                    'reference_number'   => $transaction_number,
                    'phase'              => 'opr',
                    'created_by'         => auth('api')->user()->id_users
                ];
            }

            $bank_reconciliation->value = $total;
            $bank_reconciliation->save();

            GeneralLedger::insert($general_ledger);

            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:bank_reconciliation_create', 1, [
                'finance:coa_bank'           => Coa::find($request->id_coa_bank)->name,
                'finance:bank_fee'           => $bank_fee,
                'finance:bank_interest'      => $bank_interest,
                'finance:transaction_number' => $transaction_number
            ]);

            return ApiResponseClass::sendResponse($bank_reconciliation, 'Transaction Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:bank_reconciliation_create', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/bank-reconciliation/{no_transaction}",
     *  summary="Delete bank reconciliation",
     *  tags={"Finance - Bank Reconciliation"},
     *  @OA\Parameter(
     *      name="no_transaction",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($no_transaction)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $check_gl = GeneralLedger::where('transaction_number', $no_transaction)->where('closed', '1')->first();

            if ($check_gl) {
                return ApiResponseClass::throw('Transaction Already Closed !', 400);
            } else {
                $check_transaction = BankReconciliation::where('transaction_number', $no_transaction)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                GeneralLedger::where('transaction_number', $no_transaction)->delete();

                ActivityLogHelper::log('finance:bank_reconciliation_delete', 1, [
                    'finance:transaction_number' => $no_transaction,
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:bank_reconciliation_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
