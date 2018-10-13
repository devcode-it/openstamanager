<?php

include_once Modules::filepath('Articoli', 'modutil.php');

use Modules\Interventi\Articolo;
use Modules\Interventi\Intervento;
use Modules\Articoli\Articolo as ArticoloOriginale;

switch ($resource) {
    case 'intervento':
        $data = $request['data'];

        // Se l'idintervento non esiste, creo un nuovo intervento
        $formato = setting('Formato codice intervento');
        $template = str_replace('#', '%', $formato);

        $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice=(SELECT MAX(CAST(codice AS SIGNED)) FROM in_interventi) AND codice LIKE '.prepare($template).' ORDER BY codice DESC LIMIT 0,1');
        $codice = Util\Generator::generate($formato, $rs[0]['codice']);

        if (empty($codice)) {
            $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice LIKE '.prepare($template).' ORDER BY codice DESC LIMIT 0,1');

            $codice = Util\Generator::generate($formato, $rs[0]['codice']);
        }

        if (!empty($codice) && !empty($data['id_anagrafica']) && !empty($data['id_tipo_intervento'])) {
            // Salvataggio modifiche intervento
            $dbo->insert('in_interventi', [
                'idanagrafica' => $data['id_anagrafica'],
                'idclientefinale' => 0,
                'id_stato' => $data['id_stato_intervento'],
                'id_tipo_intervento' => $data['id_tipo_intervento'],
                'idsede' => 0,
                'idautomezzo' => 0,

                'codice' => $codice,
                'data_richiesta' => $data['data_richiesta'],
                'richiesta' => $data['richiesta'],
                'descrizione' => $data['descrizione'],
                'informazioniaggiuntive' => $data['informazioni_aggiuntive'],
            ]);

            $response['id'] = $dbo->lastInsertedID();
            $response['codice'] = $codice;

            $start = date('Y-m-d H:i:s');
            $end = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($start)));

            add_tecnico($response['id'], $user['idanagrafica'], $start, $end);
        }

        break;

    case 'sessione':
        $data = $request['data'];

        add_tecnico($data['id_intervento'], $user['idanagrafica'], $data['orario_inizio'], $data['orario_fine']);

        break;

    case 'articolo_intervento':
        $data = $request['data'];

        $originale = ArticoloOriginale::find($data['id_articolo']);
        $intervento = Intervento::find($data['id_intervento']);
        $articolo = Articolo::make($intervento, $originale, $data['id_automezzo']);

        $articolo->qta = $data['qta'];
        $articolo->um = $data['um'];

        $articolo->save();

        break;
}

return [
    'intervento',
    'sessione',
    'articolo_intervento',
];
