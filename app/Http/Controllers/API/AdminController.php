<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin;

class AdminController extends Controller
{
    /**
     * Register a new admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = new Admin([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
        ]);
        $admin->save();

        return response()->json(['message' => 'Admin created successfully']);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            // Authentication passed...
            $admin = Auth::guard('admin')->user();
            $token = $admin->createToken('admin')->accessToken;
            return response()->json(['token' => $token]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}
