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
use Carbon\Carbon;

$date_start = filter('date_start');
$date_end = filter('date_end');

$anno_precedente_start = (new Carbon($date_start))->subYears(1)->format('Y-m-d');
$anno_precedente_end = (new Carbon($date_end))->subYears(1)->format('Y-m-d');

$periodo = $dbo->fetchOne('SELECT valore FROM zz_settings WHERE nome="Liquidazione iva"');
if ($periodo['valore'] == 'Mensile') {
    $periodo_precedente_start = (new Carbon($date_start))->subMonth(1)->format('Y-m-d');
    $periodo_precedente_end = (new Carbon($date_end))->subMonth(1)->format('Y-m-d');
} else {
    $periodo_precedente_start = (new Carbon($date_start))->subMonth(3)->format('Y-m-d');
    $periodo_precedente_end = (new Carbon($date_end))->subMonth(3)->format('Y-m-d');
}

$maggiorazione = 0;
$iva_vendite_esigibile = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota, co_iva.descrizione AS descrizione,  SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva, SUM((subtotale-sconto)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS subtotale FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE co_documenti.split_payment=0 AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND dir="entrata" AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');

$iva_vendite_nonesigibile = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota,co_iva.descrizione AS descrizione, SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva, SUM((subtotale-sconto)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS subtotale FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE co_documenti.split_payment=1 AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND dir="entrata" AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');

$iva_vendite = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota,co_iva.descrizione AS descrizione, SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva, SUM((subtotale-sconto)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS subtotale FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE dir="entrata" AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');

$iva_vendite_anno_precedente = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota,co_iva.descrizione AS descrizione, SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE dir="entrata" AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($anno_precedente_start).' AND co_documenti.data_competenza <= '.prepare($anno_precedente_end).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');

$iva_vendite_periodo_precedente = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota,co_iva.descrizione AS descrizione, SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE dir="entrata" AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($periodo_precedente_start).' AND co_documenti.data_competenza <= '.prepare($periodo_precedente_end).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');

$iva_acquisti_detraibile = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota,co_iva.descrizione AS descrizione, SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva, SUM((subtotale-sconto)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS subtotale FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE co_documenti.split_payment=0 AND dir="uscita" AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end).' AND co_iva.indetraibile = 0 AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');

$iva_acquisti_nondetraibile = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota, co_iva.descrizione AS descrizione, co_iva.indetraibile AS indetraibile, SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva, SUM((subtotale-sconto)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS subtotale FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE dir="uscita" AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end).' AND co_iva.indetraibile != 0 AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');

$iva_acquisti = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota,co_iva.descrizione AS descrizione, SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva, SUM((subtotale-sconto)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS subtotale FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE dir="uscita" AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');

$iva_acquisti_anno_precedente = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota,co_iva.descrizione AS descrizione, SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE dir="uscita" AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($anno_precedente_start).' AND co_documenti.data_competenza <= '.prepare($anno_precedente_end).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');

$iva_acquisti_periodo_precedente = $dbo->fetchArray('SELECT co_iva.codice_natura_fe AS cod_iva, co_iva.percentuale AS aliquota,co_iva.descrizione AS descrizione, SUM((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed=0, 1, -1))) AS iva FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento INNER JOIN co_iva ON  co_righe_documenti.idiva=co_iva.id WHERE dir="uscita" AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($periodo_precedente_start).' AND co_documenti.data_competenza <= '.prepare($periodo_precedente_end).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") GROUP BY idiva');
