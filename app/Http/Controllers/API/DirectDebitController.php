<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use app\Services\DirectDebitService;


class DirectDebitController extends Controller
{
    //
    protected $directDebitService;

    public function __construct(DirectDebitService $directDebitService)
    {
        $this->directDebitService = $directDebitService;
    }

    public function addProduct(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'productName' => 'required|string|max:255',
                'productDescription' => 'required|string',
            ]);

            // Call ProductService to add the product
            $product = $this->directDebitService->addProduct($validatedData);

            return response()->json(['success' => true, 'product' => $product], 201);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error adding product: ' . $e->getMessage());

            // Return error response
            return response()->json(['error' => 'Failed to add product. Please try again later.'], 500);
        }
    }

}
