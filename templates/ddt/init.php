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
use Modules\DDT\DDT;
use Modules\Pagamenti\Pagamento;

$documento = DDT::find($id_record);
$d_qta = (int) setting('Cifre decimali per quantità in stampa');
$d_importi = (int) setting('Cifre decimali per importi in stampa');
$d_totali = (int) setting('Cifre decimali per totali in stampa');

$id_cliente = $documento['id_anagrafica'];
$id_sede = $record['id_sede_partenza'];
$id_azienda = setting('Azienda predefinita');

$pagamento = Pagamento::find($documento['id_pagamento']);
$causale = $dbo->fetchOne('SELECT `dt_causalet`.*, `dt_causalet_lang`.`title` as descrizione FROM `dt_causalet` LEFT JOIN `dt_causalet_lang` ON (`dt_causalet`.`id` = `dt_causalet_lang`.`id_record` AND `dt_causalet_lang`.`id_lang` ='.prepare(Models\Locale::getDefault()->id).') WHERE `dt_causalet`.`id` = '.prepare($documento['id_causale_t']));
$porto = $dbo->fetchOne('SELECT `dt_porto`.*, `dt_porto_lang`.`title` as descrizione FROM `dt_porto` LEFT JOIN `dt_porto_lang` ON (`dt_porto`.`id` = `dt_porto_lang`.`id_record` AND `dt_porto_lang`.`id_lang` ='.prepare(Models\Locale::getDefault()->id).') WHERE `dt_porto`.`id` = '.prepare($documento['id_porto']));
$aspetto_beni = $dbo->fetchOne('SELECT `dt_aspettobeni`.*, `dt_aspettobeni_lang`.`title` as descrizione FROM `dt_aspettobeni` LEFT JOIN `dt_aspettobeni_lang` ON (`dt_aspettobeni`.`id`=`dt_aspettobeni_lang`.`id_record` AND `dt_aspettobeni_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `dt_aspettobeni`.`id` = '.prepare($documento['id_aspetto_beni']));
$spedizione = $dbo->fetchOne('SELECT `dt_spedizione`.*, `dt_spedizione_lang`.`title` as descrizione FROM `dt_spedizione` LEFT JOIN `dt_spedizione_lang` ON (`dt_spedizione`.`id`=`dt_spedizione_lang`.`id_record` AND `dt_spedizione_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `dt_spedizione`.`id` = '.prepare($documento['id_spedizione']));

$vettore = $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE id = '.prepare($documento['idvettore']));

