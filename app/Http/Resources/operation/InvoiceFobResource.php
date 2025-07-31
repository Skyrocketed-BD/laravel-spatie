<?php

namespace App\Http\Resources\operation;

use App\Http\Resources\finance\ReceiptResource;
use App\Http\Resources\finance\TransactionResource;
use App\Http\Resources\finance\TransactionTermResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceFobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_invoice_fob']     = $this->id_invoice_fob;
        $response['id_plan_barging']    = $this->id_plan_barging;
        $response['plan_barging']       = $this->toPlanBarging->pb_name;
        $response['kontraktor']         = $this->toPlanBarging->toKontraktor->company ?? null;
        $response['id_journal']         = $this->id_journal;
        $response['journal']            = $this->toJournal->name;
        $response['transaction_number'] = $this->transaction_number;
        $response['date']               = $this->date;
        $response['buyer_name']         = $this->buyer_name;
        $response['hpm']                = $this->hpm;
        $response['kurs']               = $this->kurs;
        $response['ni']                 = $this->ni;
        $response['price']              = $this->price;
        $response['mc']                 = $this->mc;
        $response['tonage']             = $this->tonage;
        $response['description']        = $this->description;
        $response['reference_number']   = $this->reference_number;

        if ($this->toTransaction) {
            $response['transaction_number'] = TransactionResource::make($this->toTransaction);
        } else {
            $response['transaction_number'] = null;
        }

        if ($this->toTransaction) {
            if ($this->toTransaction->toReceipts) {
                $response['receipts'] = ReceiptResource::collection($this->toTransaction->toReceipts);
            } else {
                $response['receipts'] = [];
            }

            if ($this->toTransaction->toTransactionTerm) {
                $response['transaction_terms'] = TransactionTermResource::collection($this->toTransaction->toTransactionTerm);
            } else {
                $response['transaction_terms'] = [];
            }
        } else {
            $response['receipts'] = [];
            $response['transaction_terms'] = [];
        }

        return $response;
    }
}
