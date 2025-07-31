<?php

namespace App\Http\Resources\main;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $module = [];

        if ($this->toModelHasRole) {
            foreach ($this->toModelHasRole->toRoleAccess as $key => $value) {
                $module[] = [
                    'id_module' => $value->toMenuBody->toMenuCategory->toMenuModule->id_menu_module,
                    'name'      => $value->toMenuBody->toMenuCategory->toMenuModule->name,
                ];
            }
        }

        $module = array_unique($module, SORT_REGULAR);

        $result['id_menu_module'] = $module;
        $result['id_kontraktor']  = $this->id_kontraktor;
        $result['color']          = $this->toKontraktor ? $this->toKontraktor->color : null;
        $result['company']        = $this->toKontraktor ? $this->toKontraktor->company : get_arrangement('company_name');
        $result['id_users']       = $this->id_users;
        $result['id_role']        = $this->toModelHasRole->toRole->id ?? null;
        $result['role']           = $this->toModelHasRole->toRole->name ?? null;

        $result['name']           = $this->name;
        $result['email']          = $this->email;
        $result['username']       = $this->username;

        $result['avatar']     = $this->avatar ? asset_upload('file/profile/' . $this->avatar) : null;
        $result['created_at'] = $this->created_at;
        $result['updated_at'] = $this->updated_at;
        $result['is_setup']   = (int) get_arrangement('is_setup');

        return $result;
    }
}
