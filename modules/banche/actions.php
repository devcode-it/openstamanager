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

use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $id_anagrafica = filter('id_anagrafica');
        $anagrafica = Anagrafica::find($id_anagrafica);

        $nome = filter('nome');
        $iban = filter('iban');
        $bic = filter('bic');

        $banca = Banca::build($anagrafica, $nome, $iban, $bic);
        $id_record = $banca->id;

        if (isAjaxRequest()) {
            echo json_encode([
                'id' => $id_record,
                'text' => $nome,
            ]);
        }

        flash()->info(tr('Aggiunta nuova _TYPE_', [
            '_TYPE_' => 'banca',
        ]));

        break;

    case 'update':
        $nome = filter('nome');

        $banca->nome = post('nome');
        $banca->iban = post('iban');
        $banca->bic = post('bic');

        $banca->note = post('note');
        $banca->id_pianodeiconti3 = post('id_pianodeiconti3');
        $banca->filiale = post('filiale');
        $banca->creditor_id = post('creditor_id');
        $banca->codice_sia = post('codice_sia');

        $banca->predefined = post('predefined');

        $banca->save();

        flash()->info(tr('Salvataggio completato'));

        break;

    case 'delete':
        $banca->delete();

        flash()->info(tr('_TYPE_ eliminata con successo!', [
            '_TYPE_' => 'Banca',
        ]));

        break;
}
