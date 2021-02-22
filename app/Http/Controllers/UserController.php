<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Models\Log;

class UserController extends Controller
{
    /** @var int Lunghezza minima della password */
    public static $min_length_password = 8;

    /**
     * Gestisce la pagina di informazioni dell'utente.
     */
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

    /**
     * Gestisce la visualizzazione del modal per la modifica della password.
     */
    public function password()
    {
        $args['min_length_password'] = self::$min_length_password;

        return view('user.password', $args);
    }

    /**
     * Gestisce il salvataggio della nuova password per l'utente.
     */
    public function savePassword(Request $request)
    {
        $user = auth()->user();
        $password = $request->input('password');

        $user->password = $password;
        $user->save();

        flash()->info(tr('Password aggiornata!'));

        return redirect(route('user-info'));
    }

    /***
     * Gestisce la visualizzazione dei log di accesso dell'utente corrente al gestionale.
     */
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

    /**
     * Gestisce la visualizzazione del modal per la modifica della foto utente.
     */
    public function photo()
    {
        return view('user.photo');
    }

    /**
     * Gestisce il salvataggio della nuova foto utente.
     */
    public function savePhoto(Request $request)
    {
        $user = auth()->user();
        $file = $request->file('photo');

        $user->photo = $file;
        $user->save();

        flash()->info(tr('Foto utente aggiornata!'));

        return redirect(route('user-info'));
    }
}
