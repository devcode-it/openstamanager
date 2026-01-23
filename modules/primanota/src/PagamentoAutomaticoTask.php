<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\PrimaNota;

use Tasks\Manager;

/**
 * Task dedicato alla registrazione automatica dei pagamenti alla scadenza delle fatture.
 */
class PagamentoAutomaticoTask extends Manager
{
    public function needsExecution()
    {
        // Il task viene eseguito sempre dallo scheduler
        return true;
    }

    public function execute()
    {
        $result = [
            'response' => 1,
            'message' => tr('Pagamenti registrati correttamente!'),
        ];

        $database = database();
        
        // Recupero le scadenze scadute o in scadenza oggi con flag registra_pagamento attivo
        $scadenze = $database->fetchArray("
            SELECT 
                `co_scadenziario`.*,
                `co_documenti`.`idanagrafica`,
                `co_documenti`.`idtipodocumento`,
                `co_tipidocumento`.`dir`,
                `co_pagamenti`.`idconto_vendite`,
                `co_pagamenti`.`idconto_acquisti`
            FROM 
                `co_scadenziario`
                INNER JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`
                INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
                INNER JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
            WHERE 
                `co_pagamenti`.`registra_pagamento_automatico` = 1
                AND ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`)
                AND (
                    IF(`co_scadenziario`.`data_concordata`, `co_scadenziario`.`data_concordata`, `co_scadenziario`.`scadenza`) <= CURDATE()
                )
        ");

        $conta_registrati = 0;
        $conta_errori = 0;

        foreach ($scadenze as $scadenza) {
            try {
                // Calcolo l'importo da registrare
                $importo_da_pagare = abs($scadenza['da_pagare']);
                $importo_gia_pagato = abs($scadenza['pagato']);
                $importo_da_registrare = $importo_da_pagare - $importo_gia_pagato;

                if ($importo_da_registrare <= 0) {
                    continue;
                }

                // Determino il conto cliente/fornitore
                $id_conto_anagrafica = null;
                if ($scadenza['dir'] == 'entrata') {
                    // Fattura di vendita: conto cliente
                    $id_conto_anagrafica = $database->selectOne('an_anagrafiche', 'idconto_cliente', ['idanagrafica' => $scadenza['idanagrafica']])['idconto_cliente'];
                } else {
                    // Fattura di acquisto: conto fornitore
                    $id_conto_anagrafica = $database->selectOne('an_anagrafiche', 'idconto_fornitore', ['idanagrafica' => $scadenza['idanagrafica']])['idconto_fornitore'];
                }

                // Determino il conto di contropartita
                $id_conto_contropartita = null;
                if ($scadenza['dir'] == 'entrata') {
                    $id_conto_contropartita = $scadenza['idconto_vendite'];
                } else {
                    $id_conto_contropartita = $scadenza['idconto_acquisti'];
                }

                // Verifica che i conti siano valorizzati
                if (empty($id_conto_anagrafica)) {
                    $conta_errori++;
                    continue;
                }

                if (empty($id_conto_contropartita)) {
                    $conta_errori++;
                    continue;
                }

                // Crea il movimento di prima nota
                Movimento::registraPagamentoAutomatico(
                    $scadenza['id'],
                    $importo_da_registrare,
                    $id_conto_anagrafica,
                    $id_conto_contropartita,
                    $scadenza['dir']
                );

                $conta_registrati++;
            } catch (\Exception $e) {
                $conta_errori++;
            }
        }

        if ($conta_registrati == 0 && $conta_errori == 0) {
            $result = [
                'response' => 1,
                'message' => tr('Nessun pagamento da registrare'),
            ];
        } elseif ($conta_errori > 0) {
            $result = [
                'response' => 2,
                'message' => tr('Pagamenti registrati: _REGISTRATI_, Errori: _ERRORI_', [
                    '_REGISTRATI_' => $conta_registrati,
                    '_ERRORI_' => $conta_errori,
                ]),
            ];
        } else {
            $result = [
                'response' => 1,
                'message' => tr('_NUM_ pagamenti registrati correttamente!', [
                    '_NUM_' => $conta_registrati,
                ]),
            ];
        }

        return $result;
    }
}
