<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\main\RoleRequest;
use App\Models\main\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * @OA\Get(
     *  path="/roles",
     *  summary="Get the list of roles",
     *  tags={"Main - Role"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = Role::orderBy('id', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Role Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/roles",
     *  summary="Add a new role",
     *  tags={"Main - Role"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Role name"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "Cash",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(RoleRequest $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $data = Role::create([
                'name' => $request->name
            ]);

            ActivityLogHelper::log('admin:role_create', 1, ['name' => $request->name]);
            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($data, 'Role Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:role_create', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/roles/{id}",
     *  summary="Get a single role",
     *  tags={"Main - Role"},
     *  @OA\Parameter(
     *      name="id",
     *      description="ID of role to get",
     *      required=true,
     *      in="path",
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = Role::find($id);

        return ApiResponseClass::sendResponse($data, 'Role Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/roles/{id}",
     *  summary="Update a role",
     *  tags={"Main - Role"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Role name"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "Cash",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(RoleRequest $request, $id)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $data = Role::find($id);

            $data->update([
                'name' => $request->name
            ]);

            ActivityLogHelper::log('admin:role_update', 1, ['name' => $request->name]);
            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($data, 'Role Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:role_update', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/roles/{id}",
     *  summary="Delete a role",
     *  tags={"Main - Role"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = Role::find($id);

            $data->delete();
            
            ActivityLogHelper::log('admin:role_delete', 1, ['name' => $data->name]);
            
            return ApiResponseClass::sendResponse($data, 'Role Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:role_delete', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    public function revoke(Request $request, $id)
    {
        try {
            $data = Role::find($id);

            $data->revokePermissionTo($request->permission);

            ActivityLogHelper::log('admin:role_revoke', 1, ['name' => $data->name]);

            return ApiResponseClass::sendResponse($data, 'Role Revoked Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:role_revoke', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::throw('Cannot revoke data', 409, $e->getMessage());
        }
    }

    public function give(Request $request, $id)
    {
        try {
            $data = Role::find($id);

            $data->givePermissionTo($request->permission);

            ActivityLogHelper::log('admin:role_give', 1, ['name' => $data->name]);

            return ApiResponseClass::sendResponse($data, 'Role Given Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:role_give', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::throw('Cannot give data', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *  path="/roles/access/{id_role}/{id_menu_module}",
     *  summary="Get role access",
     *  tags={"Main - Role"},
     *  @OA\Parameter(
     *      name="id_role",
     *      in="path",
     *      required=true,
     *      description="ID of role to get",
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_module",
     *      in="path",
     *      required=true,
     *      description="ID of menu module to get",
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function access($id_role, $id_menu_module)
    {
        $data = Role::with(['toRoleAccess'])->find($id_role);

        $permission = $data->toRoleHasPermission;

        // untuk role permission
        $role_permission = [];

        foreach ($permission as $key => $value) {
            $role_permission[$value->permission_id] = $value->toPermission->name;
        }

        // untuk role access
        $menu_module   = [];
        $menu_category = [];
        $menu_body     = [];
        $menu_child    = [];
        $menus         = [];
        $permission    = [];

        $access = $data->toRoleAccess;

        foreach ($access as $key => $value) {
            if ($value->toMenuBody->toMenuCategory->toMenuModule) {
                $menu_module[] = [
                    'id_menu_module' => $value->toMenuBody->toMenuCategory->toMenuModule->id_menu_module,
                    'name'           => $value->toMenuBody->toMenuCategory->toMenuModule->name,
                ];
            }

            if ($value->toMenuBody->toMenuCategory) {
                $menu_category[] = [
                    'id_menu_category' => $value->toMenuBody->toMenuCategory->id_menu_category,
                    'id_menu_module'   => $value->toMenuBody->toMenuCategory->toMenuModule->id_menu_module,
                    'name'             => $value->toMenuBody->toMenuCategory->name,
                    'position'         => $value->toMenuBody->toMenuCategory->position,
                ];
            }

            usort($menu_category, function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });

            if ($value->toMenuBody->is_enabled == 1) {
                $hasListPermission = false;

                // Cek apakah ada permission yang diawali 'list'
                if ($value->toMenuBody->toMenuPermission->count() > 0) {
                    foreach ($value->toMenuBody->toMenuPermission as $key => $row) {
                        $permissionName = $row->toPermission->name;

                        if (Str::startsWith($permissionName, 'list') && !empty($role_permission[$row->id_permission])) {
                            $hasListPermission = true;
                        }

                        $permission[$row->id_menu_body][] = [
                            'id_permission' => $row->id_permission,
                            'name'          => $permissionName,
                        ];
                    }
                }

                // Hanya tampilkan jika punya permission 'list-*'
                if ($hasListPermission) {
                    if ($value->toMenuBody->parent_id === 0) {
                        $menu_body[] = [
                            'id_menu_category' => $value->toMenuBody->toMenuCategory->id_menu_category,
                            'id_menu_body'     => $value->toMenuBody->id_menu_body,
                            'name'             => $value->toMenuBody->name,
                            'icon'             => $value->toMenuBody->icon,
                            'url'              => $value->toMenuBody->url,
                            'is_enabled'       => $value->toMenuBody->is_enabled,
                            'position'         => $value->toMenuBody->position,
                        ];
                    } else {
                        $menu_child[] = [
                            'id_menu_body'  => $value->toMenuBody->id_menu_body,
                            'parent_id'     => $value->toMenuBody->parent_id,
                            'name'          => $value->toMenuBody->name,
                            'icon'          => $value->toMenuBody->icon,
                            'url'           => $value->toMenuBody->url,
                        ];
                    }
                }
            }
        }

        usort($menu_body, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        $menu_module   = array_unique($menu_module, SORT_REGULAR);
        $menu_category = array_unique($menu_category, SORT_REGULAR);

        foreach ($menu_child as $key3 => $value3) {
            $child[$value3['parent_id']][] = [
                'icon'       => $value3['icon'],
                'title'      => $value3['name'],
                'pathname'   => $value3['url'],
                'permission' => $permission[$value3['id_menu_body']] ?? '',
            ];
        }

        foreach ($menu_body as $key2 => $value2) {
            $body[$value2['id_menu_category']][] = [
                'icon'       => $value2['icon'],
                'title'      => $value2['name'],
                'pathname'   => $value2['url'],
                'permission' => $permission[$value2['id_menu_body']] ?? '',
                'subMenu'    => $child[$value2['id_menu_body']] ?? '',
            ];
        }

        foreach ($menu_category as $key1 => $value1) {
            $category[$value1['id_menu_module']][] = [
                'category' => $value1['name'],
                'items'    => $body[$value1['id_menu_category']] ?? '',
            ];
        }

        foreach ($menu_module as $key => $value) {
            if ($id_menu_module == $value['id_menu_module']) {
                $menus = $category[$value['id_menu_module']] ?? '';
            }
        }

        return ApiResponseClass::sendResponse($menus, 'Role Access Successfully');
    }
}
