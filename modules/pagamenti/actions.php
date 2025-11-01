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

use Modules\Pagamenti\Pagamento;

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            foreach (post('id') as $key => $id) {
                // Data fatturazione
                $giorno = 0;

                $pagamento = Pagamento::find($id);
                // Data fatturazione fine mese
                if (post('scadenza')[$key] == 2) {
                    $giorno = -1;
                }

                // Data fatturazione giorno fisso
                if (post('scadenza')[$key] == 3) {
                    $giorno = (int) post('giorno')[$key];
                }

                // Data fatturazione fine mese (giorno fisso)
                elseif (post('scadenza')[$key] == 4) {
                    $giorno = -(int) post('giorno')[$key] - 1;
                }

                if (empty($id)) {
                    $pagamento = Pagamento::build($descrizione, post('codice_modalita_pagamento_fe'));
                }

                if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                    $pagamento->name = $descrizione;
                }
                $pagamento->num_giorni = (int) post('distanza')[$key];
                $pagamento->giorno = $giorno;
                $pagamento->prc = (float) post('percentuale')[$key];
                $pagamento->idconto_vendite = post('idconto_vendite') ?: null;
                $pagamento->idconto_acquisti = post('idconto_acquisti') ?: null;
                $pagamento->descrizione_incasso = post('descrizione_incasso');
                $pagamento->importo_fisso_incasso = (float) post('importo_fisso_incasso');
                $pagamento->importo_percentuale_incasso = (float) post('importo_percentuale_incasso');
                $pagamento->codice_modalita_pagamento_fe = post('codice_modalita_pagamento_fe');
                $pagamento->save();
                $pagamento->setTranslation('title', $descrizione);
            }

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $codice_modalita_pagamento_fe = filter('codice_modalita_pagamento_fe_add');

        if (isset($descrizione)) {
            $id_pagamento = (new Pagamento())->getByField('title', $descrizione);

            if ($id_pagamento) {
                flash()->error(tr('Esiste già un metodo di pagamento con questo nome!'));
            } else {
                $pagamento = Pagamento::build($descrizione,$codice_modalita_pagamento_fe);
                $id_record = $dbo->lastInsertedID();
                $pagamento->save();

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'pagamento',
                ]));
            }
        }

        break;

    case 'delete':
        if (!empty($id_record)) {
            $descrizione = filter('descrizione');

            $dbo->query('DELETE FROM `co_pagamenti` WHERE `id` IN (SELECT `id_record` FROM `co_pagamenti_lang` WHERE `title` = '.prepare($descrizione).')');
            $dbo->query('DELETE FROM `co_pagamenti_lang` WHERE `title` = '.prepare($descrizione));

            $dbo->query('DELETE FROM `co_pagamenti` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'pagamento',
            ]));
        }

        break;

    case 'delete_rata':
        $id = filter('id');
        if (isset($id)) {
            $dbo->query('DELETE FROM `co_pagamenti` WHERE `id`='.prepare($id));
            flash()->info(tr('Elemento eliminato con successo!'));

            if ($id_record == $id) {
                $res = $dbo->fetchArray('SELECT * FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` WHERE `co_pagamenti`.`id`!='.prepare($id).' AND `title`='.prepare($record['descrizione']));
                if (count($res) != 0) {
                    redirect_url(base_path().'/editor.php?id_module='.$id_module.'&id_record='.$res[0]['id']);
                } else {
                    // $_POST['backto'] = 'record-list';
                    redirect_url(base_path().'/controller.php?id_module='.$id_module);
                }
            }
        }

        break;
}
