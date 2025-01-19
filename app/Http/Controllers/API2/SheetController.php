<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SheetController extends Controller
{
    //

public function externalContentPostMethod(Request $request)

{


    $sheetContentUrl = env('Sheet_Content');

    $date = now();
    //return $date;

    $data = [
        "name" => $request->name,
        "email" => $request->email,
        "phone" => $request->phone,
        "message" => $request->message,
        "CurrentTimeStamp" => $date
    ];

  $response = Http::post($sheetContentUrl, $data);
  if($response->failed())
  {
    return false;
  }
    $postContent = json_decode($response->body(), true);
    return response()->json($postContent);
}

}