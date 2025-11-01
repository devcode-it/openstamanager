<?php

namespace Middlewares;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Models\User;
use Models\UserTokens;
use Symfony\Component\HttpFoundation\Response;

class APIAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(Request): (Response) $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if (!Auth::user()) {
            $token = $request->headers->get('X-API-Key');

            $user = null;
            if (!empty($token)) {
                $user_match = UserTokens::where('enabled', 1)->where('token', $token)->first();

                if (!empty($user_match)) {
                    $user = User::with('group')->find($user_match->id_utente);
                }
            }

            if ($user) {
                Auth::login($user, false);
                auth_osm()->identifyUser($user->id);

                return $next($request);
            }
        }

        // Disabilita autenticazione su base delle opzioni
        if (config('osm.api_development', false) && !$request->headers->has('X-API-Key')) {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthenticated.'], 401);
    }
}
