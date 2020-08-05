<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

switch (post('op')) {
    case 'add':
        $articolo = Articolo::find(post('idarticolo'));
        $tipo_movimento = post('tipo_movimento');
        $descrizione = post('movimento');
        $data = post('data');
        $qta = post('qta');

        $idsede_partenza = post('idsede_partenza');
        $idsede_destinazione = post('idsede_destinazione');

        if ($tipo_movimento == 'carico' || $tipo_movimento == 'scarico') {
            if ($tipo_movimento == 'carico') {
                $id_sede_azienda = $idsede_destinazione;
                $id_sede_controparte = 0;
            } elseif ($tipo_movimento == 'scarico') {
                $id_sede_controparte = 0;
                $id_sede_azienda = $idsede_partenza;

                $qta = -$qta;
            }

            // Registrazione del movimento con variazione della quantità
            $articolo->movimenta($qta, $descrizione, $data, 1, [
                'idsede_controparte' => $id_sede_controparte,
                'idsede_azienda' => $id_sede_azienda,
            ]);
        } elseif ($tipo_movimento == 'spostamento') {
            // Registrazione del movimento verso la sede di destinazione
            $articolo->registra($qta, $descrizione, $data, 1, [
                'idsede_controparte' => 0,
                'idsede_azienda' => $idsede_destinazione,
            ]);

            // Registrazione del movimento dalla sede di origine
            $articolo->registra(-$qta, $descrizione, $data, 1, [
                'idsede_controparte' => 0,
                'idsede_azienda' => $idsede_partenza,
            ]);
        }

        break;
}
