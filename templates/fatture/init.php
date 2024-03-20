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

use Modules\Anagrafiche\Nazione;
use Modules\Fatture\Fattura;

$documento = Fattura::find($id_record);
$banca = $documento->getBanca();
$d_qta = (int) setting('Cifre decimali per quantità in stampa');
$d_importi = (int) setting('Cifre decimali per importi in stampa');
$d_totali = (int) setting('Cifre decimali per totali in stampa');

// Lettura info fattura
$record = $dbo->fetchOne('SELECT 
    `co_documenti`.*,
    `co_statidocumento_lang`.`name` AS stato_doc,
    `co_tipidocumento_lang`.`name` AS tipo_doc,
    `co_tipidocumento`.`dir` AS dir,
    `co_pagamenti_lang`.`name` AS pagamento,
    `dt_causalet_lang`.`name` AS causalet,
    `dt_porto_lang`.`name` AS porto,
    `dt_aspettobeni_lang`.`name` AS aspettobeni,
    `dt_spedizione_lang`.`name` AS spedizione,
    `vettore`.`ragione_sociale` AS vettore,
    `co_banche`.`id` AS id_banca,
    `zz_segments`.`is_fiscale` AS is_fiscale,
    `an_anagrafiche`.`tipo` AS tipo_cliente
FROM 
    `co_documenti`
    INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`co_documenti`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS vettore ON `vettore`.`idanagrafica` = `co_documenti`.`idvettore`
    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento`=`co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento`=`co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
    LEFT JOIN `co_banche` ON `co_banche`.`id` = `co_documenti`.`id_banca_azienda`
    INNER JOIN `zz_segments` ON `co_documenti`.`id_segment` = `zz_segments`.`id`
    LEFT JOIN `dt_causalet` ON `dt_causalet`.`id` = `co_documenti`.`idcausalet`
    LEFT JOIN `dt_causalet_lang` ON (`dt_causalet_lang`.`id_record` = `dt_causalet`.`id` AND `dt_causalet_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
    LEFT JOIN `dt_porto` ON `dt_porto`.`id` = `co_documenti`.`idporto`
    LEFT JOIN `dt_porto_lang` ON (`dt_porto_lang`.`id_record` = `dt_porto`.`id` AND `dt_porto_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
    LEFT JOIN `dt_aspettobeni` ON `dt_aspettobeni`.`id` = `co_documenti`.`idaspettobeni`
    LEFT JOIN `dt_aspettobeni_lang` ON (`dt_aspettobeni_lang`.`id_record` = `dt_aspettobeni`.`id` AND `dt_aspettobeni_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
    LEFT JOIN `dt_spedizione` ON `dt_spedizione`.`id` = `co_documenti`.`idspedizione`
    LEFT JOIN `dt_spedizione_lang` ON (`dt_spedizione_lang`.`id_record` = `dt_spedizione`.`id` AND `dt_spedizione_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
WHERE 
    `co_documenti`.`id`='.prepare($id_record));

$record['rivalsainps'] = floatval($record['rivalsainps']);
$record['ritenutaacconto'] = floatval($record['ritenutaacconto']);
$record['bollo'] = floatval($record['bollo']);

$nome_banca = $banca->nome;
$iban_banca = $banca->iban;
$bic_banca = $banca->bic;

$module_name = ($record['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';

$id_cliente = $record['idanagrafica'];
$tipo_cliente = $record['tipo_cliente'];

$tipo_doc = $record['tipo_doc'];
$numero = !empty($record['numero_esterno']) ? $record['numero_esterno'] : $record['numero'];

// Fix per le fattura accompagnatorie
$fattura_accompagnatoria = ($record['tipo_doc'] == 'Fattura accompagnatoria di vendita');

// Caso particolare per le fatture pro forma
if (empty($record['is_fiscale'])) {
    $tipo_doc = tr('Fattura pro forma');
}

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$destinazione = '';
if (!empty($record['idsede_destinazione'])) {
    $rsd = $dbo->fetchArray('SELECT (SELECT `codice` FROM `an_anagrafiche` WHERE `idanagrafica`=`an_sedi`.`idanagrafica`) AS codice, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica`=`an_sedi`.`idanagrafica`) AS ragione_sociale, `nomesede`, `indirizzo`, `indirizzo2`, `cap`, `citta`, `provincia`, `piva`, `codice_fiscale`, `id_nazione` FROM `an_sedi` WHERE `idanagrafica`='.prepare($id_cliente).' AND id='.prepare($record['idsede_destinazione']));

    if (!empty($rsd[0]['nomesede'])) {
        $destinazione .= $rsd[0]['nomesede'].'<br/>';
    }
    if (!empty($rsd[0]['indirizzo'])) {
        $destinazione .= $rsd[0]['indirizzo'].'<br/>';
    }
    if (!empty($rsd[0]['indirizzo2'])) {
        $destinazione .= $rsd[0]['indirizzo2'].'<br/>';
    }
    if (!empty($rsd[0]['cap'])) {
        $destinazione .= $rsd[0]['cap'].' ';
    }
    if (!empty($rsd[0]['citta'])) {
        $destinazione .= $rsd[0]['citta'];
    }
    if (!empty($rsd[0]['provincia'])) {
        $destinazione .= ' ('.$rsd[0]['provincia'].')';
    }
    if (!empty($rsd[0]['id_nazione'])) {
        $nazione = Nazione::find($rsd[0]['id_nazione']);
        if ($nazione['iso2'] != 'IT') {
            $destinazione .= ' - '.$nazione->getTranslation('name');
        }
    }
}

// Sostituzioni specifiche
$custom = [
    'tipo_doc' => Stringy\Stringy::create($tipo_doc)->toUpperCase(),
    'numero' => $numero,
    'tipo_documento' => $tipo_doc,
    'data' => Translator::dateToLocale($record['data']),
    'pagamento' => $record['id_pagamento'],
    'c_destinazione' => $destinazione,
    'aspettobeni' => $record['aspettobeni'],
    'causalet' => $record['causalet'],
    'porto' => $record['porto'],
    'n_colli' => !empty($record['n_colli']) ? $record['n_colli'] : '',
    'spedizione' => $record['spedizione'],
    'vettore' => $record['vettore'],
    'appoggiobancario' => $nome_banca,
    'codiceiban' => $iban_banca,
    'bic' => $bic_banca,
];

// Accesso solo a:
// - cliente se è impostato l'idanagrafica di un Cliente
// - utente qualsiasi con permessi almeno in lettura sul modulo
// - admin
if ((Auth::user()['gruppo'] == 'Clienti' && $id_cliente != Auth::user()['idanagrafica'] && !Auth::admin()) || Modules::getPermission($module_name) == '-') {
    exit(tr('Non hai i permessi per questa stampa!'));
}

if ($fattura_accompagnatoria) {
    $settings['footer-height'] += 40;
}
