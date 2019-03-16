<?php

namespace Middlewares\Authorization;

use Middlewares\Middleware;
use Util\Query;

/**
 * Classe per il controllo sui permessi di accesso relativi alle diverse sezioni del gestionale.
 *
 * @since 2.5
 */
class PermissionMiddleware extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        $route = $request->getAttribute('route');
        if (!$route) {
            return $next($request, $response);
        }

        $args = $route->getArguments();

        // Controllo sui permessi di accesso alla struttura
        $enabled = ['r', 'rw'];
        $permission = in_array($args['structure']->permission, $enabled);

        // Controllo sui permessi di accesso al record
        if (!empty($args['id_record'])) {
            $permission &= $this->recordAccess($args);
        }

        if (!$permission) {
            $response = $this->twig->render($response, 'errors\403.twig', $args);

            return $response->withStatus(403);
        } else {
            $response = $next($request, $response);
        }

        return $response;
    }

    protected function recordAccess($args)
    {
        Query::setSegments(false);
        $query = Query::getQuery($args['structure'], [
            'id' => $args['id_record'],
        ]);
        Query::setSegments(true);

        $has_access = !empty($query) ? $this->database->fetchNum($query) !== 0 : true;

        return $has_access;
    }
}
