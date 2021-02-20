<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Models\Log;

class UserController extends Controller
{
    /** @var int Lunghezza minima della password */
    public static $min_length_password = 8;

    public function index()
    {
        $user = auth()->user();

        $tokens = $user->getApiTokens();
        $token = $tokens[0]['token'];

        $api = base_url().'/api/?token='.$token;

        $args = [
            'user' => $user,
            'api' => $api,
            'token' => $token,
            'sync_link' => $api.'&resource=sync',
        ];

        return view('user.info', $args);
    }

    public function password()
    {
        $args['min_length_password'] = self::$min_length_password;

        return view('user.password', $args);
    }

    public function save(Request $request)
    {
        $user = auth()->user();
        $password = $request->input('password');

        $user->password = $password;
        $user->save();

        flash()->info(tr('Password aggiornata!'));

        return redirect(route('user-info'));
    }

    public function logs()
    {
        $user = auth()->user();

        $logs = Log::orderBy('created_at')->limit(100);
        if (!$user->isAdmin()) {
            $logs = $logs->where('id_utente', '=', $user->id);
        }

        $logs = $logs->get();

        return view('user.logs', [
            'logs' => $logs,
        ]);
    }
}
