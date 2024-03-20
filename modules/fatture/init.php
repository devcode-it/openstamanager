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

use Modules\Fatture\Fattura;
use Modules\Fatture\StatoFE;

include_once __DIR__.'/../../core.php';

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

if (isset($id_record)) {
    $fattura = Fattura::with('tipo', 'stato')->find($id_record);
    $dir = $fattura->direzione;

    $is_fiscale = false;
    if (!empty($fattura)) {
        $is_fiscale = $fattura->isFiscale();
    }

    $record = $dbo->fetchOne('SELECT 
        `co_documenti`.*,
        `co_tipidocumento`.`reversed` AS is_reversed,
        `co_documenti`.`idagente` AS idagente_fattura,
        `co_documenti`.`note`,
        `co_documenti`.`note_aggiuntive`,
        `co_documenti`.`idpagamento`,
        `co_documenti`.`id` AS iddocumento,
		`co_documenti`.`split_payment` AS split_payment,
        `co_statidocumento_lang`.`name` AS `stato`,
        `co_tipidocumento_lang`.`name` AS `descrizione_tipo`,
        `co_tipidocumento`.`id` AS `idtipodocumento`,
        `zz_segments`.`is_fiscale` AS is_fiscale,
        (SELECT `descrizione` FROM `co_ritenutaacconto` WHERE `id`=`idritenutaacconto`) AS ritenutaacconto_desc,
        (SELECT `descrizione` FROM `co_rivalse` WHERE `id`=`idrivalsainps`) AS rivalsainps_desc,
        `dt_causalet_lang`.`name` AS causale_desc
    FROM `co_documenti`
        INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
        LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`
        INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id`
        LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento`=`co_pagamenti`.`id`
        LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        LEFT JOIN `dt_causalet` ON `co_documenti`.`idcausalet`=`dt_causalet`.`id`
        LEFT JOIN `dt_causalet_lang` ON (`dt_causalet_lang`.`id_record` = `dt_causalet`.`id` AND `dt_causalet_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
        INNER JOIN `zz_segments` ON `co_documenti`.`id_segment` = `zz_segments`.`id`
    WHERE 
        `co_tipidocumento`.`dir` = '.prepare($dir).' AND `co_documenti`.`id`='.prepare($id_record));

    // Note di credito collegate
    $note_accredito = $dbo->fetchArray("SELECT `co_documenti`.`id`, IF(`numero_esterno` != '', `numero_esterno`, `numero`) AS numero, data FROM `co_documenti` JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` WHERE `reversed` = 1 AND `ref_documento`=".prepare($id_record));

    // Blocco gestito dallo stato della Fattura Elettronica
    $stato_fe = StatoFE::find($fattura->codice_stato_fe)->id_record;
    $abilita_genera = empty($fattura->codice_stato_fe) || intval($stato_fe['is_generabile']);

    // Controllo autofattura e gestione avvisi
    $reverse_charge = null;
    $abilita_autofattura = null;
    $autofattura_vendita = null;
    $fattura_acquisto_originale = null;

    if (!empty($fattura)) {
        $reverse_charge = $fattura->getRighe()->first(function ($item, $key) {
            return $item->aliquota != null && substr($item->aliquota->codice_natura_fe, 0, 2) == 'N6';
        })->id;
        $autofattura_vendita = Fattura::find($fattura->id_autofattura);

        $abilita_autofattura = (($fattura->anagrafica->nazione->iso2 != 'IT' && !empty($fattura->anagrafica->nazione->iso2)) || $reverse_charge) && $dir == 'uscita' && $fattura->id_autofattura == null;

        $fattura_acquisto_originale = Fattura::where('id_autofattura', '=', $fattura->id)->first();
        $autofattura_collegata = Fattura::where('id_autofattura', '=', $fattura->id)->where('id', '!=', $fattura_acquisto_originale->id)->orderBy('id', 'DESC')->first();
    }

    $superselect['idtipodocumento'] = $record['idtipodocumento'];
}
