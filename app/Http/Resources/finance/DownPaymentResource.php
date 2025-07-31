<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DownPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_down_payment']    = $this->id_down_payment;
        $response['id_kontak']          = $this->id_kontak;
        $response['contact_name']       = $this->toKontak->name;
        $response['jenis']              = $this->toKontak->toKontakJenis->name;

        $total = $this->toDownPaymentDetail
            ? $this->toDownPaymentDetail->sum(function ($item) {
                return $item->category === 'penerimaan' ? $item->value : -$item->value;
            })
            : 0;

        $response['total'] = $total;

        if ($this->toDownPaymentDetail) $response['details'] = $this->toDownPaymentDetail;

        return $response;
    }
}
