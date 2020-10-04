<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\DDT\DDT;

$documento = DDT::find($id_record);

$id_cliente = $documento['idanagrafica'];
$id_sede = $record['idsede_partenza'];

$pagamento = $dbo->fetchOne('SELECT * FROM co_pagamenti WHERE id = '.prepare($documento['idpagamento']));
$causale = $dbo->fetchOne('SELECT * FROM dt_causalet WHERE id = '.prepare($documento['idcausalet']));
$porto = $dbo->fetchOne('SELECT * FROM dt_porto WHERE id = '.prepare($documento['idporto']));
$aspetto_beni = $dbo->fetchOne('SELECT * FROM dt_aspettobeni WHERE id = '.prepare($documento['idaspettobeni']));
$spedizione = $dbo->fetchOne('SELECT * FROM dt_spedizione WHERE id = '.prepare($documento['idspedizione']));

$vettore = $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare($documento['idvettore']));

$tipo_doc = $documento->tipo->descrizione;
if (empty($documento['numero_esterno'])) {
    $numero = 'pro-forma '.$numero;
    $tipo_doc = tr('DDT pro-forma', [], ['upper' => true]);
} else {
    $numero = !empty($documento['numero_esterno']) ? $documento['numero_esterno'] : $documento['numero'];
}

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
$destinazione = '';
if (!empty($documento['idsede_destinazione'])) {
    $rsd = $dbo->fetchArray('SELECT (SELECT codice FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS ragione_sociale, nomesede, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale, id_nazione FROM an_sedi WHERE idanagrafica='.prepare($id_cliente).' AND id='.prepare($documento['idsede_destinazione']));

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
        $nazione = $database->fetchOne("SELECT * FROM an_nazioni WHERE id = ".prepare($rsd[0]['id_nazione']));
        if ($nazione['iso2']!='IT'){
            $destinazione .= ' - '.$nazione['name'];
        }
    }
}

// Sostituzioni specifiche
$custom = [
    'tipo_doc' => $tipo_doc,
    'numero' => $numero,
    'data' => Translator::dateToLocale($documento['data']),
    'pagamento' => $pagamento['descrizione'],
    'c_destinazione' => $destinazione,
    'aspettobeni' => $aspetto_beni['descrizione'],
    'causalet' => $causale['descrizione'],
    'porto' => $porto['descrizione'],
    'n_colli' => !empty($documento['n_colli']) ? $documento['n_colli'] : '',
    'spedizione' => $spedizione['descrizione'],
    'vettore' => $vettore['ragione_sociale'],
];

// Accesso solo a:
// - cliente se è impostato l'idanagrafica di un Cliente
// - utente qualsiasi con permessi almeno in lettura sul modulo
// - admin
if ((Auth::user()['gruppo'] == 'Clienti' && $id_cliente != Auth::user()['idanagrafica'] && !Auth::admin()) || Modules::getPermission($documento->module) == '-') {
    die(tr('Non hai i permessi per questa stampa!'));
}
