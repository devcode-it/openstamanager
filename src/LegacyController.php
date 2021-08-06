<?php

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegacyController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function index(Request $request, $path)
    {
        //$path = substr($request->getPathInfo(), 1);

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
            $output = json_decode($output, true);
            $response = $response->header('Content-Type', 'application/json')
                ->setStatusCode($output['status']);
        }

        return $response;
    }

    public static function simulate($path)
    {
        $base_path = base_dir();

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

    protected static function isApiRequest($path)
    {
        // Fix per redirect all'API
        $api_request = false;
        if (in_array($path, ['api', 'api/', 'api/index.php'])) {
            $api_request = true;
        }

        return $api_request;
    }
}
