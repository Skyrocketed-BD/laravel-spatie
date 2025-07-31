<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionTermResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_transaction_term' => $this->id_transaction_term,
            'id_transaction'      => $this->id_transaction,
            'id_kontak'           => $this->toTransaction->id_kontak,
            'nama'                => $this->nama,
            'date'                => $this->date,
            'percent'             => $this->percent,
            'value_ppn'           => $this->value_ppn,
            'value_pph'           => $this->value_pph,
            'value_percent'       => $this->value_percent,
            'final'               => $this->final
        ];
    }
}
