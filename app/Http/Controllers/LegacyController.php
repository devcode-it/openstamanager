<?php

namespace App\Http\Controllers;

use App\Exceptions\LegacyExitException;
use App\Exceptions\LegacyRedirectException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegacyController extends Controller
{
    public function index(Request $request)
    {
        $path = substr($request->getPathInfo(), 1);

        // Gestione dell'output
        $output = self::simulate($path);
        $response = response($output);

        // Fix content-type per contenuti non HTML
        if (ends_with($path, '.js')) {
            $response = $response->header('Content-Type', 'application/javascript');
        } elseif (string_contains($path, 'pdfgen.php')) {
            $response = $response->header('Content-Type', 'application/pdf');
        }
        // Correzione header per API
        elseif (self::isApiRequest($path)) {
            $response = $response->header('Content-Type', 'application/json');
        }

        return $response;
    }

    protected static function isApiRequest($path)
    {
        // Fix per redirect all'API
        $api_request = false;
        if (in_array($path, ['api', 'api/', 'api/index.php'])) {
            $api_request = true;
        }

        return $api_request;
    }

    public static function simulate($path)
    {
        $base_path = base_path('legacy');

        // Fix per redirect all'API
        $api_request = self::isApiRequest($path);
        if ($api_request) {
            $path = 'api/index.php';
        }

        // Ricerca del file interessato
        $file = realpath($base_path.'/'.$path);
        if (strpos($file, $base_path) === false) {
            throw new NotFoundHttpException();
        }

        // Inclusione diretta del file
        ob_start();
        try {
            require $file;
        } catch (LegacyExitException $e) {
        } catch (LegacyRedirectException $e) {
            return Redirect::to($e->getMessage());
        }

        // Gestione dell'output
        $output = ob_get_clean();

        return $output;
    }
}
