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
use Modules\Ordini\Ordine;

$documento = Ordine::find($id_record);
$d_qta = (int) setting('Cifre decimali per quantità in stampa');
$d_importi = (int) setting('Cifre decimali per importi in stampa');
$d_totali = (int) setting('Cifre decimali per totali in stampa');

$id_cliente = $documento['idanagrafica'];

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$destinazione = '';
if (!empty($documento->idsede_destinazione)) {
    $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS ragione_sociale, nomesede, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale, id_nazione, codice_destinatario FROM an_sedi WHERE idanagrafica='.prepare($id_cliente).' AND id='.prepare($documento->idsede_destinazione));

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
            $destinazione .= ' - '.$nazione->getTranslation('title');
        }
    }
    if (!empty($rsd[0]['codice_destinatario'])) {
        $codice_destinatario = $rsd[0]['codice_destinatario'];
    }
}

$numero = !empty($documento['numero_esterno']) ? $documento['numero_esterno'] : $documento['numero'];
$pagamento = $dbo->fetchOne('SELECT `co_pagamenti_lang`.`title` FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_pagamenti`.`id` = '.prepare($documento->idpagamento));

$porto = $dbo->fetchOne('SELECT `dt_porto_lang`.`title` as descrizione FROM `dt_porto` LEFT JOIN `dt_porto_lang` ON (`dt_porto`.`id` = `dt_porto_lang`.`id_record` AND `dt_porto_lang`.`id_lang` ='.prepare(Models\Locale::getDefault()->id).') WHERE `dt_porto`.`id` = '.prepare($documento['idporto']));
$spedizione = $dbo->fetchOne('SELECT `dt_spedizione_lang`.`title` as descrizione FROM `dt_spedizione` LEFT JOIN `dt_spedizione_lang` ON (`dt_spedizione`.`id`=`dt_spedizione_lang`.`id_record` AND `dt_spedizione_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `dt_spedizione`.`id` = '.prepare($documento['idspedizione']));
$vettore = $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare($documento['idvettore']));

// Sostituzioni specifiche
$custom = [
    'tipo_doc' => Stringy\Stringy::create($documento->tipo->getTranslation('title'))->toUpperCase(),
    'numero' => $numero,
    'data' => Translator::dateToLocale($documento['data']),
    'pagamento' => $pagamento['title'],
    'porto' => $porto['descrizione'],
    'spedizione' => $spedizione['descrizione'],
    'vettore' => $vettore['ragione_sociale'],
];
