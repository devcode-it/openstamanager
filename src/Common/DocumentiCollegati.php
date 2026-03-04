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

namespace Common;

use Models\Locale;
use Modules;
use Translator;

/**
 * Classe per la gestione ottimizzata dei documenti collegati
 * Gestisce il caricamento via AJAX per migliorare le performance
 * Può essere utilizzata da tutti i moduli del sistema
 */
class DocumentiCollegati
{
    /**
     * Recupera i documenti collegati a un record specifico
     *
     * @param int $id_record ID del record
     * @param string $tipo_record Tipo di record (es. 'intervento', 'fattura_vendita', ecc.)
     * @return array Array di documenti collegati
     */
    public static function getDocumenti($id_record, $tipo_record = 'intervento')
    {
        if (empty($id_record) || !is_numeric($id_record)) {
            return [];
        }

        try {
            // In base al tipo di record, esegui la query appropriata
            switch ($tipo_record) {
                case 'intervento':
                    return self::getDocumentiIntervento($id_record);
                case 'fattura_vendita':
                    return self::getDocumentiFatturaVendita($id_record);
                case 'fattura_acquisto':
                    return self::getDocumentiFatturaAcquisto($id_record);
                case 'contratto':
                    return self::getDocumentiContratto($id_record);
                case 'preventivo':
                    return self::getDocumentiPreventivo($id_record);
                case 'ordine':
                    return self::getDocumentiOrdine($id_record);
                case 'ddt':
                    return self::getDocumentiDDT($id_record);
                default:
                    return [];
            }
        } catch (\Exception $e) {
            throw new \Exception('Errore nel recupero dei documenti: '.$e->getMessage());
        }
    }

