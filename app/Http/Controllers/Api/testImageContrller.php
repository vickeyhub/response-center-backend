<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class testImageContrller extends Controller
{
    public function upload(Request $request)
    {
        $imagePath = $request->file('image')->store('images', 'public');

        return response()->json(['imagePath' => $imagePath], 201);
    }
}
