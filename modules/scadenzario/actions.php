<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {

    case 'add':
        $data = post("data");
        $tipo = post("tipo");
        $da_pagare = post("da_pagare");
        $descrizione = post("descrizione");

        $dbo->query("INSERT INTO co_scadenziario(descrizione, tipo, data_emissione, scadenza, da_pagare, pagato, data_pagamento) VALUES(".prepare($descrizione).", ".prepare($tipo).", CURDATE(), ".prepare($data).", ".prepare($da_pagare).", '0', '0000-00-00')");
        $id_record = $dbo->lastInsertedID();
        
        flash()->info(tr('Scadenza inserita!'));
        break;

    case 'update':
        // Calcolo il totale da pagare
        $rs = $dbo->fetchArray('SELECT SUM(da_pagare) AS totale_da_pagare, iddocumento FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento=(SELECT iddocumento FROM co_scadenziario s WHERE id='.prepare($id_record).')');
        $totale_da_pagare = sum($rs[0]['totale_da_pagare'], null, Settings::get('Cifre decimali per importi'));

        $totale_utente = 0;

        // Verifico se il totale sommato è uguale al totale da pagare (solo per le scadenze delle fatture)
        foreach (post('scadenza') as $idscadenza => $da_pagare) {
            $totale_utente = sum($totale_utente, $da_pagare);
        }

        if ($totale_utente == $totale_da_pagare || empty($rs[0]['iddocumento'])) {
            foreach (post('scadenza') as $idscadenza => $da_pagare) {
                $dbo->query('UPDATE co_scadenziario SET da_pagare='.prepare($da_pagare).', pagato='.prepare(post('pagato')[$idscadenza]).', scadenza='.prepare(post('data')[$idscadenza]).' WHERE id='.prepare($idscadenza));
            }

            flash()->info(tr('Scadenze aggiornate!'));
        } else {
            flash()->error(tr('Il totale degli importi inseriti non corrisponde al totale da pagare!'));
        }

        break;

    case "delete":
        $dbo->query("DELETE FROM co_scadenziario WHERE id='".$id_record."'");
        flash()->info(tr('Scadenza eliminata!'));
        break;
}
