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

use Carbon\Carbon;
use Models\Locale;
use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\PrimaNota\Mastrino;
use Modules\PrimaNota\Movimento;

/**
 * Verifica la presenza di sbilanci nel libro giornale per un determinato periodo.
 *
 * @param string $date_start Data inizio periodo
 * @param string $date_end   Data fine periodo
 *
 * @return array Array con informazioni sugli sbilanci trovati
 */
function verificaSbilanciLibroGiornale($date_start, $date_end)
{
    global $dbo;

    $totali = $dbo->fetchOne('
        SELECT
            SUM(CASE WHEN totale_raggruppato >= 0 THEN totale_raggruppato ELSE 0 END) AS totale_dare,
            SUM(CASE WHEN totale_raggruppato < 0 THEN totale_raggruppato ELSE 0 END) AS totale_avere,
            COUNT(DISTINCT idmastrino) AS numero_mastrini,
            COUNT(*) AS numero_movimenti
        FROM (
            SELECT
                co_movimenti.idmastrino,
                co_movimenti.idconto,
                SUM(co_movimenti.totale) AS totale_raggruppato
            FROM co_movimenti
            INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id
            INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id
            WHERE co_movimenti.data >= '.prepare($date_start).' AND co_movimenti.data <= '.prepare($date_end).'
            GROUP BY co_movimenti.idmastrino, co_movimenti.idconto
        ) AS movimenti_raggruppati
    ');

    $totale_dare = floatval($totali['totale_dare']);
    $totale_avere = floatval($totali['totale_avere']); // Questo è negativo

    $sbilancio_totale = $totale_dare + $totale_avere;
    $ha_sbilanci = abs($sbilancio_totale) > 0.01;

    $risultato = [
        'ha_sbilanci' => $ha_sbilanci,
        'totale_sbilancio' => abs($sbilancio_totale),
    ];

    return $risultato;
}

/**
 * Calcola gli importi della liquidazione IVA per un determinato periodo.
 *
 * @param string $date_start Data inizio periodo
 * @param string $date_end   Data fine periodo
 *
 * @return array Array con tutti i dati calcolati per stampa e movimento
 */
function calcolaImportiLiquidazioneIva($date_start, $date_end)
{
    global $dbo;

    $anno_precedente_start = (new Carbon($date_start))->subYears(1)->format('Y-m-d');
    $anno_precedente_end = (new Carbon($date_end))->subYears(1)->format('Y-m-d');
    $periodo = setting('Liquidazione iva');

    if ($periodo == 'Mensile') {
        $periodo_precedente_start = (new Carbon($date_start))->subMonth()->format('Y-m-d');
        $periodo_precedente_end = (new Carbon($date_end))->subMonth()->format('Y-m-d');
    } else {
        $periodo_precedente_start = (new Carbon($date_start))->subMonths(3)->format('Y-m-d');
        $periodo_precedente_end = (new Carbon($date_end))->subMonths(3)->format('Y-m-d');
    }

    $vendita_banco = Module::where('name', 'Vendita al banco')->first()->id;
    $maggiorazione = 0;

    // Calcolo IVA su fatture + vendite al banco (se presente il modulo)
    if (!empty($vendita_banco)) {
        $iva_vendite_esigibile = $dbo->fetchArray('
        SELECT
            `id`,
            `cod_iva`,
            `aliquota`,
            `descrizione`,
            SUM(`iva`) AS iva,
            SUM(`subtotale`) AS subtotale
        FROM
            (
            SELECT
                `co_documenti`.`id` AS id,
                `co_iva`.`codice_natura_fe` AS cod_iva,
                `co_iva`.`percentuale` AS aliquota,
                `co_iva_lang`.`title` AS descrizione,
                SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
                SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
                IF(
                    (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                    DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                    DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
                ) AS data_competenza_iva
            FROM
                `co_iva`
                LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
                INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
                INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
                INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
            WHERE
                `co_tipidocumento`.`dir` = "entrata" AND `co_righe_documenti`.`is_descrizione` = 0 AND `co_documenti`.`split_payment` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
            GROUP BY
                `cod_iva`, `aliquota`, `descrizione`, `co_documenti`.`id`
            HAVING
                data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
        UNION
            SELECT
                `vb_venditabanco`.`id` AS id,
                `co_iva`.`codice_natura_fe` AS cod_iva,
                `co_iva`.`percentuale` AS aliquota,
                `co_iva_lang`.`title` AS descrizione,
                SUM(`vb_righe_venditabanco`.`iva`) AS iva,
                SUM(
                    `vb_righe_venditabanco`.`subtotale` - `vb_righe_venditabanco`.`sconto`
                ) AS subtotale,
                `vb_venditabanco`.`data` as data_competenza_iva
            FROM
                `co_iva`
                LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
                INNER JOIN `vb_righe_venditabanco` ON `vb_righe_venditabanco`.`idiva` = `co_iva`.`id`
                INNER JOIN `vb_venditabanco` ON `vb_venditabanco`.`id` = `vb_righe_venditabanco`.`idvendita`
                INNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`
            WHERE
                `vb_righe_venditabanco`.`is_descrizione` = 0 AND `vb_stati_vendita`.`descrizione` = "Pagato"
            GROUP BY
                `cod_iva`, `aliquota`, `descrizione`, `vb_venditabanco`.`id`
            HAVING
                data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
            ) AS tabella
        GROUP BY
            `cod_iva`,
            `aliquota`,
            `descrizione`,
            `id`');

        $iva_vendite = $dbo->fetchArray('
        SELECT
            `id`,
            `cod_iva`,
            `aliquota`,
            `descrizione`,
            SUM(`iva`) AS iva,
            SUM(`subtotale`) AS subtotale
        FROM
            (
            SELECT
                `co_documenti`.`id` AS id,
                `co_iva`.`codice_natura_fe` AS cod_iva,
                `co_iva`.`percentuale` AS aliquota,
                `co_iva_lang`.`title` AS descrizione,
                SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
                SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
                IF(
                    (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                    DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                    DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
                ) AS data_competenza_iva
            FROM
                `co_iva`
                LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
                INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = co_iva.id
                INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
                INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
            WHERE
                `co_tipidocumento`.`dir` = "entrata" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN(SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
            GROUP BY
                `cod_iva`, `aliquota`, `descrizione`, `co_documenti`.`id`
            HAVING
                data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
        UNION
            SELECT
                `vb_venditabanco`.`id` AS id,
                `co_iva`.`codice_natura_fe` AS cod_iva,
                `co_iva`.`percentuale` AS aliquota,
                `co_iva_lang`.`title` AS descrizione,
                SUM(`vb_righe_venditabanco`.`iva`) AS iva,
                SUM(`vb_righe_venditabanco`.`subtotale` - `vb_righe_venditabanco`.`sconto`) AS subtotale,
                `vb_venditabanco`.`data` as data_competenza_iva
            FROM
                `co_iva`
                LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
                INNER JOIN `vb_righe_venditabanco` ON `vb_righe_venditabanco`.`idiva` = `co_iva`.`id`
                INNER JOIN `vb_venditabanco` ON `vb_venditabanco`.`id` = `vb_righe_venditabanco`.`idvendita`
                INNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`
            WHERE
                `vb_righe_venditabanco`.`is_descrizione` = 0 AND `vb_stati_vendita`.`descrizione` = "Pagato"
            GROUP BY
                `cod_iva`, `aliquota`, `descrizione`, `vb_venditabanco`.`id`
            HAVING
                data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
            ) AS tabella
        GROUP BY
            `cod_iva`,
            `aliquota`,
            `descrizione`,
            `id`');
    } else {
        // Calcolo IVA solo su fatture (senza vendite al banco)
        $iva_vendite_esigibile = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "entrata" AND `co_righe_documenti`.`is_descrizione` = 0 AND `co_documenti`.`split_payment` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
        ORDER BY `aliquota` desc');

        $iva_vendite = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "entrata" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
        ORDER BY `aliquota` desc');
    }

    // Calcolo IVA non esigibile (split payment)
    $iva_vendite_nonesigibile = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "entrata" AND `co_righe_documenti`.`is_descrizione` = 0 AND `co_documenti`.`split_payment` = 1 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
        ORDER BY `aliquota` desc');

    // Calcolo IVA detraibile (acquisti)
    $iva_acquisti_detraibile = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "uscita" AND `co_righe_documenti`.`is_descrizione` = 0 AND `co_documenti`.`split_payment` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata")) AND `co_iva`.`indetraibile` != 100
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
        ORDER BY `aliquota` desc');

    // Calcolo IVA non detraibile (acquisti)
    $iva_acquisti_nondetraibile = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *`indetraibile`/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "uscita" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata")) AND `co_iva`.`indetraibile` != 0
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
        ORDER BY `aliquota` desc');

    // Calcolo IVA acquisti totale
    $iva_acquisti = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "uscita" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($date_start).' AND '.prepare($date_end).'
        ORDER BY `aliquota` desc');

    // Calcolo dati per periodi precedenti (necessari per la stampa)
    $iva_vendite_anno_precedente = [];
    $iva_vendite_periodo_precedente = [];
    $iva_acquisti_anno_precedente = [];
    $iva_acquisti_periodo_precedente = [];

    // Query semplificate per i totali dei periodi precedenti
    if (!empty($vendita_banco)) {
        // Con vendite al banco - query più complesse
        $iva_vendite_anno_precedente = $dbo->fetchArray('
        SELECT
            `cod_iva`,
            `aliquota`,
            `descrizione`,
            SUM(`iva`) AS iva,
            SUM(`subtotale`) AS subtotale
        FROM
            (
            SELECT
                `co_iva`.`codice_natura_fe` AS cod_iva,
                `co_iva`.`percentuale` AS aliquota,
                `co_iva_lang`.`title` AS descrizione,
                SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
                SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
                IF(
                    (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                    DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                    DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
                ) AS data_competenza_iva
            FROM
                `co_iva`
                LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
                INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
                INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
                INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
            WHERE
                `co_tipidocumento`.`dir` = "entrata" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
            GROUP BY
                `cod_iva`, `aliquota`, `descrizione`, `co_documenti`.`id`
            HAVING
                data_competenza_iva BETWEEN '.prepare($anno_precedente_start).' AND '.prepare($anno_precedente_end).'
        UNION
            SELECT
                `co_iva`.`codice_natura_fe` AS cod_iva,
                `co_iva`.`percentuale` AS aliquota,
                `co_iva_lang`.`title` AS descrizione,
                SUM(`vb_righe_venditabanco`.`iva`) AS iva,
                SUM(`vb_righe_venditabanco`.`subtotale` - `vb_righe_venditabanco`.`sconto`) AS subtotale,
                `vb_venditabanco`.`data` as data_competenza_iva
            FROM
                `co_iva`
                LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
                INNER JOIN `vb_righe_venditabanco` ON `vb_righe_venditabanco`.`idiva` = `co_iva`.`id`
                INNER JOIN `vb_venditabanco` ON `vb_venditabanco`.`id` = `vb_righe_venditabanco`.`idvendita`
                INNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`
            WHERE
                `vb_righe_venditabanco`.`is_descrizione` = 0 AND `vb_stati_vendita`.`descrizione` = "Pagato"
            GROUP BY
                `cod_iva`, `aliquota`, `descrizione`, `vb_venditabanco`.`id`
            HAVING
                data_competenza_iva BETWEEN '.prepare($anno_precedente_start).' AND '.prepare($anno_precedente_end).'
            ) AS tabella
        GROUP BY
            `cod_iva`,
            `aliquota`,
            `descrizione`');

        $iva_vendite_periodo_precedente = $dbo->fetchArray('
        SELECT
            `cod_iva`,
            `aliquota`,
            `descrizione`,
            SUM(`iva`) AS iva,
            SUM(`subtotale`) AS subtotale
        FROM
            (
            SELECT
                `co_iva`.`codice_natura_fe` AS cod_iva,
                `co_iva`.`percentuale` AS aliquota,
                `co_iva_lang`.`title` AS descrizione,
                SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
                SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
                IF(
                    (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                    DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                    DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
                ) AS data_competenza_iva
            FROM
                `co_iva`
                LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
                INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
                INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
                INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
            WHERE
                `co_tipidocumento`.`dir` = "entrata" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
            GROUP BY
                `cod_iva`, `aliquota`, `descrizione`, `co_documenti`.`id`
            HAVING
                data_competenza_iva BETWEEN '.prepare($periodo_precedente_start).' AND '.prepare($periodo_precedente_end).'
        UNION
            SELECT
                `co_iva`.`codice_natura_fe` AS cod_iva,
                `co_iva`.`percentuale` AS aliquota,
                `co_iva_lang`.`title` AS descrizione,
                SUM(`vb_righe_venditabanco`.`iva`) AS iva,
                SUM(`vb_righe_venditabanco`.`subtotale` - `vb_righe_venditabanco`.`sconto`) AS subtotale,
                `vb_venditabanco`.`data` as data_competenza_iva
            FROM
                `co_iva`
                LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
                INNER JOIN `vb_righe_venditabanco` ON `vb_righe_venditabanco`.`idiva` = `co_iva`.`id`
                INNER JOIN `vb_venditabanco` ON `vb_venditabanco`.`id` = `vb_righe_venditabanco`.`idvendita`
                INNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`
            WHERE
                `vb_righe_venditabanco`.`is_descrizione` = 0 AND `vb_stati_vendita`.`descrizione` = "Pagato"
            GROUP BY
                `cod_iva`, `aliquota`, `descrizione`, `vb_venditabanco`.`id`
            HAVING
                data_competenza_iva BETWEEN '.prepare($periodo_precedente_start).' AND '.prepare($periodo_precedente_end).'
            ) AS tabella
        GROUP BY
            `cod_iva`,
            `aliquota`,
            `descrizione`');
    } else {
        // Solo fatture - query semplificate
        $iva_vendite_anno_precedente = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "entrata" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($anno_precedente_start).' AND '.prepare($anno_precedente_end).'
        ORDER BY aliquota desc');

        $iva_vendite_periodo_precedente = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "entrata" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($periodo_precedente_start).' AND '.prepare($periodo_precedente_end).'
        ORDER BY `aliquota` desc');
    }

    // Query per acquisti dei periodi precedenti
    $iva_acquisti_anno_precedente = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "uscita" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($anno_precedente_start).' AND '.prepare($anno_precedente_end).'
        ORDER BY `aliquota` desc');

    $iva_acquisti_periodo_precedente = $dbo->fetchArray('
        SELECT
            `co_iva`.`codice_natura_fe` AS cod_iva,
            `co_iva`.`percentuale` AS aliquota,
            `co_iva_lang`.`title` AS descrizione,
            SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
            SUM((`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto` + `co_righe_documenti`.`rivalsainps`) *(IF(`co_tipidocumento`.`reversed` = 0,1,-1))) AS subtotale,
            IF(
                (YEAR(co_documenti.data_registrazione) = YEAR(co_documenti.data_competenza) AND MONTH(co_documenti.data_registrazione) > MONTH(co_documenti.data_competenza) AND DAY(co_documenti.data_registrazione) >= 16) OR (YEAR(co_documenti.data_registrazione) > YEAR(co_documenti.data_competenza)),
                DATE_FORMAT(co_documenti.data_registrazione, \'%Y-%m-01\'),
                DATE_FORMAT(co_documenti.data_competenza, \'%Y-%m-01\')
            ) AS data_competenza_iva
        FROM
            `co_iva`
            LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).')
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
            INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = "uscita" AND `co_righe_documenti`.`is_descrizione` = 0 AND `idstatodocumento` NOT IN (SELECT `id` FROM `co_statidocumento` WHERE `name` IN ("Bozza", "Annullata"))
        GROUP BY
            `co_iva`.`id`, `co_documenti`.`id`
        HAVING
            data_competenza_iva BETWEEN '.prepare($periodo_precedente_start).' AND '.prepare($periodo_precedente_end).'
        ORDER BY `aliquota` desc');

    $acconto_iva_periodo_corrente = $dbo->fetchOne('
        SELECT
            SUM(totale) AS totale
        FROM
            co_movimenti
            INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id
        WHERE
            co_pianodeiconti3.descrizione = "Erario c/to iva acconto"
            AND co_movimenti.data >= '.prepare($date_start).' AND co_movimenti.data <= '.prepare($date_end));

    $acconto_iva_periodo_precedente = $dbo->fetchOne('
        SELECT
            SUM(totale) AS totale
        FROM
            co_movimenti
            INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id
        WHERE
            co_pianodeiconti3.descrizione = "Erario c/to iva acconto"
            AND co_movimenti.data >= '.prepare($periodo_precedente_start).' AND co_movimenti.data <= '.prepare($periodo_precedente_end));

    $acconto_iva_periodo_precedente_utilizzato = $dbo->fetchOne('
            SELECT
                -SUM(totale) AS totale
            FROM
                co_movimenti
                INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id
            WHERE
                co_pianodeiconti3.descrizione = "Erario c/to iva acconto"
                AND co_movimenti.data >= '.prepare($date_start).' AND co_movimenti.data <= '.prepare($date_end).'
                AND co_movimenti.totale < 0');

    // Calcolo totali
    $totale_iva_esigibile = sum(array_column($iva_vendite_esigibile, 'iva'), null, 2);
    $totale_iva_nonesigibile = sum(array_column($iva_vendite_nonesigibile, 'iva'), null, 2);
    $totale_iva_detraibile = sum(array_column($iva_acquisti_detraibile, 'iva'), null, 2);
    $totale_iva_nondetraibile = sum(array_column($iva_acquisti_nondetraibile, 'iva'), null, 2);

    $totale_iva_vendite_periodo_precedente = sum(array_column($iva_vendite_periodo_precedente, 'iva'), null, 2);
    $totale_iva_acquisti_periodo_precedente = sum(array_column($iva_acquisti_periodo_precedente, 'iva'), null, 2);
    $totale_iva_periodo_precedente = $totale_iva_vendite_periodo_precedente - $totale_iva_acquisti_periodo_precedente;

    $totale_iva = $totale_iva_esigibile - $totale_iva_detraibile;

    // Calcolo maggiorazione per liquidazioni trimestrali
    $maggiorazione = 0;
    $totale_iva_maggiorata = $totale_iva;

    if ($periodo == 'Trimestrale') {
        if ($totale_iva_periodo_precedente > 0) {
            $totale_iva += $totale_iva_periodo_precedente;
        }
        $maggiorazione = $totale_iva * 0.01;
        $totale_iva_maggiorata = $totale_iva + $maggiorazione;
    }

    return [
        'iva_vendite_esigibile' => $iva_vendite_esigibile,
        'iva_vendite' => $iva_vendite,
        'iva_vendite_nonesigibile' => $iva_vendite_nonesigibile,
        'iva_acquisti_detraibile' => $iva_acquisti_detraibile,
        'iva_acquisti_nondetraibile' => $iva_acquisti_nondetraibile,
        'iva_acquisti' => $iva_acquisti,
        'iva_vendite_anno_precedente' => $iva_vendite_anno_precedente,
        'iva_vendite_periodo_precedente' => $iva_vendite_periodo_precedente,
        'iva_acquisti_anno_precedente' => $iva_acquisti_anno_precedente,
        'iva_acquisti_periodo_precedente' => $iva_acquisti_periodo_precedente,
        'acconto_iva_periodo_corrente' => $acconto_iva_periodo_corrente,
        'acconto_iva_periodo_precedente' => $acconto_iva_periodo_precedente,
        'acconto_iva_periodo_precedente_utilizzato' => $acconto_iva_periodo_precedente_utilizzato,
        'totale_iva_esigibile' => $totale_iva_esigibile,
        'totale_iva_nonesigibile' => $totale_iva_nonesigibile,
        'totale_iva_detraibile' => $totale_iva_detraibile,
        'totale_iva_nondetraibile' => $totale_iva_nondetraibile,
        'totale_iva_vendite_periodo_precedente' => $totale_iva_vendite_periodo_precedente,
        'totale_iva_acquisti_periodo_precedente' => $totale_iva_acquisti_periodo_precedente,
        'totale_iva_periodo_precedente' => $totale_iva_periodo_precedente,
        'totale_iva' => $totale_iva,
        'totale_iva_maggiorata' => $totale_iva_maggiorata,
        'maggiorazione' => $maggiorazione,
        'periodo' => $periodo,
        'vendita_banco' => $vendita_banco,
        'is_debito' => $totale_iva >= 0,
        'importo_finale' => $periodo == 'Mensile' ? $totale_iva : $totale_iva_maggiorata,
        'date_start' => $date_start,
        'date_end' => $date_end,
        'periodo_precedente_start' => $periodo_precedente_start,
        'periodo_precedente_end' => $periodo_precedente_end,
        'anno_precedente_start' => $anno_precedente_start,
        'anno_precedente_end' => $anno_precedente_end,
    ];
}

/**
 * Crea il movimento in prima nota per la liquidazione IVA.
 *
 * @param string $date_start Data inizio periodo
 * @param string $date_end   Data fine periodo
 *
 * @return int|null ID del mastrino creato o null in caso di errore
 */
function creaMovimentoLiquidazioneIva($date_start, $date_end)
{
    try {
        // Calcola gli importi della liquidazione
        $importi = calcolaImportiLiquidazioneIva($date_start, $date_end);

        // Se non c'è IVA da versare o da recuperare, non creare il movimento
        if (abs($importi['importo_finale']) < 0.01) {
            return null;
        }

        // Recupera i conti dalle impostazioni
        $id_conto_erario = setting('Conto per erario Iva');
        $id_conto_iva_debito = setting('Conto per Iva su vendite');
        $id_conto_iva_credito = setting('Conto per Iva su acquisti');

        if (empty($id_conto_erario) || empty($id_conto_iva_debito) || empty($id_conto_iva_credito)) {
            throw new Exception('Conti IVA non configurati nelle impostazioni');
        }

        // Crea il mastrino
        $descrizione = 'Liquidazione IVA dal '.dateFormat($date_start).' al '.dateFormat($date_end);
        $mastrino = Mastrino::build($descrizione, $date_end, false, true);
        $mastrino->save();

        $importo_finale = abs($importi['importo_finale']);

        if ($importi['is_debito']) {
            // IVA a debito: DARE Conto IVA vendite, AVERE Erario IVA
            $movimento_dare = Movimento::build($mastrino, $id_conto_iva_debito);
            $movimento_dare->setTotale(0, $importo_finale);
            $movimento_dare->save();

            $movimento_avere = Movimento::build($mastrino, $id_conto_erario);
            $movimento_avere->setTotale($importo_finale, 0);
            $movimento_avere->save();
        } else {
            // IVA a credito: DARE Erario IVA, AVERE Conto IVA acquisti
            $movimento_dare = Movimento::build($mastrino, $id_conto_erario);
            $movimento_dare->setTotale(0, $importo_finale);
            $movimento_dare->save();

            $movimento_avere = Movimento::build($mastrino, $id_conto_iva_credito);
            $movimento_avere->setTotale($importo_finale, 0);
            $movimento_avere->save();
        }

        return $mastrino->idmastrino;
    } catch (Exception $e) {
        // Log dell'errore
        error_log('Errore nella creazione del movimento di liquidazione IVA: '.$e->getMessage());

        return null;
    }
}

/**
 * Genera il file XML per la LIPE (Liquidazione IVA Periodica).
 *
 * @param string $date_start Data inizio periodo
 * @param string $date_end   Data fine periodo
 *
 * @return string Contenuto del file XML
 */
function generaXmlLipe($date_start, $date_end)
{
    // Recupera i dati dell'anagrafica azienda
    $azienda = Anagrafica::find(setting('Azienda predefinita'));

    if (empty($azienda)) {
        throw new Exception('Anagrafica azienda non configurata');
    }

    // Determina i mesi da includere
    $data_start = new Carbon($date_start);
    $data_end = new Carbon($date_end);
    $anno = $data_start->format('Y');

    // Calcola i mesi del periodo
    $mesi = [];
    $current = clone $data_start;
    while ($current <= $data_end) {
        $mesi[] = (int) $current->format('m');
        $current->addMonth();
    }

    // Recupera dati intermediario (da impostazioni o fallback ai dati azienda)
    $cf_dichiarante = setting('Codice fiscale dichiarante') ?: $azienda->codice_fiscale;
    $cf_intermediario = setting('Codice fiscale intermediario');
    $identificativo_software = setting('Identificativo software');
    $piva = $azienda->piva ?: '';

    // Crea il documento DOM
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    // Crea elemento radice Fornitura con namespace
    $fornitura = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:Fornitura');
    $fornitura->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
    $dom->appendChild($fornitura);

    // Crea Intestazione
    $intestazione = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:Intestazione');
    $fornitura->appendChild($intestazione);

    // CodiceFornitura
    $codiceFornitura = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:CodiceFornitura');
    $codiceFornitura->appendChild($dom->createTextNode('IVP18'));
    $intestazione->appendChild($codiceFornitura);

    // CodiceFiscaleDichiarante
    $codiceFiscaleDichiarante = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:CodiceFiscaleDichiarante');
    $codiceFiscaleDichiarante->appendChild($dom->createTextNode($cf_dichiarante ?: ''));
    $intestazione->appendChild($codiceFiscaleDichiarante);

    // CodiceCarica
    $codiceCarica = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:CodiceCarica');
    $codiceCarica->appendChild($dom->createTextNode('1'));
    $intestazione->appendChild($codiceCarica);

    // Crea Comunicazione
    $comunicazione = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:Comunicazione');
    $comunicazione->setAttribute('identificativo', '00001');
    $fornitura->appendChild($comunicazione);

    // Crea Frontespizio
    $frontespizio = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:Frontespizio');
    $comunicazione->appendChild($frontespizio);

    // CodiceFiscale (Partita IVA)
    $codiceFiscale = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:CodiceFiscale');
    $codiceFiscale->appendChild($dom->createTextNode($piva ?: ''));
    $frontespizio->appendChild($codiceFiscale);

    // AnnoImposta
    $annoImposta = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:AnnoImposta');
    $annoImposta->appendChild($dom->createTextNode($anno));
    $frontespizio->appendChild($annoImposta);

    // PartitaIVA
    $partitaIVA = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:PartitaIVA');
    $partitaIVA->appendChild($dom->createTextNode($piva ?: ''));
    $frontespizio->appendChild($partitaIVA);

    // CFDichiarante
    $cfDichiarante = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:CFDichiarante');
    $cfDichiarante->appendChild($dom->createTextNode($cf_dichiarante ?: ''));
    $frontespizio->appendChild($cfDichiarante);

    // CodiceCaricaDichiarante
    $codiceCaricaDichiarante = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:CodiceCaricaDichiarante');
    $codiceCaricaDichiarante->appendChild($dom->createTextNode('1'));
    $frontespizio->appendChild($codiceCaricaDichiarante);

    // FirmaDichiarazione
    $firmaDichiarazione = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:FirmaDichiarazione');
    $firmaDichiarazione->appendChild($dom->createTextNode('1'));
    $frontespizio->appendChild($firmaDichiarazione);

    // CFIntermediario
    if ($cf_intermediario) {
        $cfIntermediario = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:CFIntermediario');
        $cfIntermediario->appendChild($dom->createTextNode($cf_intermediario ?: ''));
        $frontespizio->appendChild($cfIntermediario);

        // ImpegnoPresentazione
        $impegnoPresentazione = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:ImpegnoPresentazione');
        $impegnoPresentazione->appendChild($dom->createTextNode('1'));
        $frontespizio->appendChild($impegnoPresentazione);

        // DataImpegno
        $dataImpegno = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:DataImpegno');
        $dataImpegno->appendChild($dom->createTextNode(date('dmY')));
        $frontespizio->appendChild($dataImpegno);

        // FirmaIntermediario
        $firmaIntermediario = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:FirmaIntermediario');
        $firmaIntermediario->appendChild($dom->createTextNode('1'));
        $frontespizio->appendChild($firmaIntermediario);
    }

    // IdentificativoProdSoftware
    if ($identificativo_software) {
        $identificativoProdSoftware = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:IdentificativoProdSoftware');
        $identificativoProdSoftware->appendChild($dom->createTextNode($identificativo_software ?: ''));
        $frontespizio->appendChild($identificativoProdSoftware);
    }

    // Crea DatiContabili
    $datiContabili = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:DatiContabili');
    $comunicazione->appendChild($datiContabili);

    // Genera i moduli per ogni mese
    $numero_modulo = 1;

    foreach ($mesi as $mese) {
        // Calcola date per il mese corrente
        $mese_start = $anno.'-'.str_pad((string) $mese, 2, '0', STR_PAD_LEFT).'-01';
        $mese_end = (new Carbon($mese_start))->endOfMonth()->format('Y-m-d');

        // Calcola importi per il mese specifico
        $importi_mese = calcolaImportiLiquidazioneIva($mese_start, $mese_end);

        // Calcola totali operazioni per il mese
        $totale_attive_mese = 0;
        $totale_passive_mese = 0;

        foreach ($importi_mese['iva_vendite'] as $iva) {
            $totale_attive_mese += floatval($iva['subtotale']);
        }

        foreach ($importi_mese['iva_acquisti'] as $iva) {
            $totale_passive_mese += floatval($iva['subtotale']);
        }

        $iva_esigibile_mese = floatval($importi_mese['totale_iva_esigibile']);
        $iva_detraibile_mese = floatval($importi_mese['totale_iva_detraibile']);
        $iva_dovuta_mese = $iva_esigibile_mese - $iva_detraibile_mese;
        $importo_da_versare_mese = max(0, $iva_dovuta_mese);

        // Crea elemento Modulo
        $modulo = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:Modulo');
        $datiContabili->appendChild($modulo);

        // NumeroModulo
        $numeroModuloEl = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:NumeroModulo');
        $numeroModuloEl->appendChild($dom->createTextNode((string) $numero_modulo));
        $modulo->appendChild($numeroModuloEl);

        // Mese
        $meseEl = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:Mese');
        $meseEl->appendChild($dom->createTextNode((string) $mese));
        $modulo->appendChild($meseEl);

        // TotaleOperazioniAttive
        $totaleAttive = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:TotaleOperazioniAttive');
        $totaleAttive->appendChild($dom->createTextNode(number_format($totale_attive_mese, 2, ',', '')));
        $modulo->appendChild($totaleAttive);

        // TotaleOperazioniPassive
        $totalePassive = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:TotaleOperazioniPassive');
        $totalePassive->appendChild($dom->createTextNode(number_format($totale_passive_mese, 2, ',', '')));
        $modulo->appendChild($totalePassive);

        // IvaEsigibile
        $ivaEsigibile = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:IvaEsigibile');
        $ivaEsigibile->appendChild($dom->createTextNode(number_format($iva_esigibile_mese, 2, ',', '')));
        $modulo->appendChild($ivaEsigibile);

        // IvaDetratta
        $ivaDetratta = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:IvaDetratta');
        $ivaDetratta->appendChild($dom->createTextNode(number_format($iva_detraibile_mese, 2, ',', '')));
        $modulo->appendChild($ivaDetratta);

        // IvaDovuta
        $ivaDovuta = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:IvaDovuta');
        $ivaDovuta->appendChild($dom->createTextNode(number_format($iva_dovuta_mese, 2, ',', '')));
        $modulo->appendChild($ivaDovuta);

        // ImportoDaVersare
        $importoDaVersare = $dom->createElementNS('urn:www.agenziaentrate.gov.it:specificheTecniche:sco:ivp', 'iv:ImportoDaVersare');
        $importoDaVersare->appendChild($dom->createTextNode(number_format($importo_da_versare_mese, 2, ',', '')));
        $modulo->appendChild($importoDaVersare);

        ++$numero_modulo;
    }

    return $dom->saveXML();
}
