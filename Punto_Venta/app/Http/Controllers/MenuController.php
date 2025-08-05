<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;

class MenuController extends Controller
{
    public function data()
    {
        return response()->json([
            'data' => []
        ]);
    }
}
