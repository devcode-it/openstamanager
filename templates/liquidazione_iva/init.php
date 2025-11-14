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
include_once __DIR__.'/../../modules/stampe_contabili/modutil.php';

$date_start = filter('date_start');
$date_end = filter('date_end');

// Utilizza la funzione centralizzata per calcolare tutti gli importi
$dati_liquidazione = calcolaImportiLiquidazioneIva($date_start, $date_end);

// Estrae i dati calcolati
$iva_vendite_esigibile = $dati_liquidazione['iva_vendite_esigibile'];
$iva_vendite = $dati_liquidazione['iva_vendite'];
$iva_vendite_nonesigibile = $dati_liquidazione['iva_vendite_nonesigibile'];
$iva_acquisti_detraibile = $dati_liquidazione['iva_acquisti_detraibile'];
$iva_acquisti_nondetraibile = $dati_liquidazione['iva_acquisti_nondetraibile'];
$iva_acquisti = $dati_liquidazione['iva_acquisti'];
$periodo = $dati_liquidazione['periodo'];
$vendita_banco = $dati_liquidazione['vendita_banco'];
$maggiorazione = $dati_liquidazione['maggiorazione'];

// Estrae le date calcolate
$periodo_precedente_start = $dati_liquidazione['periodo_precedente_start'];
$periodo_precedente_end = $dati_liquidazione['periodo_precedente_end'];
$anno_precedente_start = $dati_liquidazione['anno_precedente_start'];
$anno_precedente_end = $dati_liquidazione['anno_precedente_end'];

// Estrae i dati dei periodi precedenti dalla funzione centralizzata
$iva_vendite_anno_precedente = $dati_liquidazione['iva_vendite_anno_precedente'];
$iva_vendite_periodo_precedente = $dati_liquidazione['iva_vendite_periodo_precedente'];
$iva_acquisti_anno_precedente = $dati_liquidazione['iva_acquisti_anno_precedente'];
$iva_acquisti_periodo_precedente = $dati_liquidazione['iva_acquisti_periodo_precedente'];

// Estrae i totali dei periodi precedenti
$totale_iva_vendite_periodo_precedente = $dati_liquidazione['totale_iva_vendite_periodo_precedente'];
$totale_iva_acquisti_periodo_precedente = $dati_liquidazione['totale_iva_acquisti_periodo_precedente'];
$totale_iva_periodo_precedente = $dati_liquidazione['totale_iva_periodo_precedente'];