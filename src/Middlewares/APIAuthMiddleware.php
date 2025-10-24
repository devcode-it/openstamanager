<?php
 
namespace Middlewares;
 
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Auth as OSMAuth;
use Models\UserTokens;
use Models\User;

class APIAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::user()){
            $token = $request->headers->get('X-API-Key');
            
            $user = null;
            if (!empty($token)) {
                $user_match = UserTokens::where('enabled', 1)->find($token);

                if ($user_match) $user = User::with('group')->find($user_match->id_utente);
            }

            if ($user) Auth::once($user);
        }
        
        return $next($request);
    }
}