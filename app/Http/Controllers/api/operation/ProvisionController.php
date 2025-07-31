<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\ProvisionRequest;
use App\Http\Resources\operation\ProvisionResource;
use App\Models\operation\Provision;
use App\Models\operation\ShippingInstruction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProvisionController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/provision",
     *  summary="Get the list of provision",
     *  tags={"Operation - Provision"},
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
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

        $provision = Provision::query();

        $provision->with([
            'toShippingInstruction.toPlanBarging.toPlanBargingDetail',
            'toShippingInstruction.toPlanBarging.toInvoiceFob.toTransaction.toReceipts',
            'toShippingInstruction.toPlanBarging.toInvoiceFob.toTransaction.toTransactionTerm',
            'toShippingInstruction.toKontraktor',
            'toProvisionCoa',
        ]);

        if ($this->id_kontraktor != null) {
            $provision->whereHas('toShippingInstruction', function ($query) {
                $query->where('id_kontraktor', $this->id_kontraktor);
            });
        }

        $provision->whereBetween('departure_date', [$start_date, $end_date]);

        $data = $provision->get();

        return ApiResponseClass::sendResponse(ProvisionResource::collection($data), 'Provision Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/provision",
     *  summary="Add a new provision",
     *  tags={"Operation - Provision"},
     *  @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_kontak",
     *                  type="string",
     *                  description="ID Kontak"
     *              ),
     *              @OA\Property(
     *                  property="id_shipping_instruction",
     *                  type="string",
     *                  description="Id Shipping Instruction"
     *              ),
     *              @OA\Property(
     *                  property="buyer_name",
     *                  type="string",
     *                  description="Buyer Name"
     *              ),
     *              @OA\Property(
     *                  property="inv_provision",
     *                  type="string",
     *                  description="Invoice Provision"
     *              ),
     *              @OA\Property(
     *                  property="method_sales",
     *                  type="string",
     *                  description="Method Sales",
     *                  enum={"cif", "fob"}
     *              ),
     *              @OA\Property(
     *                  property="departure_date",
     *                  type="date",
     *                  description="Departure Date"
     *              ),
     *              @OA\Property(
     *                  property="pnbp_provision",
     *                  type="integer",
     *                  description="Pnbp Provision"
     *              ),
     *              @OA\Property(
     *                  property="selling_price",
     *                  type="integer",
     *                  description="Selling Price"
     *              ),
     *              @OA\Property(
     *                  property="cow",
     *                  type="integer",
     *                  description="Certificate of Weight"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(ProvisionRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $attachment = add_file($request->attachment, 'provision/');

            $data = new Provision();
            $data->id_kontak               = $request->id_kontak;
            $data->id_shipping_instruction = $request->id_shipping_instruction;
            $data->inv_provision           = $request->inv_provision;
            $data->method_sales            = $request->method_sales;
            $data->departure_date          = $request->departure_date;
            $data->pnbp_provision          = $request->pnbp_provision;
            $data->selling_price           = $request->selling_price;
            $data->tonage_actual           = $request->tonage_actual;
            $data->attachment              = $attachment;
            $data->save();

            ActivityLogHelper::log('operation:provision_create', 1, [
                'operation:provision_invoice' => $request->inv_provision,
                'operation:departure_date'    => $request->departure_date,
            ]);

            ShippingInstruction::where('id_shipping_instruction', $request->id_shipping_instruction)->update([
                'status' => '5',
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Provision Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:provision_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
