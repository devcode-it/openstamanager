<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $data = post('data');
        $tipo = post('tipo');
        $da_pagare = post('da_pagare');
        $descrizione = post('descrizione');

        $dbo->query('INSERT INTO co_scadenziario(descrizione, tipo, data_emissione, scadenza, da_pagare, pagato) VALUES('.prepare($descrizione).', '.prepare($tipo).', CURDATE(), '.prepare($data).', '.prepare(-$da_pagare).", '0')");
        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Scadenza inserita!'));
        break;

    case 'update':
        $tipo = post('tipo');
        $descrizione = post('descrizione');
        $iddocumento = post('iddocumento') ?: 0;

        // Calcolo il totale da pagare
        $scadenza = $dbo->fetchOne('SELECT SUM(da_pagare) AS totale_da_pagare, iddocumento FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento=(SELECT iddocumento FROM co_scadenziario s WHERE id='.prepare($id_record).')');
        $totale_da_pagare = sum($scadenza['totale_da_pagare'], null);

        // Verifico se il totale sommato è uguale al totale da pagare (solo per le scadenze delle fatture)
        $totale_utente = 0;
        foreach (post('da_pagare') as $id_scadenza => $da_pagare) {
            $totale_utente = sum($totale_utente, $da_pagare);
        }

        $totale_pagato = 0;
        $id_scadenza_non_completa = null;
        foreach (post('da_pagare') as $id => $da_pagare) {
            $pagato = post('pagato')[$id];
            $scadenza = post('scadenza')[$id];
            $data_concordata = post('data_concordata')[$id];

            $pagato = floatval($pagato);
            $da_pagare = floatval($da_pagare);

            $totale_pagato = sum($totale_pagato, $pagato);

            $id_scadenza = post('id_scadenza')[$id];
            if (!empty($id_scadenza)) {
                $database->update('co_scadenziario', [
                    'descrizione' => $descrizione,
                    'da_pagare' => $da_pagare,
                    'pagato' => $pagato,
                    'scadenza' => $scadenza,
                    'data_concordata' => $data_concordata,
                ], ['id' => $id_scadenza]);

                if ($da_pagare == 0) {
                    $database->delete('co_scadenziario', ['id' => $id]);
                }
            } else {
                $database->insert('co_scadenziario', [
                    'descrizione' => $descrizione,
                    'tipo' => $tipo,
                    'iddocumento' => $iddocumento,
                    'da_pagare' => $da_pagare,
                    'pagato' => $pagato,
                    'scadenza' => $scadenza,
                    'data_concordata' => $data_concordata,
                    'data_emissione' => date('Y-m-d'),
                ]);

                $id_scadenza = $database->lastInsertedID();
            }

            if ($pagato != $da_pagare) {
                $id_scadenza_non_completa = $id_scadenza;
            }
        }

        flash()->info(tr('Scadenze aggiornate!'));

        if ($totale_pagato == $totale_da_pagare) {
            flash()->warning(tr('Le scadenze sono state completate!'));

            redirect(ROOTDIR.'/controller.php?id_module='.$id_module);
            Filter::set('post', 'backto', null);
        } else {
            $id_record = $id_scadenza_non_completa;
        }

        if ($totale_da_pagare != $totale_utente) {
            flash()->error(tr('ATTENZIONE: il totale degli importi inseriti non corrisponde al totale da pagare!'));
        }

        break;

    case 'delete':
        $dbo->query("DELETE FROM co_scadenziario WHERE id='".$id_record."'");
        flash()->info(tr('Scadenza eliminata!'));
        break;
}
