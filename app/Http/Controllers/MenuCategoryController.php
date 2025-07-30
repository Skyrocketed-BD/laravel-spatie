<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Resources\main\MenuCategoryResource;
use App\Models\main\MenuCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuCategoryController extends Controller
{
    /**
     * @OA\Get(
     *  path="/menu/categories/{id_menu_module}",
     *  summary="Get a list of menu category",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_module",
     *      description="Menu Module id",
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
    public function index($id_menu_module)
    {
        $data = MenuCategory::whereIdMenuModule($id_menu_module)->orderBy('position')->get();

        return ApiResponseClass::sendResponse(MenuCategoryResource::collection($data), 'Menu Category Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/menu/categories",
     *  summary="Add a new menu category",
     *  tags={"Main - Menu Management"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_menu_module",
     *                  type="integer",
     *                  description="Menu Module id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Menu Category name"
     *              ),
     *              required={"id_menu_module", "name"},
     *              example={
     *                  "id_menu_module": 1,
     *                  "name": "Cash",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = MenuCategory::create([
                'id_menu_module' => $request->id_menu_module,
                'name'           => $request->name
            ]);

            DB::commit();

            return ApiResponseClass::sendResponse($data, 'Menu Category Created Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/menu/categories/{id_menu_module}/{id_menu_category}",
     *  summary="Get a menu category",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_module",
     *      description="Menu Module id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_category",
     *      description="Menu Category id",
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
    public function show($id_menu_module, $id_menu_category)
    {
        $data = MenuCategory::whereIdMenuModule($id_menu_module)->whereIdMenuCategory($id_menu_category)->get();

        return ApiResponseClass::sendResponse(MenuCategoryResource::collection($data), 'Menu Category Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/menu/categories/{id_menu_module}/{id_menu_category}",
     *  summary="Update a menu category",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_module",
     *      description="Menu Module id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_category",
     *      description="Menu Category id",
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
     *                  property="name",
     *                  type="string",
     *                  description="Menu Category name"
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
    public function update(Request $request, $id_menu_module, $id_menu_category)
    {
        DB::beginTransaction();
        try {
            $data = MenuCategory::find($id_menu_category);

            $data->update([
                'name' => $request->name
            ]);

            DB::commit();

            return ApiResponseClass::sendResponse($data, 'Menu Category Updated Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/menu/categories/{id_menu_module}/{id_menu_category}",
     *  summary="Delete a menu category",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_module",
     *      description="Menu Module id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_category",
     *      description="Menu Category id",
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
    public function destroy($id_menu_module, $id_menu_category)
    {
        try {
            $data = MenuCategory::findOrFail($id_menu_category);

            $data->delete();

            return ApiResponseClass::sendResponse($data, 'Menu Category Deleted Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
