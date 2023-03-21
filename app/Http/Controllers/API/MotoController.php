<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Moto;
use Auth;

class MotoController extends Controller
{

    public function moto(Request $request)
    {

    $expense = Moto::create([
        'option' => $request->option,
        'user_id' => Auth::user()->id
    ]);

    return response()->json($expense);
    }

    public function getMotoMethod()
    {
        $getMoto = moto::all();
        return $getMoto;
    }

}
