<?php

switch ($resource) {
    case 'an_anagrafiche':
        $table = 'an_anagrafiche';

        if (empty($order)) {
            $order[] = 'idanagrafica';
        }

        if (empty($where['deleted_at'])) {
            $where['deleted_at'] = null;
        }

        break;

    case 'clienti':
        $q = 'SELECT AN.idanagrafica,
                    AN.ragione_sociale,
                    AN.piva,
                    AN.codice_fiscale,
                    AN.indirizzo,
                    AN.indirizzo2,
                    AN.citta,
                    AN.cap,
                    AN.provincia,
                    AN.km,
                    IFNULL(AN.lat, 0.00) AS latitudine,
                    IFNULL(AN.lng, 0.00) AS longitudine,
                    NAZIONE.nome AS nazione,
                    AN.telefono,
                    AN.fax,
                    AN.cellulare,
                    AN.email,
                    AN.sitoweb,
                    AN.note,
                    AN.idzona,
                    AN.deleted_at
                FROM (an_anagrafiche AS AN
                        LEFT OUTER JOIN an_nazioni NAZIONE ON AN.id_nazione=NAZIONE.id)
                HAVING  1=1 AND
                        AN.deleted_at IS NULL AND
                        AN.idanagrafica IN (SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica=1)
                ORDER BY AN.ragione_sociale';

            $results = $dbo->fetchArray($q);

        break;
}

return [
    'an_anagrafiche',
    'clienti',
];
