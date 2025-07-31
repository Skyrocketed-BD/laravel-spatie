<?php

namespace App\Http\Resources\finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiabilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response['id_liability'] = $this->id_liability;
        $response['id_kontak']    = $this->id_kontak;
        $response['contact_name'] = $this->toKontak->name;
        $response['jenis']        = $this->toKontak->toKontakJenis->name;

        $total = $this->toLiabilityDetail
            ? $this->toLiabilityDetail->sum(function ($item) {
                return $item->category === 'penerimaan' ? $item->value : -$item->value;
            })
            : 0;

        $response['total'] = $total;

        if ($this->toLiabilityDetail) $response['details'] = $this->toLiabilityDetail;

        return $response;
    }
}
