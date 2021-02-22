<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $list = [
            'error',
            'warning',
            'info',
        ];

        $results = [];
        foreach ($list as $element) {
            $results[$element] = flash()->getMessage($element);
        }

        return response()->json($results);
    }
}
