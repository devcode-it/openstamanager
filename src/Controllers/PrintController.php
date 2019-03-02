<?php

namespace Controllers;

class PrintController extends Controller
{
    public function index($request, $response, $args)
    {
        $filename = !empty($filename) ? $filename : null;
        $id_print = $args['print_id'];

        // Retrocompatibilità
        $ptype = get('ptype');
        if (!empty($ptype)) {
            $print = $dbo->fetchArray('SELECT id, previous FROM zz_prints WHERE directory = '.prepare($ptype).' ORDER BY predefined DESC LIMIT 1');
            $id_print = $print[0]['id'];

            $id_record = !empty($id_record) ? $id_record : get($print[0]['previous']);
        }

        $pdf = Prints::render($id_print, $id_record, $filename);
        $response = $response->write($pdf);

        return $response;
    }
}
