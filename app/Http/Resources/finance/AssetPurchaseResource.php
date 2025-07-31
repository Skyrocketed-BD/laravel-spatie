<?php

namespace App\Http\Resources\finance;

use App\Models\main\Kontak;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetPurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kontak = Kontak::find($this->id_kontak);
        return [
            'transaction_number' => $this->transaction_number,
            'kontak'             => $kontak->name,
            'date'               => $this->date,
            'value'              => $this->value,
            'description'        => $this->description
        ];
    }
}
