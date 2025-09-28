<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EncryptionKeyService
{
    protected $apiKey = '93cdbd1e3ae649b3b5e173ffb87d95d2993de430b81a4415b8c2f309356d2278';
    protected $endpoint = 'https://www.sandbox.paythru.ng/debit/api/v1/directdebit/Pipeline/reset';

    public function generateKey()
    {
        $response = Http::withHeaders([
            'API_key' => $this->apiKey,
        ])->get($this->endpoint);

        $responseData = $response->json();

        if ($responseData['succeed']) {
            return $responseData['data'];
        } else {
            throw new \Exception('Failed to generate encryption key: ' . $responseData['message']);
        }
    }
}

