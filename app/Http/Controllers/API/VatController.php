<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Vat;
use Auth;

class VatController extends Controller
{
    //

    public function createVat(Request $request)
    {
    $getAdmin = Auth::user();
    $getAd = $getAdmin -> usertype;
         if($getAd === 'admin')
    {
    $vat = Vat::create([
        'vat' => $request->vat,
        'admin_id' => Auth::user()->id
    ]);
    return response()->json($vat);
}else{
    return response()->json('You are not authorize to perform this action');
        }

    
    }



    public function getVatMethod()
    {
        $getVat = Vat::all();
        return $getVat;
    }
}
