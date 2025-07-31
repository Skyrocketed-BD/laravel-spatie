<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class ExpenditureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_expenditure'     => $this->id_expenditure,
            'journal'            => $this->toJournal->name,
            'reference_number'   => $this->reference_number,
            'transaction_number' => $this->transaction_number,
            'date'               => $this->date,
            'outgoing_to'        => $this->toKontak->name,
            'pay_type'           => Config::get('constants.pay_type')[$this->pay_type],
            'value'              => $this->value,
            'description'        => $this->description,
            'status'             => $this->status,
        ];
    }
}
