<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_transaction']      = $this->id_transaction;
        $response['id_journal']          = $this->id_journal;
        $response['id_kontak']           = $this->id_kontak;
        $response['id_transaction_name'] = $this->id_transaction_name;
        $response['transaction_number']  = $this->transaction_number;
        $response['transaction_name']    = $this->toTransactionName->name;
        $response['reference_number']    = $this->reference_number;
        $response['journal']             = $this->toJournal->name;
        $response['date']                = $this->date;
        $response['from_or_to']          = $this->from_or_to;
        $response['description']         = $this->description;
        $response['in_ex']               = $this->in_ex;
        $response['status']              = $this->status;
        $response['value']               = $this->value;
        $response['durasi_hari']         = empty($this->date) ? null : count_days($this->date, date('Y-m-d'));

        if ($this->toReceipts) {
            $response['bayar'] = $this->toReceipts->where('status', 'valid')->sum('value');

            $sisa = ($this->value - $this->toReceipts->where('status', 'valid')->sum('value'));

            $response['sisa'] = $sisa;
        } else if ($this->toExpenditure) {
            $response['bayar'] = $this->toExpenditure->where('status', 'valid')->sum('value');

            $sisa = ($this->value - $this->toExpenditure->where('status', 'valid')->sum('value'));

            $response['sisa'] = $sisa;
        }

        return $response;
    }
}
