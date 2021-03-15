<?php

namespace App\Http\Controllers;

use App\OSM\Prints\Template;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function view(Request $request)
    {
        $link = route('print-open', [
            'print_id' => $args['print_id'],
            'record_id' => $args['record_id'],
        ]);
        $args['link'] = base_url().'/assets/pdfjs/web/viewer.html?file='.$link;

        $response = $this->twig->render($response, '@resources/uploads/frame.twig', $args);

        return $response;
    }

    public function open(Request $request)
    {
        $print = Template::find($args['print_id']);
        $manager = $print->getManager();

        $pdf = $manager->render();

        return response()->setContent($pdf)
            ->header('Content-Type', 'application/pdf');
    }
}
