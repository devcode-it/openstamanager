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
use Modules\Iva\Aliquota;

switch (filter('op')) {
    case 'update':
        $esente = post('esente') ?: 0;
        $percentuale = empty($esente) ? post('percentuale') : 0;
        $indetraibile = post('indetraibile');
        $dicitura = post('dicitura');
        $codice = post('codice');
        $codice_natura_fe = post('codice_natura_fe') ?: null;
        $esigibilita = post('esigibilita');
        $descrizione = post('descrizione');

        $aliquota = Aliquota::where('id', '=', (new Aliquota())->getByField('title', $descrizione))->where('codice', '=', $codice)->orWhere('name', $descrizione)->where('id', '!=', $iva->id)->first();
        if (!$aliquota) {
            $iva->esente = $esente;
            $iva->percentuale = $percentuale;
            $iva->indetraibile = $indetraibile;
            $iva->dicitura = $dicitura ?: 0;
            $iva->codice = $codice;
            $iva->codice_natura_fe = $codice_natura_fe;
            $iva->esigibilita = $esigibilita;
            $iva->setTranslation('title', $descrizione);
            if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                $iva->name = $descrizione;
            }
            $iva->save();

            // Messaggio di avvertenza
            if ((stripos('N6', (string) $codice_natura_fe) === 0) && $esigibilita == 'S') {
                flash()->warning(tr('Combinazione di natura IVA _TYPE_ ed esigibilità non compatibile', [
                    '_TYPE_' => $codice_natura_fe,
                ]));
            }

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso codice e descrizione", [
                '_TYPE_' => 'IVA',
            ]));
        }
        break;

    case 'add':
        $descrizione = post('descrizione');
        $codice = post('codice');
        $esente = post('esente_add');
        $percentuale = empty($esente) ? post('percentuale') : 0;
        $codice_natura = post('codice_natura_fe') ?: '';
        $esigibilita = post('esigibilita');
        $indetraibile = post('indetraibile');

        $aliquota = Aliquota::where('id', '=', (new Aliquota())->getByField('title', $descrizione))->where('codice', '=', $codice)->first();
        if (!$aliquota) {
            $iva = Aliquota::build($esente, $percentuale, $indetraibile, $dicitura, $codice, $codice_natura_fe, $esigibilita);
            $id_record = $dbo->lastInsertedID();
            $iva->setTranslation('title', $descrizione);
            if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                $iva->name = $descrizione;
            }
            $iva->save();

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'IVA',
            ]));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso codice e descrizione", [
                '_TYPE_' => 'IVA',
            ]));
        }

        break;

    case 'delete':
        if (!empty($id_record)) {
            $dbo->query('UPDATE `co_iva` SET deleted_at = NOW() WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo', [
                '_TYPE_' => 'IVA',
            ]));
        }

        break;
}