$tipo_doc = $documento->tipo->getTranslation('title');
if ($tipo_doc != 'Ddt in entrata' && $tipo_doc != 'Ddt in uscita') {
    $numero = 'pro-forma '.$documento['numero'];
    $tipo_doc = tr('DDT pro-forma', [], ['upper' => true]);
} else {
    $numero = !empty($documento['numero_esterno']) ? $documento['numero_esterno'] : $documento['numero'];
}

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$destinazione = '';
if (!empty($documento['id_sede_destinazione'])) {
    if ($tipo_doc == 'Ddt in uscita') {
        $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE id=an_sedi.id_anagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE id=an_sedi.id_anagrafica) AS ragione_sociale, nome_sede, indirizzo, cap, citta, provincia, p_iva, codice_fiscale, id_nazione, telefono, cellulare FROM an_sedi WHERE id_anagrafica='.prepare($documento['id_anagrafica']).' AND id='.prepare($documento['id_sede_destinazione']));
    } else {
        $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE id=an_sedi.id_anagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE id=an_sedi.id_anagrafica) AS ragione_sociale, nome_sede, indirizzo, cap, citta, provincia, p_iva, codice_fiscale, id_nazione, telefono, cellulare FROM an_sedi WHERE id_anagrafica='.prepare($id_azienda).' AND id='.prepare($documento['id_sede_destinazione']));
    }

    if (!empty($rsd[0]['nome_sede'])) {
        $destinazione .= $rsd[0]['nome_sede'].'<br/>';
    }
    if (!empty($rsd[0]['indirizzo'])) {
        $destinazione .= $rsd[0]['indirizzo'].'<br/>';
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
            $destinazione .= ' - '.$nazione->getTranslation('title').'<br />';
        }
    }
    if (!empty($rsd[0]['telefono'])) {
        $destinazione .= 'Tel: '.$rsd[0]['telefono'].'<br />';
    }
    if (!empty($rsd[0]['cellualre'])) {
        $destinazione .= 'Cell: '.$rsd[0]['cellulare'];
    }
}

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$partenza = '';
if (!empty($documento['id_sede_partenza'])) {
    if ($tipo_doc != 'Ddt in uscita') {
        $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE id=an_sedi.id_anagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE id=an_sedi.id_anagrafica) AS ragione_sociale, nome_sede, indirizzo, cap, citta, provincia, p_iva, codice_fiscale, id_nazione, telefono, cellulare FROM an_sedi WHERE id_anagrafica='.prepare($documento['id_anagrafica']).' AND id='.prepare($documento['id_sede_partenza']));
    } else {
        $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE id=an_sedi.id_anagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE id=an_sedi.id_anagrafica) AS ragione_sociale, nome_sede, indirizzo, cap, citta, provincia, p_iva, codice_fiscale, id_nazione, telefono, cellulare FROM an_sedi WHERE id_anagrafica='.prepare($id_azienda).' AND id='.prepare($documento['id_sede_partenza']));
    }

    if (!empty($rsd[0]['nome_sede'])) {
        $partenza .= $rsd[0]['nome_sede'].'<br/>';
    }
    if (!empty($rsd[0]['indirizzo'])) {
        $partenza .= $rsd[0]['indirizzo'].'<br/>';
    }
    if (!empty($rsd[0]['cap'])) {
        $partenza .= $rsd[0]['cap'].' ';
    }
    if (!empty($rsd[0]['citta'])) {
        $partenza .= $rsd[0]['citta'];
    }
    if (!empty($rsd[0]['provincia'])) {
        $partenza .= ' ('.$rsd[0]['provincia'].')';
    }
    if (!empty($rsd[0]['id_nazione'])) {
        $nazione = Nazione::find($rsd[0]['id_nazione']);
        if ($nazione['iso2'] != 'IT') {
            $partenza .= ' - '.$nazione->getTranslation('title').'<br />';
        }
    }
    if (!empty($rsd[0]['telefono'])) {
        $partenza .= 'Tel: '.$rsd[0]['telefono'].'<br />';
    }
    if (!empty($rsd[0]['cellualre'])) {
        $partenza .= 'Cell: '.$rsd[0]['cellulare'];
    }
}

// Sostituzioni specifiche
$custom = [
    'tipo_doc' => $tipo_doc,
    'numero' => $numero,
    'data' => Translator::dateToLocale($documento['data']),
    'pagamento' => $pagamento ? $pagamento->getTranslation('title') : '',
    'c_destinazione' => $destinazione,
    'c_partenza' => $partenza,
    'aspettobeni' => $aspetto_beni['descrizione'],
    'causalet' => $causale['descrizione'],
    'porto' => $porto['descrizione'],
    'n_colli' => !empty($documento['n_colli']) ? $documento['n_colli'] : '',
    'spedizione' => $spedizione['descrizione'],
    'vettore' => $vettore['ragione_sociale'],
];

// Accesso solo a:
// - cliente se è impostato l'id_anagrafica di un Cliente
// - utente qualsiasi con permessi almeno in lettura sul modulo
// - admin
if ((auth_osm()->getUser()['gruppo'] == 'Clienti' && $id_cliente != auth_osm()->getUser()['id_anagrafica'] && !AuthOSM::admin()) || Modules::getPermission($documento->module) == '-') {
    exit(tr('Non hai i permessi per questa stampa!'));
}
