<?php

include_once Modules::filepath('Articoli', 'modutil.php');

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
                'idstatointervento' => $data['id_stato_intervento'],
                'idtipointervento' => $data['id_tipo_intervento'],
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
        }

        break;

    case 'sessioni_intervento':
        $data = $request['data'];

        add_tecnico($data['id_intervento'], $data['id_tecnico'], $data['orario_inizio'], $data['orario_fine']);

        break;

    case 'articolo_intervento':
        $data = $request['data'];

        // Inserisco movimento generico per questo articolo
        add_movimento_magazzino($data['id_articolo'], $data['qta'], [
            'idintervento' => $data['id_intervento'],
            'idautomezzo' => $data['id_automezzo'],
        ], 'Movimento da APP - Intervento '.$data['idintervento'], $data['data']);

        // collego articolo all'intervento in questione
        $q = "INSERT INTO mg_articoli_interventi(
            idarticolo,
            idintervento,
            descrizione,
            prezzo_vendita,
            idiva_vendita,
            idautomezzo,
            qta
        ) VALUES(
            '".$data['id_articolo']."',
            '".$data['id_intervento']."',
            (SELECT descrizione FROM mg_articoli WHERE mg_articoli.id=\"".$data['id_articolo'].'"),
            (SELECT prezzo_vendita FROM mg_articoli WHERE mg_articoli.id="'.$data['id_articolo']."\"),
            (SELECT valore FROM `zz_impostazioni` WHERE nome=\"Iva predefinita\"),
            '".$data['id_automezzo']."',
            '".$data['qta']."'
        )";
        $dbo->query($q);

        $dbo->query('UPDATE mg_articoli SET qta=(qta - '.$data['qta'].") WHERE id='".$data['id_articolo']."'");

        break;
}

return [
    'intervento',
    'sessione',
    'articolo_intervento',
];
