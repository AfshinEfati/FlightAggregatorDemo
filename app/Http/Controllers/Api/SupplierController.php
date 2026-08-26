<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Jobs\PollSupplierJob;
use App\Models\Route;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/admin/suppliers",
     *      operationId="getSuppliersList",
     *      tags={"Suppliers"},
     *      summary="Get list of suppliers",
     *      description="Returns list of suppliers",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        return SupplierResource::collection(Supplier::all());
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/admin/suppliers/{id}",
     *      operationId="updateSupplier",
     *      tags={"Suppliers"},
     *      summary="Update existing supplier",
     *      description="Update supplier data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Supplier id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateSupplierRequest")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    /**
     * @OA\Post(
     *      path="/api/v1/admin/suppliers/{id}/poll",
     *      operationId="pollSupplier",
     *      tags={"Suppliers"},
     *      summary="Manually trigger supplier poll",
     *      description="Dispatches polling jobs for all routes for this supplier",
     *      @OA\Parameter(
     *          name="id",
     *          description="Supplier id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function poll(Supplier $supplier): JsonResponse
    {
        $routes = Route::all();

        foreach ($routes as $route) {
            PollSupplierJob::dispatch($supplier, $route);
        }

        return response()->json([
            'message' => "Polling jobs dispatched for supplier: {$supplier->name}",
        ]);
    }
}

/**
 * @OA\Schema(
 *     schema="UpdateSupplierRequest",
 *     type="object",
 *     title="UpdateSupplierRequest",
 *     properties={
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="base_url", type="string"),
 *         @OA\Property(property="poll_interval_minutes", type="integer"),
 *         @OA\Property(property="is_active", type="boolean"),
 *         @OA\Property(property="timeout_seconds", type="integer"),
 *         @OA\Property(property="retry_attempts", type="integer"),
 *     }
 * )
 */
