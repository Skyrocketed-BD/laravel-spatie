<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\TransactionNameRequest;
use App\Models\finance\TransactionName;
use Illuminate\Support\Facades\DB;

class TransactionNameController extends Controller
{
    /**
     * @OA\Get(
     *  path="/transaction-names",
     *  summary="Get the list of transaction names",
     *  tags={"Finance - Transaction Name"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = TransactionName::orderBy('id_transaction_name', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Transaction Name Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/transaction-names",
     *  summary="Add a new transaction name",
     *  tags={"Finance - Transaction Name"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Transaction name name"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Transaction name category (penerimaan, pengeluaran)"
     *              ),
     *              required={"name", "category"},
     *              example={
     *                  "name": "Harian",
     *                  "category": "penerimaan"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TransactionNameRequest $request)
    {
        DB::connection('finance')->beginTransaction();

        try {
            $transaction_name           = new TransactionName();
            $transaction_name->name     = $request->name;
            $transaction_name->category = $request->category;
            $transaction_name->save();

            ActivityLogHelper::log('finance:transaction_name_create', 1, [
                'name'     => $transaction_name->name,
                'category' => $transaction_name->category
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($transaction_name, 'Transaction Name Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_name_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/transaction-names/{id}",
     *  summary="Get the detail of transaction name",
     *  tags={"Finance - Transaction Name"},
     *  @OA\Parameter(
     *      name="id",
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
    public function show($id)
    {
        $data = TransactionName::find($id);

        return ApiResponseClass::sendResponse($data, 'Transaction Name Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/transaction-names/{id}",
     *  summary="Update transaction name",
     *  tags={"Finance - Transaction Name"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Transaction name name"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Transaction name category (penerimaan, pengeluaran)"
     *              ),
     *              required={"name", "category"},
     *              example={
     *                  "name": "Harian",
     *                  "category": "penerimaan"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(TransactionNameRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = TransactionName::find($id);

            $data->update([
                'name'     => $request->name,
                'category' => $request->category,
            ]);

            ActivityLogHelper::log('finance:transaction_name_update', 1, [
                'name'     => $request->name,
                'category' => $request->category
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Transaction Name Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_name_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/transaction-names/{id}",
     *  summary="Delete transaction name",
     *  tags={"Finance - Transaction Name"},
     *  @OA\Parameter(
     *      name="id",
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
    public function destroy($id)
    {
        try {
            $data = TransactionName::find($id);

            if ($data->id_transaction_name == 1 || $data->id_transaction_name == 2) {
                return ApiResponseClass::throw('Cannot delete default data', 500);
            }

            $data->delete();
            
            ActivityLogHelper::log('finance:transaction_name_delete', 1, [
                'name'     => $data->name,
                'category' => $data->category
            ]);

            return ApiResponseClass::sendResponse($data, 'Transaction Name Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_name_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *  path="/transaction-names/type/{type}",
     *  summary="Get the detail of transaction name by type",
     *  tags={"Finance - Transaction Name"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"penerimaan", "pengeluaran"}
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function type($type)
    {
        $data = TransactionName::where('category', $type)->get();

        $result = [];

        foreach ($data as $key => $value) {
            $result[] = [
                'id_transaction_name' => $value->id_transaction_name,
                'name'                => $value->name,
                'category'            => $value->category
            ];
        }

        return ApiResponseClass::sendResponse($result, 'Transaction Type Retrieved Successfully');
    }
}
