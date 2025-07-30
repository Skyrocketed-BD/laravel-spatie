<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuPermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_menu_permission' => $this->id_menu_permission,
            'id_menu_body'       => $this->id_menu_body,
            'id_permission'      => $this->id_permission,
            'permission'         => $this->toPermission->name,
        ];
    }
}
