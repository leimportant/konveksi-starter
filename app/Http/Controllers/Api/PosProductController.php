<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosProduct;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PosProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(): JsonResponse
    {
        try {
            $products = PosProduct::with(['category', 'uom'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products'
            ], 500);
        }
    }

    /**
     * Store a new product
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:150',
                'category_id' => 'required|exists:mst_category,id',
                'uom_id' => 'required|string|max:10',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'remark' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = PosProduct::create([
                ...$request->all(),
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product'
            ], 500);
        }
    }

    /**
     * Display the specified product
     */
    public function show($id): JsonResponse
    {
        try {
            $product = PosProduct::with(['category', 'uom'])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:150',
                'category_id' => 'required|exists:mst_category,id',
                'uom_id' => 'required|string|max:10',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'remark' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = PosProduct::findOrFail($id);
            $product->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product'
            ], 500);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy($id): JsonResponse
    {
        try {
            $product = PosProduct::findOrFail($id);
            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product'
            ], 500);
        }
    }
}