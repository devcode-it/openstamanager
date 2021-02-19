<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HTMLBuilder
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $html_template = $response->content();
        $content = translateTemplate($html_template);
        $response->setContent($content);

        return $response;
    }
}
