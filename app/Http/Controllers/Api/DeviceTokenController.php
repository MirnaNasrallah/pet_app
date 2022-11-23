<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    public function storeToken(Request $request)
    {
       $request->validate([
        'token' => 'required',
       'device' => 'required',
       'type' => 'nullable'
    ]);
    $user = Auth::guard('sanctum')->user();
    $user->deviceTokens()->create([
        'token' => $request->post('token')
    ]);
    return;

    }
    public function saveToken(Request $request)
    {
        auth('sanctum')->user()->update(['device_token'=>$request->token]);
        return response()->json(['token saved successfully.']);
    }
}
