<?php

namespace App\Services;

use App\DirectDebitProduct;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class DirectDebitService
{
    public function addProduct(array $requestData): DirectDebitProduct
    {
        // Initialize product variable
        $product = null;

        try {
            // Store data in DirectDebitProduct
            $product = new DirectDebitProduct();
            $product->productName = $requestData['productName'];
            $product->isPacketBased = false;
            $product->productDescription = $requestData['productDescription'];
            $product->isUserResponsibleForCharges = true;
            $product->classification = "FixedContract";
            $product->partialCollectionEnabled = false;
            $product->save();

            // Serialize product object into JSON
            $productData = json_encode($product);

            // Send product data to third-party API
            $apiUrl = env("Paythru_Direct_Debt_Test_Url");
            $response = Http::post($apiUrl, ['product' => $productData]);

            // Check if API call was successful
            if ($response->successful()) {
                // Decode JSON response
                $responseData = $response->json();

                // Check if the API response indicates success
                if ($responseData['succeed']) {
                    // Retrieve product ID from API response
                    $productId = $responseData['data']['productId'];

                    // Update product with API-provided product ID
                    $product->productId = $productId;
                    $product->save();

                    // Log the response
                    Log::info('Response from paythru API: ' . $response->body());
                } else {
                    // Log the error response
                    Log::error('Error response from paythru API: ' . $response->body());

                    // Throw an exception with the error message from the API response
                    throw new Exception('Failed to create product. paythru returned an error: ' . $responseData['message']);
                }
            } else {
                // Log the error response
                Log::error('Error response from paythru: ' . $response->body());

                // Throw an exception if the API call was not successful
                throw new Exception('Failed to create product. paythru returned an error.');
            }
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error sending data to paythru: ' . $e->getMessage());

            // If product was created before the exception, delete it
            if ($product && $product->exists) {
                $product->delete();
            }
            // Re-throw the exception to be caught by the caller
            throw $e;
        }
        return $product;
    }



    public function createMandate()
    {

    }

}
