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

        $totale_utente = 0;

        // Verifico se il totale sommato è uguale al totale da pagare (solo per le scadenze delle fatture)
        foreach (post('da_pagare') as $id_scadenza => $da_pagare) {
            $totale_utente = sum($totale_utente, $da_pagare);
        }

        if ($totale_utente == $totale_da_pagare || empty($scadenza['iddocumento'])) {
            foreach (post('da_pagare') as $id => $da_pagare) {
                $pagato = post('pagato')[$id];
                $scadenza = post('scadenza')[$id];
                $data_concordata = post('data_concordata')[$id];

                $nuova = post('nuova')[$id];
                if (empty($nuova)) {
                    $database->update('co_scadenziario', [
                        'da_pagare' => $da_pagare,
                        'pagato' => $pagato,
                        'scadenza' => $scadenza,
                        'data_concordata' => $data_concordata,
                    ], ['id' => $id]);

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
                }
            }

            flash()->info(tr('Scadenze aggiornate!'));
        } else {
            flash()->error(tr('Il totale degli importi inseriti non corrisponde al totale da pagare!'));
        }

        break;

    case 'delete':
        $dbo->query("DELETE FROM co_scadenziario WHERE id='".$id_record."'");
        flash()->info(tr('Scadenza eliminata!'));
        break;
}
