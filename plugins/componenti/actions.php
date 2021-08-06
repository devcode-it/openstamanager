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
use Modules\Articoli\Articolo;
use Modules\Impianti\Impianto;
use Plugins\ComponentiImpianti\Componente;

$operazione = filter('op');

switch ($operazione) {
    case 'add':
        $impianto = Impianto::find($id_record);

        $id_articolo = filter('id_articolo');
        $articolo = Articolo::find($id_articolo);

        $componente = Componente::build($impianto, $articolo, new Carbon());

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'update':
        $id_componente = filter('id_componente');
        $componente = Componente::find($id_componente);

        $componente->data_registrazione = filter('data_registrazione') ?: null;
        $componente->data_installazione = filter('data_installazione') ?: null;
        $componente->data_rimozione = filter('data_rimozione') ?: null;
        $componente->note = filter('note') ?: null;

        $componente->save();

        flash()->info(tr('Salvataggio completato!'));
        break;

    case 'sostituisci':
        $id_componente = filter('id_componente');
        $componente = Componente::find($id_componente);

        // Creazione copia del componenten
        $copia = $componente->replicate();
        $copia->data_registrazione = new Carbon();
        $copia->data_installazione = new Carbon();
        $copia->data_sostituzione = null;
        $copia->data_rimozione = null;
        // Rimozione riferimento intervento di installazione
        $copia->id_intervento = null;
        $copia->save();

        // Sostituzione del componente indicato
        $componente->data_sostituzione = new Carbon();
        $componente->id_sostituzione = $copia->id;
        $componente->save();

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'rimuovi':
        $id_componente = filter('id_componente');
        $componente = Componente::find($id_componente);

        $componente->data_rimozione = new Carbon();
        $componente->save();

        flash()->info(tr('Componente rimosso!'));
        break;
}
