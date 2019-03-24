<?php

namespace Controllers;

use Prints;

class PrintController extends Controller
{
    public function view($request, $response, $args)
    {
        $link = pathFor('print-open', [
            'print_id' => $args['print_id'],
            'record_id' => $args['record_id'],
        ]);
        $args['link'] = ROOTDIR.'/assets/pdfjs/web/viewer.html?file='.$link;

        $response = $this->twig->render($response, 'uploads\frame.twig', $args);

        return $response;
    }

    public function open($request, $response, $args)
    {
        /*
        $id_print = $args['print_id'];

        // Retrocompatibilità
        $ptype = get('ptype');
        if (!empty($ptype)) {
            $print = $this->database->fetchOne('SELECT id, previous FROM zz_prints WHERE directory = '.prepare($ptype).' ORDER BY predefined DESC LIMIT 1');
            $id_print = $print['id'];

            $id_record = !empty($id_record) ? $id_record : get($print['previous']);
            $args['record_id'] = $id_record;
        }*/

        $pdf = Prints::render($args['print_id'], $args['record_id']);

        $response = $response
            ->withHeader('Content-Type', 'application/pdf')
            ->write($pdf);

        return $response;
    }
}
