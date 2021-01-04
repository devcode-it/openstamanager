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
        $base_path = realpath(__DIR__.'/../../../legacy/');
        $file = realpath($base_path.'/'.$path);
        if (strpos($file, $base_path) === false){
            throw new NotFoundHttpException();
        }

        ob_start();
        try {
            require $file;
        }  catch (LegacyExitException $e) {
        }catch (LegacyRedirectException $e){
            return Redirect::to($e->getMessage());
        }

        $output = ob_get_clean();

        return new Response($output);
    }
}
