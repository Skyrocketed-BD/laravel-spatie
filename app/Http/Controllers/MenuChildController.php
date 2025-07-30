<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Resources\main\MenuChildResource;
use App\Models\main\MenuChild;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuChildController extends Controller
{
    /**
     * @OA\Get(
     *  path="/menu/child/{id_menu_body}",
     *  summary="Get a list of menu child",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_body",
     *      description="Menu Body id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index($id_menu_body)
    {
        $data = MenuChild::whereIdMenuBody($id_menu_body)->get();

        return ApiResponseClass::sendResponse(MenuChildResource::collection($data), 'Menu Child Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/menu/child",
     *  summary="Add a new menu child",
     *  tags={"Main - Menu Management"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_menu_body",
     *                  type="integer",
     *                  description="Menu Body id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Menu Child name"
     *              ),
     *              @OA\Property(
     *                  property="url",
     *                  type="string",
     *                  description="Menu Child url"
     *              ),
     *              required={"id_menu_body", "name", "url"},
     *              example={
     *                  "id_menu_body": 1,
     *                  "name": "Cash",
     *                  "url": "/cash"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Add a new resource"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = MenuChild::create([
                'id_menu_body' => $request->id_menu_body,
                'name'         => $request->name,
                'url'          => $request->url
            ]);

            DB::commit();

            return ApiResponseClass::sendResponse($data, 'Menu Child Created Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/menu/child/{id_menu_body}/{id_menu_child}",
     *  summary="Get a menu child",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_body",
     *      description="Menu Body id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_child",
     *      description="Menu Child id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id_menu_body, $id_menu_child)
    {
        $data = MenuChild::whereIdMenuBody($id_menu_body)->whereIdMenuChild($id_menu_child)->get();

        return ApiResponseClass::sendResponse(MenuChildResource::collection($data), 'Menu Child Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/menu/child/{id_menu_body}/{id_menu_child}",
     *  summary="Update a menu child",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_body",
     *      description="Menu Body id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_child",
     *      description="Menu Child id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_menu_body",
     *                  type="integer",
     *                  description="Menu Body id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Menu Child name"
     *              ),
     *              @OA\Property(
     *                  property="url",
     *                  type="string",
     *                  description="Menu Child url"
     *              ),
     *              required={"id_menu_body", "name", "url"},
     *              example={
     *                  "id_menu_body": 1,
     *                  "name": "Cash",
     *                  "url": "/cash"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Add a new resource"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(Request $request, $id_menu_body, $id_menu_child)
    {
        DB::beginTransaction();
        try {
            $data = MenuChild::find($id_menu_child);

            $data->update([
                'id_menu_body' => $request->id_menu_body,
                'name'         => $request->name,
                'url'          => $request->url
            ]);

            DB::commit();

            return ApiResponseClass::sendResponse($data, 'Menu Child Updated Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/menu/child/{id_menu_body}/{id_menu_child}",
     *  summary="Delete a menu child",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_body",
     *      description="Menu Body id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_child",
     *      description="Menu Child id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id_menu_body, $id_menu_child)
    {
        try {
            $data = MenuChild::find($id_menu_child);

            $data->delete();

            return ApiResponseClass::sendResponse($data, 'Menu Child Deleted Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
