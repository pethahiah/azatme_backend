<?php

namespace App\Services;

use App\Charge;

class ChargeService
{
    public function applyCharges($latestCharge): bool
    {
        $applyCharges = false;

        $charges = Charge::whereIn('product_affected', ['all_products', 'refundme', 'ajo', 'kontribute'])->get();

        // Check if charges apply for any of the specified products
        if ($charges->isNotEmpty()) {
            $applyCharges = true;
        }

        return $applyCharges;
    }
}
