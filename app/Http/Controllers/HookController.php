<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Models\Hook;

class HookController extends Controller
{
    public function list(Request $request)
    {
        $hooks = Hook::all();

        $results = [];
        foreach ($hooks as $hook) {
            $results[] = [
                'id' => $hook->id,
                'name' => $hook->name,
            ];
        }

        return response()->json($results);
    }

    public function lock(Request $request, $hook_id)
    {
        $hook = Hook::find($hook_id);
        $token = $hook->lock();

        return response()->json($token);
    }

    public function execute(Request $request, $hook_id, $token)
    {
        $hook = Hook::find($hook_id);
        $results = $hook->execute($token);

        return response()->json($results);
    }

    public function response(Request $request, $hook_id)
    {
        $hook = Hook::find($hook_id);
        $results = $hook->response();

        return response()->json($results);
    }
}
