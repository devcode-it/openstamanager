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

    // Elenco clienti per l'applicazione
    case 'clienti':
        $query = "SELECT an_anagrafiche.idanagrafica AS id,
            an_anagrafiche.ragione_sociale,
            an_anagrafiche.piva,
            an_anagrafiche.codice_fiscale,
            an_anagrafiche.indirizzo,
            an_anagrafiche.indirizzo2,
            an_anagrafiche.citta,
            an_anagrafiche.cap,
            an_anagrafiche.provincia,
            an_anagrafiche.km,
            IFNULL(an_anagrafiche.lat, 0.00) AS latitudine,
            IFNULL(an_anagrafiche.lng, 0.00) AS longitudine,
            an_nazioni.nome AS nazione,
            an_anagrafiche.telefono,
            an_anagrafiche.fax,
            an_anagrafiche.cellulare,
            an_anagrafiche.email,
            an_anagrafiche.sitoweb,
            an_anagrafiche.note,
            an_anagrafiche.idzona,
            an_anagrafiche.deleted_at
        FROM an_anagrafiche
            LEFT OUTER JOIN an_nazioni ON an_anagrafiche.id_nazione=an_nazioni.id
        WHERE
            an_anagrafiche.deleted_at IS NULL AND
            an_anagrafiche.idanagrafica IN (SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica = (SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione = 'Cliente'))
        ORDER BY an_anagrafiche.ragione_sociale";

            $results = $dbo->fetchArray($query);

            $results['records'] = $database->fetchNum($query);
            $results['pages'] = $results['records'] / $length;
        break;

    // Elenco sedi per l'applicazione
    case 'sedi':
        $table = 'an_sedi';

        if (empty($order)) {
            $order[] = 'id';
        }

        break;
}

return [
    'an_anagrafiche',
    'clienti',
    'sedi',
];
