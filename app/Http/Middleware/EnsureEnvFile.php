<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class EnsureEnvFile
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Copia del file .env dal formato standard
        $env_file = base_path('.env');
        if (!file_exists($env_file)) {
            copy(base_path('.env.example'), $env_file);

            if (!file_exists($env_file)) {
                return response('Missing .env file');
            }

            // Generazione automatica delle key Laravel
            Artisan::call('key:generate');
            header('Refresh: 0;');

            return response('Missing app key');
        }

        return $next($request);
    }
}
