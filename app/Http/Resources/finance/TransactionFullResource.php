<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionFullResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_transaction_full' => $this->id_transaction_full,
            'transaction_number'  => $this->transaction_number,
            'invoice_number'      => $this->invoice_number,
            'efaktur_number'      => $this->efaktur_number,
            'date'                => $this->date,
            'from_or_to'          => $this->from_or_to,
            'description'         => $this->description,
            'attachment'          => asset_upload('file/transaction_full/' . $this->attachment),
            'category'            => $this->category,
            'record_type'         => $this->record_type,
            'value'               => $this->value,
            'in_ex'               => $this->in_ex,
            'status'              => $this->status
        ];
    }
}
