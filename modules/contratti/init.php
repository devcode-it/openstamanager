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

include_once __DIR__.'/../../core.php';

use Modules\Contratti\Contratto;

if (!empty($id_record)) {
    $contratto = Contratto::find($id_record);

    $record = $dbo->fetchOne('SELECT 
        `co_contratti`.*,
        `an_anagrafiche`.`tipo` AS tipo_anagrafica,
        `co_stati_contratti`.`is_fatturabile` AS is_fatturabile,
        `co_stati_contratti`.`is_pianificabile` AS is_pianificabile,
        `co_stati_contratti`.`is_bloccato` AS is_bloccato,
        `co_stati_contratti_lang`.`title` AS stato,
        GROUP_CONCAT(`my_impianti_contratti`.`id_impianto`) AS id_impianti,
        `co_contratti`.`id_categoria` as id_categoria,
        `co_contratti`.`id_sottocategoria` as id_sottocategoria
    FROM 
        `co_contratti`
        INNER JOIN `an_anagrafiche` ON `co_contratti`.`id_anagrafica` = `an_anagrafiche`.`id`
        INNER JOIN `co_stati_contratti` ON `co_contratti`.`id_stato` = `co_stati_contratti`.`id`
        LEFT JOIN `co_stati_contratti_lang` ON (`co_stati_contratti`.`id` = `co_stati_contratti_lang`.`id_record` AND `co_stati_contratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `my_impianti_contratti` ON `my_impianti_contratti`.`id_contratto` = `co_contratti`.`id`
    WHERE 
        `co_contratti`.`id`='.prepare($id_record));

    $elementi = $dbo->fetchArray('SELECT
        `co_documenti`.`id`,
        `co_documenti`.`data`,
        `co_documenti`.`numero`,
        `co_documenti`.`numero_esterno`,
        `co_tipi_documento_lang`.`title` AS tipo_documento,
        IF(`co_tipi_documento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
        GROUP_CONCAT(CONCAT(`co_righe_documenti`.`original_id`, " - ", `co_righe_documenti`.`qta`) SEPARATOR ", ") AS righe,
        `co_stati_documento_lang`.`title` AS stato_documento
    FROM
        `co_documenti`
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`id_documento` = `co_documenti`.`id`
        INNER JOIN `co_tipi_documento` ON `co_tipi_documento`.`id` = `co_documenti`.`id_tipo_documento`
        LEFT JOIN `co_tipi_documento_lang` ON (`co_tipi_documento_lang`.`id_record` = `co_tipi_documento`.`id` AND `co_tipi_documento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `co_stati_documento` ON `co_documenti`.`id_stato` = `co_stati_documento`.`id`
        LEFT JOIN `co_stati_documento_lang` ON (`co_stati_documento_lang`.`id_record` = `co_stati_documento`.`id` AND `co_stati_documento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        (`co_righe_documenti`.`original_document_id` = '.prepare($id_record).' AND `co_righe_documenti`.`original_document_type` = \'Modules\\\\Contratti\\\\Contratto\')
        OR `co_righe_documenti`.`id_contratto` = '.prepare($id_record).'
    GROUP BY
        `co_documenti`.`id`

    UNION

    SELECT
        `in_interventi`.`id`,
        `in_interventi`.`data_richiesta`,
        `in_interventi`.`codice`,
        NULL,
        \'Attività\',
        \'Interventi\' AS modulo,
        GROUP_CONCAT(CONCAT(`in_righe_interventi`.`original_id`, " - ", `in_righe_interventi`.`qta`) SEPARATOR ", ") AS righe,
        `in_stati_intervento_lang`.`title` AS stato_documento
    FROM
        `in_interventi`
        LEFT JOIN `in_righe_interventi` ON `in_righe_interventi`.`id_intervento` = `in_interventi`.`id`
        LEFT JOIN `in_stati_intervento` ON `in_interventi`.`id_stato` = `in_stati_intervento`.`id`
        LEFT JOIN `in_stati_intervento_lang` ON (`in_stati_intervento_lang`.`id_record` = `in_stati_intervento`.`id` AND `in_stati_intervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        (`in_righe_interventi`.`original_document_id` = '.prepare($id_record).' AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\Contratti\\\\Contratto\')
        OR `in_interventi`.`id_contratto` = '.prepare($id_record).'
    GROUP BY
        `in_interventi`.`id`

    ORDER BY `modulo`');

    $is_anagrafica_deleted = !$contratto->anagrafica;

    // Estrazione numero contratto originale
    $numero_contratto_originale = '';
    $id_contratto_prev = $contratto->id_contratto_prev;
    while ($id_contratto_prev != 0) {
        $contratto_prev = Contratto::find($id_contratto_prev);
        $id_contratto_prev = $contratto_prev->id_contratto_prev;
        $numero_contratto_originale = 'Orig. '.$contratto_prev->numero;
    }
}