    /**
     * Recupera i documenti collegati a un intervento
     *
     * @param int $id_intervento ID dell'intervento
     * @return array Array di documenti collegati
     */
    private static function getDocumentiIntervento($id_intervento)
    {
        global $dbo;

        // Query ottimizzata con eager loading delle traduzioni
        $query = 'SELECT 
            `co_documenti`.*,
            `co_tipidocumento_lang`.`title` AS tipo_documento,
            `co_statidocumento_lang`.`title` AS stato_documento,
            `co_tipidocumento`.`dir`
        FROM `co_documenti`
        INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        LEFT JOIN `co_tipidocumento_lang` ON (
            `co_tipidocumento_lang`.`id_record` = `co_documenti`.`idtipodocumento` AND 
            `co_tipidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
        LEFT JOIN `co_statidocumento_lang` ON (
            `co_statidocumento_lang`.`id_record` = `co_documenti`.`idstatodocumento` AND 
            `co_statidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `co_documenti`.`id` IN (
            SELECT `iddocumento` 
            FROM `co_righe_documenti` 
            WHERE `idintervento` = '.prepare($id_intervento).'
        )
        ORDER BY `co_documenti`.`data` DESC';

        try {
            $result = $dbo->fetchArray($query);
            return $result;
        } catch (\Exception $e) {
            throw new \Exception('Errore nella query dei documenti: '.$e->getMessage());
        }
    }

    /**
     * Recupera i documenti collegati a una fattura di vendita
     *
     * @param int $id_fattura ID della fattura
     * @return array Array di documenti collegati
     */
    private static function getDocumentiFatturaVendita($id_fattura)
    {
        global $dbo;

        $documenti = [];

        // Recupera gli interventi collegati
        $query_interventi = 'SELECT 
            `in_interventi`.`id`,
            `in_interventi`.`codice`,
            `in_interventi`.`data_richiesta`,
            \'Attività\' AS tipo_documento,
            \'Interventi\' AS modulo,
            `in_statiintervento_lang`.`title` AS stato_documento
        FROM `in_interventi`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `in_interventi`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Interventi\\\\Intervento\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )
        INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`id`
        LEFT JOIN `in_statiintervento_lang` ON (
            `in_statiintervento_lang`.`id_record` = `in_interventi`.`idstatointervento` AND 
            `in_statiintervento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        GROUP BY `in_interventi`.`id`
        ORDER BY `in_interventi`.`data_richiesta` DESC';

        $interventi = $dbo->fetchArray($query_interventi);
        $documenti = array_merge($documenti, $interventi);

        // Recupera i preventivi collegati
        $query_preventivi = 'SELECT 
            `co_preventivi`.`id`,
            `co_preventivi`.`numero`,
            `co_preventivi`.`data_bozza` AS data,
            \'Preventivo\' AS tipo_documento,
            \'Preventivi\' AS modulo,
            `co_statipreventivi_lang`.`title` AS stato_documento
        FROM `co_preventivi`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `co_preventivi`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )
        INNER JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
        LEFT JOIN `co_statipreventivi_lang` ON (
            `co_statipreventivi_lang`.`id_record` = `co_preventivi`.`idstato` AND 
            `co_statipreventivi_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        GROUP BY `co_preventivi`.`id`
        ORDER BY `co_preventivi`.`data_bozza` DESC';

        $preventivi = $dbo->fetchArray($query_preventivi);
        $documenti = array_merge($documenti, $preventivi);

        // Recupera i DDT collegati
        $query_ddt = 'SELECT 
            `dt_ddt`.`id`,
            `dt_ddt`.`numero`,
            `dt_ddt`.`numero_esterno`,
            `dt_ddt`.`data`,
            `dt_tipiddt_lang`.`title` AS tipo_documento,
            IF(`dt_tipiddt`.`dir` = \'entrata\', \'Ddt in uscita\', \'Ddt in entrata\') AS modulo,
            `dt_statiddt_lang`.`title` AS stato_documento
        FROM `dt_ddt`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `dt_ddt`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\DDT\\\\DDT\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )
        INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
        LEFT JOIN `dt_tipiddt_lang` ON (
            `dt_tipiddt_lang`.`id_record` = `dt_ddt`.`idtipoddt` AND 
            `dt_tipiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `dt_statiddt` ON `dt_ddt`.`idstatoddt` = `dt_statiddt`.`id`
        LEFT JOIN `dt_statiddt_lang` ON (
            `dt_statiddt_lang`.`id_record` = `dt_ddt`.`idstatoddt` AND 
            `dt_statiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        GROUP BY `dt_ddt`.`id`
        ORDER BY `dt_ddt`.`data` DESC';

        $ddt = $dbo->fetchArray($query_ddt);
        $documenti = array_merge($documenti, $ddt);

        // Recupera gli ordini collegati
        $query_ordini = 'SELECT 
            `or_ordini`.`id`,
            `or_ordini`.`numero`,
            `or_ordini`.`numero_esterno`,
            `or_ordini`.`data`,
            `or_tipiordine_lang`.`title` AS tipo_documento,
            IF(`or_tipiordine`.`dir` = \'entrata\', \'Ordini cliente\', \'Ordini fornitore\') AS modulo,
            `or_statiordine_lang`.`title` AS stato_documento
        FROM `or_ordini`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `or_ordini`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Ordini\\\\Ordine\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )
        INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
        LEFT JOIN `or_tipiordine_lang` ON (
            `or_tipiordine_lang`.`id_record` = `or_ordini`.`idtipoordine` AND 
            `or_tipiordine_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id`
        LEFT JOIN `or_statiordine_lang` ON (
            `or_statiordine_lang`.`id_record` = `or_ordini`.`idstatoordine` AND 
            `or_statiordine_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        GROUP BY `or_ordini`.`id`
        ORDER BY `or_ordini`.`data` DESC';

        $ordini = $dbo->fetchArray($query_ordini);
        $documenti = array_merge($documenti, $ordini);

        // Recupera i contratti collegati
        $query_contratti = 'SELECT 
            `co_contratti`.`id`,
            `co_contratti`.`numero`,
            `co_contratti`.`data_bozza` AS data,
            \'Contratto\' AS tipo_documento,
            \'Contratti\' AS modulo,
            `co_staticontratti_lang`.`title` AS stato_documento
        FROM `co_contratti`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `co_contratti`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Contratti\\\\Contratto\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )
        INNER JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
        LEFT JOIN `co_staticontratti_lang` ON (
            `co_staticontratti_lang`.`id_record` = `co_contratti`.`idstato` AND 
            `co_staticontratti_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        GROUP BY `co_contratti`.`id`
        ORDER BY `co_contratti`.`data_bozza` DESC';

        $contratti = $dbo->fetchArray($query_contratti);
        $documenti = array_merge($documenti, $contratti);

        return $documenti;
    }

    /**
     * Recupera i documenti collegati a una fattura di acquisto
     *
     * @param int $id_fattura ID della fattura
     * @return array Array di documenti collegati
     */
    private static function getDocumentiFatturaAcquisto($id_fattura)
    {
        global $dbo;

        $documenti = [];

        // Recupera i DDT collegati
        $query_ddt = 'SELECT 
            `dt_ddt`.`id`,
            `dt_ddt`.`numero`,
            `dt_ddt`.`numero_esterno`,
            `dt_ddt`.`data`,
            `dt_tipiddt_lang`.`title` AS tipo_documento,
            IF(`dt_tipiddt`.`dir` = \'entrata\', \'Ddt in uscita\', \'Ddt in entrata\') AS modulo,
            `dt_statiddt_lang`.`title` AS stato_documento
        FROM `dt_ddt`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `dt_ddt`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\DDT\\\\DDT\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )
        INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
        LEFT JOIN `dt_tipiddt_lang` ON (
            `dt_tipiddt_lang`.`id_record` = `dt_ddt`.`idtipoddt` AND 
            `dt_tipiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `dt_statiddt` ON `dt_ddt`.`idstatoddt` = `dt_statiddt`.`id`
        LEFT JOIN `dt_statiddt_lang` ON (
            `dt_statiddt_lang`.`id_record` = `dt_ddt`.`idstatoddt` AND 
            `dt_statiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        GROUP BY `dt_ddt`.`id`
        ORDER BY `dt_ddt`.`data` DESC';

        $ddt = $dbo->fetchArray($query_ddt);
        $documenti = array_merge($documenti, $ddt);

        // Recupera gli ordini collegati
        $query_ordini = 'SELECT 
            `or_ordini`.`id`,
            `or_ordini`.`numero`,
            `or_ordini`.`numero_esterno`,
            `or_ordini`.`data`,
            `or_tipiordine_lang`.`title` AS tipo_documento,
            IF(`or_tipiordine`.`dir` = \'entrata\', \'Ordini cliente\', \'Ordini fornitore\') AS modulo,
            `or_statiordine_lang`.`title` AS stato_documento
        FROM `or_ordini`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `or_ordini`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Ordini\\\\Ordine\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )
        INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
        LEFT JOIN `or_tipiordine_lang` ON (
            `or_tipiordine_lang`.`id_record` = `or_ordini`.`idtipoordine` AND 
            `or_tipiordine_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id`
        LEFT JOIN `or_statiordine_lang` ON (
            `or_statiordine_lang`.`id_record` = `or_ordini`.`idstatoordine` AND 
            `or_statiordine_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        GROUP BY `or_ordini`.`id`
        ORDER BY `or_ordini`.`data` DESC';

        $ordini = $dbo->fetchArray($query_ordini);
        $documenti = array_merge($documenti, $ordini);

        return $documenti;
    }

    /**
     * Recupera i documenti collegati a un contratto
     *
     * @param int $id_contratto ID del contratto
     * @return array Array di documenti collegati
     */
    private static function getDocumentiContratto($id_contratto)
    {
        global $dbo;

        $documenti = [];

        // Recupera le fatture collegate
        $query_fatture = 'SELECT
            `co_documenti`.`id`,
            `co_documenti`.`numero`,
            `co_documenti`.`numero_esterno`,
            `co_documenti`.`data`,
            `co_tipidocumento_lang`.`title` AS tipo_documento,
            IF(`co_tipidocumento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
            `co_statidocumento_lang`.`title` AS stato_documento
        FROM `co_documenti`
        INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        LEFT JOIN `co_tipidocumento_lang` ON (
            `co_tipidocumento_lang`.`id_record` = `co_documenti`.`idtipodocumento` AND
            `co_tipidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
        LEFT JOIN `co_statidocumento_lang` ON (
            `co_statidocumento_lang`.`id_record` = `co_documenti`.`idstatodocumento` AND
            `co_statidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`iddocumento` = `co_documenti`.`id` AND
            `co_righe_documenti`.`idcontratto` = '.prepare($id_contratto).'
        )
        GROUP BY `co_documenti`.`id`
        ORDER BY `co_documenti`.`data` DESC';

        $fatture = $dbo->fetchArray($query_fatture);
        $documenti = array_merge($documenti, $fatture);

        // Recupera gli interventi collegati
        $query_interventi = 'SELECT
            `in_interventi`.`id`,
            `in_interventi`.`codice`,
            `in_interventi`.`data_richiesta`,
            \'Attività\' AS tipo_documento,
            \'Interventi\' AS modulo,
            `in_statiintervento_lang`.`title` AS stato_documento
        FROM `in_interventi`
        INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`id`
        LEFT JOIN `in_statiintervento_lang` ON (
            `in_statiintervento_lang`.`id_record` = `in_interventi`.`idstatointervento` AND
            `in_statiintervento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `in_interventi`.`id_contratto` = '.prepare($id_contratto).'
        ORDER BY `in_interventi`.`data_richiesta` DESC';

        $interventi = $dbo->fetchArray($query_interventi);
        $documenti = array_merge($documenti, $interventi);

        // Recupera i preventivi collegati
        $query_preventivi = 'SELECT
            `co_preventivi`.`id`,
            `co_preventivi`.`numero`,
            `co_preventivi`.`data_bozza` AS data,
            \'Preventivo\' AS tipo_documento,
            \'Preventivi\' AS modulo,
            `co_statipreventivi_lang`.`title` AS stato_documento
        FROM `co_preventivi`
        INNER JOIN `co_righe_contratti` ON (
            `co_righe_contratti`.`original_document_id` = `co_preventivi`.`id` AND
            `co_righe_contratti`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\' AND
            `co_righe_contratti`.`idcontratto` = '.prepare($id_contratto).'
        )
        INNER JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
        LEFT JOIN `co_statipreventivi_lang` ON (
            `co_statipreventivi_lang`.`id_record` = `co_preventivi`.`idstato` AND
            `co_statipreventivi_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        GROUP BY `co_preventivi`.`id`
        ORDER BY `co_preventivi`.`data_bozza` DESC';

        $preventivi = $dbo->fetchArray($query_preventivi);
        $documenti = array_merge($documenti, $preventivi);

        return $documenti;
    }

    /**
     * Recupera i documenti collegati a un preventivo
     *
     * @param int $id_preventivo ID del preventivo
     * @return array Array di documenti collegati
     */
    private static function getDocumentiPreventivo($id_preventivo)
    {
        global $dbo;

        $documenti = [];

        // Recupera le fatture collegate
        $query_fatture = 'SELECT
            `co_documenti`.`id`,
            `co_documenti`.`data`,
            `co_documenti`.`numero`,
            `co_documenti`.`numero_esterno`,
            `co_tipidocumento_lang`.`title` AS tipo_documento,
            IF(`co_tipidocumento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
            `co_statidocumento_lang`.`title` AS stato_documento
        FROM `co_documenti`
        INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        LEFT JOIN `co_tipidocumento_lang` ON (
            `co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND
            `co_tipidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
        LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
        LEFT JOIN `co_statidocumento_lang` ON (
            `co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND
            `co_statidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `co_righe_documenti`.`idpreventivo` = '.prepare($id_preventivo).'
        GROUP BY `co_documenti`.`id`';

        $fatture = $dbo->fetchArray($query_fatture);
        $documenti = array_merge($documenti, $fatture);

        // Recupera gli ordini collegati
        $query_ordini = 'SELECT
            `or_ordini`.`id`,
            `or_ordini`.`data`,
            `or_ordini`.`numero`,
            `or_ordini`.`numero_esterno`,
            `or_tipiordine_lang`.`title` AS tipo_documento,
            IF(`or_tipiordine`.`dir` = \'entrata\', \'Ordini cliente\', \'Ordini fornitore\') AS modulo,
            `or_statiordine_lang`.`title` AS stato_documento
        FROM `or_ordini`
        INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id`
        INNER JOIN `or_tipiordine` ON `or_tipiordine`.`id` = `or_ordini`.`idtipoordine`
        LEFT JOIN `or_tipiordine_lang` ON (
            `or_tipiordine_lang`.`id_record` = `or_tipiordine`.`id` AND
            `or_tipiordine_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        LEFT JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id`
        LEFT JOIN `or_statiordine_lang` ON (
            `or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND
            `or_statiordine_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `or_righe_ordini`.`idpreventivo` = '.prepare($id_preventivo).'
        GROUP BY `or_ordini`.`id`';

        $ordini = $dbo->fetchArray($query_ordini);
        $documenti = array_merge($documenti, $ordini);

        // Recupera i DDT collegati
        $query_ddt = 'SELECT
            `dt_ddt`.`id`,
            `dt_ddt`.`data`,
            `dt_ddt`.`numero`,
            `dt_ddt`.`numero_esterno`,
            `dt_tipiddt_lang`.`title` AS tipo_documento,
            IF(`dt_tipiddt`.`dir` = \'entrata\', \'Ddt in uscita\', \'Ddt in entrata\') AS modulo,
            `dt_statiddt_lang`.`title` AS stato_documento
        FROM `dt_ddt`
        INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id`
        INNER JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt`
        LEFT JOIN `dt_tipiddt_lang` ON (
            `dt_tipiddt_lang`.`id_record` = `dt_tipiddt`.`id` AND
            `dt_tipiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        LEFT JOIN `dt_statiddt` ON `dt_ddt`.`idstatoddt` = `dt_statiddt`.`id`
        LEFT JOIN `dt_statiddt_lang` ON (
            `dt_statiddt`.`id` = `dt_statiddt_lang`.`id_record` AND
            `dt_statiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `dt_righe_ddt`.`original_document_id` = '.prepare($id_preventivo).'
        AND `dt_righe_ddt`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\'
        GROUP BY `dt_ddt`.`id`';

        $ddt = $dbo->fetchArray($query_ddt);
        $documenti = array_merge($documenti, $ddt);

        // Recupera gli interventi collegati
        $query_interventi = 'SELECT
            `in_interventi`.`id`,
            `in_interventi`.`data_richiesta`,
            `in_interventi`.`codice`,
            NULL AS numero_esterno,
            \'Attività\' AS tipo_documento,
            \'Interventi\' AS modulo,
            `in_statiintervento_lang`.`title` AS stato_documento
        FROM `in_interventi`
        INNER JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id`
        LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`id`
        LEFT JOIN `in_statiintervento_lang` ON (
            `in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND
            `in_statiintervento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE (`in_righe_interventi`.`original_document_id` = '.prepare($id_preventivo).'
        AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\')
        OR `in_interventi`.`id_preventivo` = '.prepare($id_preventivo).'
        GROUP BY `in_interventi`.`id`';

        $interventi = $dbo->fetchArray($query_interventi);
        $documenti = array_merge($documenti, $interventi);

        // Recupera i contratti collegati
        $query_contratti = 'SELECT
            `co_contratti`.`id`,
            `co_contratti`.`data_bozza` as data,
            `co_contratti`.`numero`,
            NULL AS numero_esterno,
            \'Contratto\' AS tipo_documento,
            \'Contratti\' AS modulo,
            `co_staticontratti_lang`.`title` AS stato_documento
        FROM `co_contratti`
        INNER JOIN `co_righe_contratti` ON `co_righe_contratti`.`idcontratto` = `co_contratti`.`id`
        LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
        LEFT JOIN `co_staticontratti_lang` ON (
            `co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND
            `co_staticontratti_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `co_righe_contratti`.`original_document_id` = '.prepare($id_preventivo).'
        AND `co_righe_contratti`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\'
        GROUP BY `co_contratti`.`id`';

        $contratti = $dbo->fetchArray($query_contratti);
        $documenti = array_merge($documenti, $contratti);

        return $documenti;
    }

    /**
     * Recupera i documenti collegati a un ordine
     *
     * @param int $id_ordine ID dell'ordine
     * @return array Array di documenti collegati
     */
    private static function getDocumentiOrdine($id_ordine)
    {
        global $dbo;

        $documenti = [];

        // Recupera le fatture collegate
        $query_fatture = 'SELECT
            `co_documenti`.`id`,
            `co_documenti`.`data`,
            `co_documenti`.`numero`,
            `co_documenti`.`numero_esterno`,
            `co_tipidocumento_lang`.`title` AS tipo_documento,
            IF(`co_tipidocumento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
            `co_statidocumento_lang`.`title` AS stato_documento
        FROM `co_documenti`
        INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        LEFT JOIN `co_tipidocumento_lang` ON (
            `co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND
            `co_tipidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
        LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
        LEFT JOIN `co_statidocumento_lang` ON (
            `co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND
            `co_statidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `co_righe_documenti`.`idordine` = '.prepare($id_ordine).'
        GROUP BY `co_documenti`.`id`
        ORDER BY `co_documenti`.`data` DESC';

        $fatture = $dbo->fetchArray($query_fatture);
        $documenti = array_merge($documenti, $fatture);

        // Recupera i DDT collegati
        $query_ddt = 'SELECT
            `dt_ddt`.`id`,
            `dt_ddt`.`data`,
            `dt_ddt`.`numero`,
            `dt_ddt`.`numero_esterno`,
            `dt_tipiddt_lang`.`title` AS tipo_documento,
            IF(`dt_tipiddt`.`dir` = \'entrata\', \'Ddt in uscita\', \'Ddt in entrata\') AS modulo,
            `dt_statiddt_lang`.`title` AS stato_documento
        FROM `dt_ddt`
        INNER JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt`
        LEFT JOIN `dt_tipiddt_lang` ON (
            `dt_tipiddt_lang`.`id_record` = `dt_tipiddt`.`id` AND
            `dt_tipiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id`
        LEFT JOIN `dt_statiddt` ON `dt_ddt`.`idstatoddt` = `dt_statiddt`.`id`
        LEFT JOIN `dt_statiddt_lang` ON (
            `dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND
            `dt_statiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `dt_righe_ddt`.`idordine` = '.prepare($id_ordine).'
        GROUP BY `dt_ddt`.`id`
        ORDER BY `dt_ddt`.`data` DESC';

        $ddt = $dbo->fetchArray($query_ddt);
        $documenti = array_merge($documenti, $ddt);

        // Recupera gli interventi collegati
        $query_interventi = 'SELECT
            `in_interventi`.`id`,
            `in_interventi`.`data_richiesta`,
            `in_interventi`.`codice`,
            NULL AS numero_esterno,
            \'Attività\' AS tipo_documento,
            \'Interventi\' AS modulo,
            `in_statiintervento_lang`.`title` AS stato_documento
        FROM `in_interventi`
        INNER JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id`
        LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`id`
        LEFT JOIN `in_statiintervento_lang` ON (
            `in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND
            `in_statiintervento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE (`in_righe_interventi`.`original_document_id` = '.prepare($id_ordine).'
        AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\Ordini\\\\Ordine\')
        OR `in_interventi`.`id_ordine` = '.prepare($id_ordine).'
        GROUP BY `in_interventi`.`id`
        ORDER BY `in_interventi`.`data_richiesta` DESC';

        $interventi = $dbo->fetchArray($query_interventi);
        $documenti = array_merge($documenti, $interventi);

        // Recupera i preventivi collegati (solo per ordini cliente)
        $query_preventivi = 'SELECT
            `co_preventivi`.`id`,
            `co_preventivi`.`data_bozza` AS data,
            `co_preventivi`.`numero`,
            NULL AS numero_esterno,
            \'Preventivo\' AS tipo_documento,
            \'Preventivi\' AS modulo,
            `co_statipreventivi_lang`.`title` AS stato_documento
        FROM `co_preventivi`
        INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = '.prepare($id_ordine).'
        INNER JOIN `co_righe_preventivi` ON `co_righe_preventivi`.`idpreventivo` = `co_preventivi`.`id`
        LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
        LEFT JOIN `co_statipreventivi_lang` ON (
            `co_statipreventivi_lang`.`id_record` = `co_statipreventivi`.`id` AND
            `co_statipreventivi_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `or_righe_ordini`.`idpreventivo` = `co_preventivi`.`id`
        GROUP BY `co_preventivi`.`id`
        ORDER BY `co_preventivi`.`data_bozza` DESC';

        $preventivi = $dbo->fetchArray($query_preventivi);
        $documenti = array_merge($documenti, $preventivi);

        return $documenti;
    }

    /**
     * Recupera i documenti collegati a un DDT
     *
     * @param int $id_ddt ID del DDT
     * @return array Array di documenti collegati
     */
    private static function getDocumentiDDT($id_ddt)
    {
        global $dbo;

        $documenti = [];

        // Recupera le fatture collegate
        $query_fatture = 'SELECT
            `co_documenti`.`id`,
            `co_documenti`.`data`,
            `co_documenti`.`numero`,
            `co_documenti`.`numero_esterno`,
            `co_tipidocumento_lang`.`title` AS tipo_documento,
            IF(`co_tipidocumento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
            `co_statidocumento_lang`.`title` AS stato_documento
        FROM `co_documenti`
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
        INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        LEFT JOIN `co_tipidocumento_lang` ON (
            `co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND
            `co_tipidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
        LEFT JOIN `co_statidocumento_lang` ON (
            `co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND
            `co_statidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `co_righe_documenti`.`idddt` = '.prepare($id_ddt).'
        GROUP BY `co_documenti`.`id`
        ORDER BY `co_documenti`.`data` DESC';

        $fatture = $dbo->fetchArray($query_fatture);
        $documenti = array_merge($documenti, $fatture);

        // Recupera gli interventi collegati
        $query_interventi = 'SELECT
            `in_interventi`.`id`,
            `in_interventi`.`data_richiesta` AS data,
            `in_interventi`.`codice` AS numero,
            NULL AS numero_esterno,
            \'Attività\' AS tipo_documento,
            \'Interventi\' AS modulo,
            `in_statiintervento_lang`.`title` AS stato_documento
        FROM `in_interventi`
        INNER JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id`
        LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`id`
        LEFT JOIN `in_statiintervento_lang` ON (
            `in_statiintervento_lang`.`id_record` = `in_interventi`.`idstatointervento` AND
            `in_statiintervento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
        )
        WHERE `in_righe_interventi`.`original_document_id` = '.prepare($id_ddt).'
        AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\DDT\\\\DDT\'
        GROUP BY `in_interventi`.`id`
        ORDER BY `in_interventi`.`data_richiesta` DESC';

        $interventi = $dbo->fetchArray($query_interventi);
        $documenti = array_merge($documenti, $interventi);

        return $documenti;
    }

    /**
     * Conta i documenti collegati a un record specifico
     *
     * @param int $id_record ID del record
     * @param string $tipo_record Tipo di record (es. 'intervento', 'fattura_vendita', ecc.)
     * @return int Numero di documenti collegati
     */
    public static function countDocumenti($id_record, $tipo_record = 'intervento')
    {
        if (empty($id_record) || !is_numeric($id_record)) {
            return 0;
        }

        // In base al tipo di record, esegui il conteggio appropriato
        switch ($tipo_record) {
            case 'intervento':
                return self::countDocumentiIntervento($id_record);
            case 'fattura_vendita':
                return self::countDocumentiFatturaVendita($id_record);
            case 'fattura_acquisto':
                return self::countDocumentiFatturaAcquisto($id_record);
            case 'contratto':
                return self::countDocumentiContratto($id_record);
            case 'preventivo':
                return self::countDocumentiPreventivo($id_record);
            case 'ordine':
                return self::countDocumentiOrdine($id_record);
            case 'ddt':
                return self::countDocumentiDDT($id_record);
            default:
                return 0;
        }
    }

    /**
     * Conta i documenti collegati a un intervento
     *
     * @param int $id_intervento ID dell'intervento
     * @return int Numero di documenti collegati
     */
    private static function countDocumentiIntervento($id_intervento)
    {
        global $dbo;

        // Query ottimizzata per il conteggio (conta documenti unici, non righe)
        $query = 'SELECT COUNT(DISTINCT `iddocumento`) AS total
        FROM `co_righe_documenti`
        WHERE `idintervento` = '.prepare($id_intervento);

        $result = $dbo->fetchOne($query);

        return (int) $result['total'];
    }

    /**
     * Conta i documenti collegati a una fattura di vendita
     *
     * @param int $id_fattura ID della fattura
     * @return int Numero di documenti collegati
     */
    private static function countDocumentiFatturaVendita($id_fattura)
    {
        global $dbo;

        $total = 0;

        // Conta gli interventi collegati
        $query_interventi = 'SELECT COUNT(DISTINCT `in_interventi`.`id`) AS total
        FROM `in_interventi`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `in_interventi`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Interventi\\\\Intervento\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )';

        $result = $dbo->fetchOne($query_interventi);
        $total += (int) $result['total'];

        // Conta i preventivi collegati
        $query_preventivi = 'SELECT COUNT(DISTINCT `co_preventivi`.`id`) AS total
        FROM `co_preventivi`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `co_preventivi`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )';

        $result = $dbo->fetchOne($query_preventivi);
        $total += (int) $result['total'];

        // Conta i DDT collegati
        $query_ddt = 'SELECT COUNT(DISTINCT `dt_ddt`.`id`) AS total
        FROM `dt_ddt`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `dt_ddt`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\DDT\\\\DDT\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )';

        $result = $dbo->fetchOne($query_ddt);
        $total += (int) $result['total'];

        // Conta gli ordini collegati
        $query_ordini = 'SELECT COUNT(DISTINCT `or_ordini`.`id`) AS total
        FROM `or_ordini`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `or_ordini`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Ordini\\\\Ordine\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )';

        $result = $dbo->fetchOne($query_ordini);
        $total += (int) $result['total'];

        // Conta i contratti collegati
        $query_contratti = 'SELECT COUNT(DISTINCT `co_contratti`.`id`) AS total
        FROM `co_contratti`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `co_contratti`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Contratti\\\\Contratto\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )';

        $result = $dbo->fetchOne($query_contratti);
        $total += (int) $result['total'];

        return $total;
    }

    /**
     * Conta i documenti collegati a una fattura di acquisto
     *
     * @param int $id_fattura ID della fattura
     * @return int Numero di documenti collegati
     */
    private static function countDocumentiFatturaAcquisto($id_fattura)
    {
        global $dbo;

        $total = 0;

        // Conta i DDT collegati
        $query_ddt = 'SELECT COUNT(DISTINCT `dt_ddt`.`id`) AS total
        FROM `dt_ddt`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `dt_ddt`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\DDT\\\\DDT\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )';

        $result = $dbo->fetchOne($query_ddt);
        $total += (int) $result['total'];

        // Conta gli ordini collegati
        $query_ordini = 'SELECT COUNT(DISTINCT `or_ordini`.`id`) AS total
        FROM `or_ordini`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`original_document_id` = `or_ordini`.`id` AND 
            `co_righe_documenti`.`original_document_type` = \'Modules\\\\Ordini\\\\Ordine\' AND
            `co_righe_documenti`.`iddocumento` = '.prepare($id_fattura).'
        )';

        $result = $dbo->fetchOne($query_ordini);
        $total += (int) $result['total'];

        return $total;
    }

    /**
     * Conta i documenti collegati a un contratto
     *
     * @param int $id_contratto ID del contratto
     * @return int Numero di documenti collegati
     */
    private static function countDocumentiContratto($id_contratto)
    {
        global $dbo;

        $total = 0;

        // Conta le fatture collegate
        $query_fatture = 'SELECT COUNT(DISTINCT `co_documenti`.`id`) AS total
        FROM `co_documenti`
        INNER JOIN `co_righe_documenti` ON (
            `co_righe_documenti`.`iddocumento` = `co_documenti`.`id` AND
            `co_righe_documenti`.`idcontratto` = '.prepare($id_contratto).'
        )';

        $result = $dbo->fetchOne($query_fatture);
        $total += (int) $result['total'];

        // Conta gli interventi collegati
        $query_interventi = 'SELECT COUNT(DISTINCT `in_interventi`.`id`) AS total
        FROM `in_interventi`
        WHERE `in_interventi`.`id_contratto` = '.prepare($id_contratto);

        $result = $dbo->fetchOne($query_interventi);
        $total += (int) $result['total'];

        // Conta i preventivi collegati
        $query_preventivi = 'SELECT COUNT(DISTINCT `co_preventivi`.`id`) AS total
        FROM `co_preventivi`
        INNER JOIN `co_righe_contratti` ON (
            `co_righe_contratti`.`original_document_id` = `co_preventivi`.`id` AND
            `co_righe_contratti`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\' AND
            `co_righe_contratti`.`idcontratto` = '.prepare($id_contratto).'
        )';

        $result = $dbo->fetchOne($query_preventivi);
        $total += (int) $result['total'];

        return $total;
    }

    /**
     * Conta i documenti collegati a un preventivo
     *
     * @param int $id_preventivo ID del preventivo
     * @return int Numero di documenti collegati
     */
    private static function countDocumentiPreventivo($id_preventivo)
    {
        global $dbo;

        $total = 0;

        // Conta le fatture collegate
        $query_fatture = 'SELECT COUNT(DISTINCT `co_documenti`.`id`) AS total
        FROM `co_documenti`
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
        WHERE `co_righe_documenti`.`idpreventivo` = '.prepare($id_preventivo);

        $result = $dbo->fetchOne($query_fatture);
        $total += (int) $result['total'];

        // Conta gli ordini collegati
        $query_ordini = 'SELECT COUNT(DISTINCT `or_ordini`.`id`) AS total
        FROM `or_ordini`
        INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id`
        WHERE `or_righe_ordini`.`idpreventivo` = '.prepare($id_preventivo);

        $result = $dbo->fetchOne($query_ordini);
        $total += (int) $result['total'];

        // Conta i DDT collegati
        $query_ddt = 'SELECT COUNT(DISTINCT `dt_ddt`.`id`) AS total
        FROM `dt_ddt`
        INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id`
        WHERE `dt_righe_ddt`.`original_document_id` = '.prepare($id_preventivo).'
        AND `dt_righe_ddt`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\'';

        $result = $dbo->fetchOne($query_ddt);
        $total += (int) $result['total'];

        // Conta gli interventi collegati
        $query_interventi = 'SELECT COUNT(DISTINCT `in_interventi`.`id`) AS total
        FROM `in_interventi`
        INNER JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id`
        WHERE (`in_righe_interventi`.`original_document_id` = '.prepare($id_preventivo).'
        AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\')
        OR `in_interventi`.`id_preventivo` = '.prepare($id_preventivo);

        $result = $dbo->fetchOne($query_interventi);
        $total += (int) $result['total'];

        // Conta i contratti collegati
        $query_contratti = 'SELECT COUNT(DISTINCT `co_contratti`.`id`) AS total
        FROM `co_contratti`
        INNER JOIN `co_righe_contratti` ON `co_righe_contratti`.`idcontratto` = `co_contratti`.`id`
        WHERE `co_righe_contratti`.`original_document_id` = '.prepare($id_preventivo).'
        AND `co_righe_contratti`.`original_document_type` = \'Modules\\\\Preventivi\\\\Preventivo\'';

        $result = $dbo->fetchOne($query_contratti);
        $total += (int) $result['total'];

        return $total;
    }

    /**
     * Conta i documenti collegati a un ordine
     *
     * @param int $id_ordine ID dell'ordine
     * @return int Numero di documenti collegati
     */
    private static function countDocumentiOrdine($id_ordine)
    {
        global $dbo;

        $total = 0;

        // Conta le fatture collegate
        $query_fatture = 'SELECT COUNT(DISTINCT `co_documenti`.`id`) AS total
        FROM `co_documenti`
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
        WHERE `co_righe_documenti`.`idordine` = '.prepare($id_ordine);

        $result = $dbo->fetchOne($query_fatture);
        $total += (int) $result['total'];

        // Conta i DDT collegati
        $query_ddt = 'SELECT COUNT(DISTINCT `dt_ddt`.`id`) AS total
        FROM `dt_ddt`
        INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id`
        WHERE `dt_righe_ddt`.`idordine` = '.prepare($id_ordine);

        $result = $dbo->fetchOne($query_ddt);
        $total += (int) $result['total'];

        // Conta gli interventi collegati
        $query_interventi = 'SELECT COUNT(DISTINCT `in_interventi`.`id`) AS total
        FROM `in_interventi`
        INNER JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id`
        WHERE (`in_righe_interventi`.`original_document_id` = '.prepare($id_ordine).'
        AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\Ordini\\\\Ordine\')
        OR `in_interventi`.`id_ordine` = '.prepare($id_ordine);

        $result = $dbo->fetchOne($query_interventi);
        $total += (int) $result['total'];

        // Conta i preventivi collegati
        $query_preventivi = 'SELECT COUNT(DISTINCT `co_preventivi`.`id`) AS total
        FROM `co_preventivi`
        INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = '.prepare($id_ordine).'
        INNER JOIN `co_righe_preventivi` ON `co_righe_preventivi`.`idpreventivo` = `co_preventivi`.`id`
        WHERE `or_righe_ordini`.`idpreventivo` = `co_preventivi`.`id`';

        $result = $dbo->fetchOne($query_preventivi);
        $total += (int) $result['total'];

        return $total;
    }

    /**
     * Conta i documenti collegati a un DDT
     *
     * @param int $id_ddt ID del DDT
     * @return int Numero di documenti collegati
     */
    private static function countDocumentiDDT($id_ddt)
    {
        global $dbo;

        $total = 0;

        // Conta le fatture collegate
        $query_fatture = 'SELECT COUNT(DISTINCT `co_documenti`.`id`) AS total
        FROM `co_documenti`
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
        WHERE `co_righe_documenti`.`idddt` = '.prepare($id_ddt);

        $result = $dbo->fetchOne($query_fatture);
        $total += (int) $result['total'];

        // Conta gli interventi collegati
        $query_interventi = 'SELECT COUNT(DISTINCT `in_interventi`.`id`) AS total
        FROM `in_interventi`
        INNER JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id`
        WHERE `in_righe_interventi`.`original_document_id` = '.prepare($id_ddt).'
        AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\DDT\\\\DDT\'';

        $result = $dbo->fetchOne($query_interventi);
        $total += (int) $result['total'];

        return $total;
    }

    /**
     * Genera la descrizione per un documento collegato a una fattura di vendita
     *
     * @param array $elemento Elemento documento
     * @return string Descrizione HTML
     */
    private static function renderDescrizione($elemento)
    {
        // In base al tipo di documento, genera la descrizione appropriata
        switch ($elemento['tipo_documento']) {
            case 'Attività':
                $numero = $elemento['codice'] ?? $elemento['numero'] ?? '?';
                $data = $elemento['data_richiesta'] ?? $elemento['data'] ?? '';
                return tr('_DOC_ num. _NUM_ del _DATE_ [_STATE_]', [
                    '_DOC_' => $elemento['tipo_documento'],
                    '_NUM_' => $numero,
                    '_DATE_' => $data ? Translator::dateToLocale($data) : '',
                    '_STATE_' => $elemento['stato_documento'] ?? '',
                ]);
            case 'Preventivo':
                $numero = $elemento['numero'] ?? '?';
                $data = $elemento['data'] ?? '';
                return tr('_DOC_ num. _NUM_ del _DATE_ [_STATE_]', [
                    '_DOC_' => $elemento['tipo_documento'],
                    '_NUM_' => $numero,
                    '_DATE_' => $data ? Translator::dateToLocale($data) : '',
                    '_STATE_' => $elemento['stato_documento'] ?? '',
                ]);
            case 'Contratto':
                $numero = $elemento['numero'] ?? '?';
                $data = $elemento['data'] ?? '';
                return tr('_DOC_ num. _NUM_ del _DATE_ [_STATE_]', [
                    '_DOC_' => $elemento['tipo_documento'],
                    '_NUM_' => $numero,
                    '_DATE_' => $data ? Translator::dateToLocale($data) : '',
                    '_STATE_' => $elemento['stato_documento'] ?? '',
                ]);
            case 'Fattura':
                // Per le fatture
                $numero = !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : ($elemento['numero'] ?? '?');
                $data = $elemento['data'] ?? '';
                return tr('_DOC_ num. _NUM_ del _DATE_ [_STATE_]', [
                    '_DOC_' => $elemento['tipo_documento'],
                    '_NUM_' => $numero,
                    '_DATE_' => $data ? Translator::dateToLocale($data) : '',
                    '_STATE_' => $elemento['stato_documento'] ?? '',
                ]);
            default:
                // Per DDT e Ordini
                $numero = !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : ($elemento['numero'] ?? '?');
                $data = $elemento['data'] ?? '';
                return tr('_DOC_ num. _NUM_ del _DATE_ [_STATE_]', [
                    '_DOC_' => $elemento['tipo_documento'],
                    '_NUM_' => $numero,
                    '_DATE_' => $data ? Translator::dateToLocale($data) : '',
                    '_STATE_' => $elemento['stato_documento'] ?? '',
                ]);
        }
    }

    /**
     * Recupera i documenti precedenti nel flusso di evasione
     *
     * @param int $id_record ID del record corrente
     * @param string $tipo_record Tipo di record corrente
     * @return array Array di documenti precedenti
     */
    private static function getDocumentiPrecedenti($id_record, $tipo_record)
    {
        global $dbo;

        $documenti_precedenti = [];

        // Determina la tabella delle righe in base al tipo di record
        $tabella_righe = '';
        $campo_id_documento = '';

        switch ($tipo_record) {
            case 'fattura_vendita':
            case 'fattura_acquisto':
                $tabella_righe = 'co_righe_documenti';
                $campo_id_documento = 'iddocumento';
                break;
            case 'ordine':
                $tabella_righe = 'or_righe_ordini';
                $campo_id_documento = 'idordine';
                break;
            case 'ddt':
                $tabella_righe = 'dt_righe_ddt';
                $campo_id_documento = 'idddt';
                break;
            case 'intervento':
                $tabella_righe = 'in_righe_interventi';
                $campo_id_documento = 'idintervento';
                break;
            case 'preventivo':
                $tabella_righe = 'co_righe_preventivi';
                $campo_id_documento = 'idpreventivo';
                break;
            case 'contratto':
                $tabella_righe = 'co_righe_contratti';
                $campo_id_documento = 'idcontratto';
                break;
            default:
                return $documenti_precedenti;
        }

        // Recupera i documenti precedenti cercando nelle righe del documento corrente
        $query = 'SELECT DISTINCT 
            `original_document_id` AS id,
            `original_document_type` AS tipo
        FROM `'.$tabella_righe.'`
        WHERE `'.$campo_id_documento.'` = '.prepare($id_record).'
        AND `original_document_id` IS NOT NULL
        AND `original_document_type` IS NOT NULL';

        $risultati = $dbo->fetchArray($query);

        foreach ($risultati as $risultato) {
            $doc_info = self::getInfoDocumento($risultato['id'], $risultato['tipo']);
            if ($doc_info) {
                $documenti_precedenti[] = $doc_info;
            }
        }

        return $documenti_precedenti;
    }

    /**
     * Recupera i documenti successivi nel flusso di evasione
     *
     * @param int $id_record ID del record corrente
     * @param string $tipo_record Tipo di record corrente
     * @return array Array di documenti successivi
     */
    private static function getDocumentiSuccessivi($id_record, $tipo_record)
    {
        global $dbo;

        $documenti_successivi = [];

        // Determina il tipo di documento corrente
        $tipo_documento_corrente = self::getTipoDocumento($tipo_record);

        if (!$tipo_documento_corrente) {
            return $documenti_successivi;
        }

        // Cerca nelle tabelle delle righe di tutti i tipi di documento
        $tabelle_righe = [
            'co_righe_documenti' => ['iddocumento', 'fattura_vendita', 'fattura_acquisto'],
            'or_righe_ordini' => ['idordine', 'ordine'],
            'dt_righe_ddt' => ['idddt', 'ddt'],
            'in_righe_interventi' => ['idintervento', 'intervento'],
            'co_righe_preventivi' => ['idpreventivo', 'preventivo'],
            'co_righe_contratti' => ['idcontratto', 'contratto'],
        ];

        foreach ($tabelle_righe as $tabella => $info) {
            $campo_id_documento = $info[0];

            $query = 'SELECT DISTINCT 
                `'.$campo_id_documento.'` AS id
            FROM `'.$tabella.'`
            WHERE `original_document_id` = '.prepare($id_record).'
            AND `original_document_type` = '.prepare($tipo_documento_corrente);

            $risultati = $dbo->fetchArray($query);

            foreach ($risultati as $risultato) {
                $tipo_doc = self::getTipoDaTabella($tabella);
                $doc_info = self::getInfoDocumento($risultato['id'], $tipo_doc);
                if ($doc_info) {
                    $documenti_successivi[] = $doc_info;
                }
            }
        }

        return $documenti_successivi;
    }

    /**
     * Ottiene informazioni su un documento
     *
     * @param int $id ID del documento
     * @param string $tipo Tipo di documento (nome classe completo)
     * @return array|null Informazioni del documento o null se non trovato
     */
    private static function getInfoDocumento($id, $tipo)
    {
        global $dbo;

        // Mappa i tipi di documento alle tabelle
        $mappa_tipi = [
            'Modules\\Interventi\\Intervento' => ['tabella' => 'in_interventi', 'campo_numero' => 'codice', 'campo_data' => 'data_richiesta', 'tipo_doc' => 'Attività', 'modulo' => 'Interventi'],
            'Modules\\Preventivi\\Preventivo' => ['tabella' => 'co_preventivi', 'campo_numero' => 'numero', 'campo_data' => 'data_bozza', 'tipo_doc' => 'Preventivo', 'modulo' => 'Preventivi'],
            'Modules\\Contratti\\Contratto' => ['tabella' => 'co_contratti', 'campo_numero' => 'numero', 'campo_data' => 'data_bozza', 'tipo_doc' => 'Contratto', 'modulo' => 'Contratti'],
            'Modules\\Ordini\\Ordine' => ['tabella' => 'or_ordini', 'campo_numero' => 'numero', 'campo_data' => 'data', 'tipo_doc' => 'Ordine', 'modulo' => 'Ordini cliente'],
            'Modules\\DDT\\DDT' => ['tabella' => 'dt_ddt', 'campo_numero' => 'numero', 'campo_data' => 'data', 'tipo_doc' => 'DDT', 'modulo' => 'Ddt in uscita'],
            'Modules\\Fatture\\Fattura' => ['tabella' => 'co_documenti', 'campo_numero' => 'numero', 'campo_data' => 'data', 'tipo_doc' => 'Fattura', 'modulo' => 'Fatture di vendita'],
        ];

        if (!isset($mappa_tipi[$tipo])) {
            return null;
        }

        $info = $mappa_tipi[$tipo];
        $tabella = $info['tabella'];
        $campo_numero = $info['campo_numero'];
        $campo_data = $info['campo_data'];

        // Costruisci la query in base al tipo di documento
        if ($tipo == 'Modules\\Interventi\\Intervento') {
            $query = 'SELECT
                `'.$tabella.'`.`id`,
                `'.$tabella.'`.`'.$campo_numero.'` AS numero,
                `'.$tabella.'`.`'.$campo_data.'` AS data,
                \''.$info['tipo_doc'].'\' AS tipo_documento,
                \''.$info['modulo'].'\' AS modulo,
                `in_statiintervento_lang`.`title` AS stato_documento
            FROM `'.$tabella.'`
            INNER JOIN `in_statiintervento` ON `'.$tabella.'`.`idstatointervento` = `in_statiintervento`.`id`
            LEFT JOIN `in_statiintervento_lang` ON (
                `in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND
                `in_statiintervento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
            )
            WHERE `'.$tabella.'`.`id` = '.prepare($id);
        } elseif ($tipo == 'Modules\\Preventivi\\Preventivo') {
            $query = 'SELECT
                `'.$tabella.'`.`id`,
                `'.$tabella.'`.`'.$campo_numero.'` AS numero,
                `'.$tabella.'`.`'.$campo_data.'` AS data,
                \''.$info['tipo_doc'].'\' AS tipo_documento,
                \''.$info['modulo'].'\' AS modulo,
                `co_statipreventivi_lang`.`title` AS stato_documento
            FROM `'.$tabella.'`
            INNER JOIN `co_statipreventivi` ON `'.$tabella.'`.`idstato` = `co_statipreventivi`.`id`
            LEFT JOIN `co_statipreventivi_lang` ON (
                `co_statipreventivi_lang`.`id_record` = `co_statipreventivi`.`id` AND
                `co_statipreventivi_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
            )
            WHERE `'.$tabella.'`.`id` = '.prepare($id);
        } elseif ($tipo == 'Modules\\Contratti\\Contratto') {
            $query = 'SELECT
                `'.$tabella.'`.`id`,
                `'.$tabella.'`.`'.$campo_numero.'` AS numero,
                `'.$tabella.'`.`'.$campo_data.'` AS data,
                \''.$info['tipo_doc'].'\' AS tipo_documento,
                \''.$info['modulo'].'\' AS modulo,
                `co_staticontratti_lang`.`title` AS stato_documento
            FROM `'.$tabella.'`
            INNER JOIN `co_staticontratti` ON `'.$tabella.'`.`idstato` = `co_staticontratti`.`id`
            LEFT JOIN `co_staticontratti_lang` ON (
                `co_staticontratti_lang`.`id_record` = `co_staticontratti`.`id` AND
                `co_staticontratti_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
            )
            WHERE `'.$tabella.'`.`id` = '.prepare($id);
        } elseif ($tipo == 'Modules\\Ordini\\Ordine') {
            $query = 'SELECT
                `'.$tabella.'`.`id`,
                `'.$tabella.'`.`'.$campo_numero.'` AS numero,
                `'.$tabella.'`.`numero_esterno`,
                `'.$tabella.'`.`'.$campo_data.'` AS data,
                `or_tipiordine_lang`.`title` AS tipo_documento,
                IF(`or_tipiordine`.`dir` = \'entrata\', \'Ordini cliente\', \'Ordini fornitore\') AS modulo,
                `or_statiordine_lang`.`title` AS stato_documento
            FROM `'.$tabella.'`
            INNER JOIN `or_tipiordine` ON `'.$tabella.'`.`idtipoordine` = `or_tipiordine`.`id`
            LEFT JOIN `or_tipiordine_lang` ON (
                `or_tipiordine_lang`.`id_record` = `or_tipiordine`.`id` AND
                `or_tipiordine_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
            )
            INNER JOIN `or_statiordine` ON `'.$tabella.'`.`idstatoordine` = `or_statiordine`.`id`
            LEFT JOIN `or_statiordine_lang` ON (
                `or_statiordine_lang`.`id_record` = `or_statiordine`.`id` AND
                `or_statiordine_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
            )
            WHERE `'.$tabella.'`.`id` = '.prepare($id);
        } elseif ($tipo == 'Modules\\DDT\\DDT') {
            $query = 'SELECT
                `'.$tabella.'`.`id`,
                `'.$tabella.'`.`'.$campo_numero.'` AS numero,
                `'.$tabella.'`.`numero_esterno`,
                `'.$tabella.'`.`'.$campo_data.'` AS data,
                `dt_tipiddt_lang`.`title` AS tipo_documento,
                IF(`dt_tipiddt`.`dir` = \'entrata\', \'Ddt in uscita\', \'Ddt in entrata\') AS modulo,
                `dt_statiddt_lang`.`title` AS stato_documento
            FROM `'.$tabella.'`
            INNER JOIN `dt_tipiddt` ON `'.$tabella.'`.`idtipoddt` = `dt_tipiddt`.`id`
            LEFT JOIN `dt_tipiddt_lang` ON (
                `dt_tipiddt_lang`.`id_record` = `dt_tipiddt`.`id` AND
                `dt_tipiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
            )
            INNER JOIN `dt_statiddt` ON `'.$tabella.'`.`idstatoddt` = `dt_statiddt`.`id`
            LEFT JOIN `dt_statiddt_lang` ON (
                `dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND
                `dt_statiddt_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
            )
            WHERE `'.$tabella.'`.`id` = '.prepare($id);
        } elseif ($tipo == 'Modules\\Fatture\\Fattura') {
            $query = 'SELECT
                `'.$tabella.'`.`id`,
                `'.$tabella.'`.`'.$campo_numero.'` AS numero,
                `'.$tabella.'`.`numero_esterno`,
                `'.$tabella.'`.`'.$campo_data.'` AS data,
                `co_tipidocumento_lang`.`title` AS tipo_documento,
                IF(`co_tipidocumento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
                `co_statidocumento_lang`.`title` AS stato_documento
            FROM `'.$tabella.'`
            INNER JOIN `co_tipidocumento` ON `'.$tabella.'`.`idtipodocumento` = `co_tipidocumento`.`id`
            LEFT JOIN `co_tipidocumento_lang` ON (
                `co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND
                `co_tipidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
            )
            INNER JOIN `co_statidocumento` ON `'.$tabella.'`.`idstatodocumento` = `co_statidocumento`.`id`
            LEFT JOIN `co_statidocumento_lang` ON (
                `co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND
                `co_statidocumento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).'
            )
            WHERE `'.$tabella.'`.`id` = '.prepare($id);
        }

        $result = $dbo->fetchOne($query);

        return $result ?: null;
    }

    /**
     * Ottiene il tipo di documento (nome classe) in base al tipo di record
     *
     * @param string $tipo_record Tipo di record
     * @return string|null Nome classe del documento o null
     */
    private static function getTipoDocumento($tipo_record)
    {
        $mappa = [
            'intervento' => 'Modules\\Interventi\\Intervento',
            'preventivo' => 'Modules\\Preventivi\\Preventivo',
            'contratto' => 'Modules\\Contratti\\Contratto',
            'ordine' => 'Modules\\Ordini\\Ordine',
            'ddt' => 'Modules\\DDT\\DDT',
            'fattura_vendita' => 'Modules\\Fatture\\Fattura',
            'fattura_acquisto' => 'Modules\\Fatture\\Fattura',
        ];

        return $mappa[$tipo_record] ?? null;
    }

    /**
     * Ottiene il tipo di documento in base alla tabella delle righe
     *
     * @param string $tabella Nome della tabella delle righe
     * @return string|null Tipo di documento o null
     */
    private static function getTipoDaTabella($tabella)
    {
        $mappa = [
            'co_righe_documenti' => 'Modules\\Fatture\\Fattura',
            'or_righe_ordini' => 'Modules\\Ordini\\Ordine',
            'dt_righe_ddt' => 'Modules\\DDT\\DDT',
            'in_righe_interventi' => 'Modules\\Interventi\\Intervento',
            'co_righe_preventivi' => 'Modules\\Preventivi\\Preventivo',
            'co_righe_contratti' => 'Modules\\Contratti\\Contratto',
        ];

        return $mappa[$tabella] ?? null;
    }

    /**
     * Genera l'HTML per la lista dei documenti collegati con flusso di evasione
     *
     * @param int $id_record ID del record
     * @param string $tipo_record Tipo di record (es. 'intervento', 'fattura_vendita', ecc.)
     * @return string HTML della lista documenti con flusso
     */
    public static function renderDocumenti($id_record, $tipo_record = 'intervento')
    {
        try {
            // Recupera i documenti precedenti e successivi
            $documenti_precedenti = self::getDocumentiPrecedenti($id_record, $tipo_record);
            $documenti_successivi = self::getDocumentiSuccessivi($id_record, $tipo_record);

            // Mostra sempre il documento corrente, anche se non ci sono documenti collegati
            $html = '<div class="flusso-evasione">';
            
            // Colonna 1: Documenti precedenti
            $html .= '<div class="colonna-documenti documenti-precedenti">';
            if (!empty($documenti_precedenti)) {
                $html .= '<ul class="list-unstyled">';
                
                foreach ($documenti_precedenti as $elemento) {
                    $descrizione = self::renderDescrizione($elemento);
                    $modulo = $elemento['modulo'] ?? '';
                    $id = $elemento['id'];
                    
                    if (!empty($modulo) && !empty($id)) {
                        $html .= '<li>'.Modules::link($modulo, $id, $descrizione).'</li>';
                    } else {
                        $html .= '<li>'.$descrizione.'</li>';
                    }
                }
                
                $html .= '</ul>';
            }
            $html .= '</div>';
            
            // Freccia verso il documento corrente (solo se ci sono documenti precedenti)
            if (!empty($documenti_precedenti)) {
                $html .= '<div class="freccia-orizzontale"><i class="fa fa-arrow-right"></i></div>';
            }

            // Colonna 2: Documento corrente (evidenziato)
            $html .= '<div class="colonna-documenti documento-corrente">';
            $html .= '<div class="documento-corrente-box">';
            
            // Ottieni informazioni sul documento corrente
            $info_corrente = self::getInfoDocumento($id_record, self::getTipoDocumento($tipo_record));
            if ($info_corrente) {
                $descrizione = self::renderDescrizione($info_corrente);
                $html .= '<span class="badge badge-primary">'.$descrizione.'</span>';
            } else {
                // Se non riesce a recuperare le informazioni, mostra un messaggio generico
                $html .= '<span class="badge badge-primary">Documento corrente</span>';
            }
            
            $html .= '</div>';
            $html .= '</div>';

            // Freccia verso i documenti successivi (solo se ci sono documenti successivi)
            if (!empty($documenti_successivi)) {
                $html .= '<div class="freccia-orizzontale"><i class="fa fa-arrow-right"></i></div>';
            }

            // Colonna 3: Documenti successivi
            $html .= '<div class="colonna-documenti documenti-successivi">';
            if (!empty($documenti_successivi)) {
                $html .= '<ul class="list-unstyled">';
                
                foreach ($documenti_successivi as $elemento) {
                    $descrizione = self::renderDescrizione($elemento);
                    $modulo = $elemento['modulo'] ?? '';
                    $id = $elemento['id'];
                    
                    if (!empty($modulo) && !empty($id)) {
                        $html .= '<li>'.Modules::link($modulo, $id, $descrizione).'</li>';
                    } else {
                        $html .= '<li>'.$descrizione.'</li>';
                    }
                }
                
                $html .= '</ul>';
            }
            $html .= '</div>';

            $html .= '</div>';

            return $html;
        } catch (\Exception $e) {
            return '<div class="alert alert-danger">'.tr('Errore nel rendering dei documenti collegati').': '.$e->getMessage().'</div>';
        }
    }

    /**
     * Gestisce la richiesta AJAX per i documenti collegati
     *
     * @param int $id_record ID del record
     * @param string $tipo_record Tipo di record (es. 'intervento', 'tipo_intervento', ecc.)
     * @param bool $count_only Se true, restituisce solo il conteggio
     * @return void
     */
    public static function handleAjaxRequest($id_record, $tipo_record = 'intervento', $count_only = false)
    {
        try {
            if ($count_only) {
                // Restituisci solo il conteggio
                $count = self::countDocumenti($id_record, $tipo_record);
                header('Content-Type: application/json');
                echo json_encode(['count' => $count]);
            } else {
                // Restituisci l'HTML completo
                $html = self::renderDocumenti($id_record, $tipo_record);
                echo $html;
            }
        } catch (\Exception $e) {
            if ($count_only) {
                header('Content-Type: application/json');
                echo json_encode(['count' => 0, 'error' => 'Errore database: '.$e->getMessage()]);
            } else {
                echo '<div class="alert alert-danger">'.tr('Errore nel caricamento dei documenti collegati').': '.$e->getMessage().'</div>';
            }
        }

        exit;
    }
}
