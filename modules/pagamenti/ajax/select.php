<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - codice_modalita_pagamento_fe
     */
    case 'pagamenti':
        $query = "SELECT id,
           CONCAT_WS(' - ', codice_modalita_pagamento_fe, descrizione) AS descrizione,
           (SELECT id FROM co_banche WHERE id_pianodeiconti3 = co_pagamenti.idconto_vendite LIMIT 1) AS id_banca_vendite,
           (SELECT id FROM co_banche WHERE id_pianodeiconti3 = co_pagamenti.idconto_acquisti LIMIT 1) AS id_banca_acquisti
        FROM co_pagamenti |where| GROUP BY descrizione ORDER BY descrizione ASC";

        foreach ($elements as $element) {
            $filter[] = 'co_pagamenti.id = '.prepare($element);
        }

        if (!empty($superselect['codice_modalita_pagamento_fe'])) {
            $where[] = 'codice_modalita_pagamento_fe = '.prepare($superselect['codice_modalita_pagamento_fe']);
        }

        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        $custom['id_banca_vendite'] = 'id_banca_vendite';
        $custom['id_banca_acquisti'] = 'id_banca_acquisti';

        break;
}
