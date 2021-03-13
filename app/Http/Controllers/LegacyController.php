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
        $base_path = base_path('legacy');

        // Fix per redirect all'API
        $api_request = false;
        if (in_array($path, ['api', 'api/', 'api/index.php'])) {
            $path = 'api/index.php';
            $api_request = true;
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
        $response = response($output);

        // Fix content-type per contenuti non HTML
        if (ends_with($path, '.js')) {
            $response = $response->header('Content-Type', 'application/javascript');
        } elseif (string_contains($path, 'pdfgen.php')) {
            $response = $response->header('Content-Type', 'application/pdf');
        } elseif ($api_request) {
            $response = $response->header('Content-Type', 'application/json');
        }

        return $response;
    }
}
