<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCalendarPeriod
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $request_period = [
            'period_start' => $request->query('period_start'),
            'period_end' => $request->query('period_end'),
        ];

        // Calendario
        // Periodo di visualizzazione
        if (!empty($request_period['period_start'])) {
            session(null, $request_period);
        }
        // Dal 01-01-yyy al 31-12-yyyy
        elseif (session('period_start') === null) {
            session(null, [
                'period_start' => date('Y').'-01-01',
                'period_end' => date('Y').'-12-31',
            ]);
        }

        return $next($request);
    }
}
