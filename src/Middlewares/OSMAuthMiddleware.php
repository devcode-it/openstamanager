<?php
 
namespace Middlewares;
 
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Auth as OSMAuth;
use Models\User;

class OSMAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $base_path = $request->url();

        $base_path = substr($base_path, stripos($base_path, $request->host()) + strlen($request->host()));
        if (stripos($base_path, '/public/') !== false) {
            $base_path = substr($base_path, 0, stripos($base_path, '/public/'));
        }

        // Sicurezza della sessioni
        ini_set('session.cookie_samesite', 'lax');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.use_only_cookies', '1');

        session_set_cookie_params(0, $base_path, null, isHTTPS(true));
        session_start();

        $user = null;
        if (isset($_SESSION['id_utente'])) {
            $user = User::with('group')->find($_SESSION['id_utente']);
        }

        if ($user && !Auth::user()) Auth::login($user);
        if (!$user && Auth::user()) Auth::logout();
        
        return $next($request);
    }
}