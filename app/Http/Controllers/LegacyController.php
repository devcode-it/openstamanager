<?php

namespace App\Http\Controllers;

use App\Exceptions\LegacyExitException;
use App\Exceptions\LegacyRedirectException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegacyController extends Controller
{
    public function index($path = 'index.php')
    {
        $base_path = base_path('legacy');
        $file = realpath($base_path.'/'.$path);
        if (strpos($file, $base_path) === false) {
            throw new NotFoundHttpException();
        }

        ob_start();
        try {
            require $file;
        } catch (LegacyExitException $e) {
        } catch (LegacyRedirectException $e) {
            return Redirect::to($e->getMessage());
        }

        $output = ob_get_clean();

        $response = response($output);

        // Fix content-type per contenuti non HTML
        if (ends_with($path, '.js')) {
            $response = $response->header('Content-Type', 'application/javascript');
        }

        return $response;
    }
}
