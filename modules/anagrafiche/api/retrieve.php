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
            an_sedi.piva,
            an_sedi.codice_fiscale,
            an_sedi.indirizzo,
            an_sedi.indirizzo2,
            an_sedi.citta,
            an_sedi.cap,
            an_sedi.provincia,
            an_sedi.km,
            IFNULL(an_sedi.lat, 0.00) AS latitudine,
            IFNULL(an_sedi.lng, 0.00) AS longitudine,
            an_sedi.telefono,
            an_sedi.fax,
            an_sedi.cellulare,
            an_sedi.email,
            an_sedi.idzona,
            an_nazioni.nome AS nazione,
            an_anagrafiche.sitoweb,
            an_anagrafiche.note,
            an_anagrafiche.deleted_at
        FROM an_anagrafiche
            INNER JOIN `an_sedi` ON `an_sedi`.`id` = `an_anagrafiche`.`id_sede_legale`
            LEFT OUTER JOIN `an_nazioni` ON `an_sedi`.`id_nazione` = `an_nazioni`.`id`
        WHERE
            an_anagrafiche.deleted_at IS NULL AND
            an_anagrafiche.idanagrafica IN (SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE id_tipo_anagrafica = (SELECT id FROM an_tipianagrafiche WHERE descrizione = 'Cliente'))
        ORDER BY an_anagrafiche.ragione_sociale";
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
