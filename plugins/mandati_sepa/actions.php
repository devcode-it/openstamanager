<?php

include_once __DIR__.'/init.php';

switch (filter('op')) {
    case 'mandato':
        $dati = [
            'id_banca' => $id_record,
            'id_mandato' => post('id_mandato'),
            'data_firma_mandato' => post('data_firma_mandato'),
            'singola_disposizione' => post('singola_disposizione'),
        ];

        if (empty($mandato['id'])) {
            $database->insert('co_mandati_sepa', $dati);
        } else {
            $database->update('co_mandati_sepa', $dati, ['id' => $mandato['id']]);
        }

        flash()->info(tr('Mandato SEPA aggiornato!'));

        break;

    case 'delete':
        if (empty($mandato['id'])) {
            $database->delete('co_mandati_sepa', ['id' => $mandato['id']]);
        }

        flash()->info(tr('Mandato SEPA eliminato!'));

        break;
}
