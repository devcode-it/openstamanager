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

use Models\Group;
use Models\Module;

if (in_array($id_cliente, $tipi_anagrafica) or in_array($id_fornitore, $tipi_anagrafica)) {
    echo '
<div class="btn-group">
    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-magic"></i> 
        '.tr('Crea').'...
    </button>
    <div class="dropdown-menu dropdown-menu-right">';

    // Aggiunta utente per i tecnici
    if (in_array($id_tecnico, $tipi_anagrafica)) {
        echo '
        <a class="dropdown-item" data-title="'.tr('Aggiungi utente').'" data-widget="modal" data-href="modules/utenti/user.php?id_module='.Module::where('name', 'Utenti e permessi')->first()->id.'&id_record='.Group::where('nome', 'Tecnici')->first()->id.'&idanagrafica='.$record['idanagrafica'].'">
            <i class="fa fa-user"></i> '.tr('Nuovo utente').'
        </a>';
    }

    if (in_array($id_cliente, $tipi_anagrafica)) {
        echo '
        
        <a class="dropdown-item" data-title="'.tr('Aggiungi attività').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Interventi')->first()->id.'&idanagrafica='.$record['idanagrafica'].'">
            <i class="fa fa-wrench"></i> '.tr('Nuova attività').'
        </a>
        
        <a class="dropdown-item" data-title="'.tr('Aggiungi preventivo').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Preventivi')->first()->id.'&idanagrafica='.$record['idanagrafica'].'">
            <i class="fa fa-file-text"></i> '.tr('Nuovo preventivo').'
        </a>

        <a class="dropdown-item" data-title="'.tr('Aggiungi contratto').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Contratti')->first()->id.'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-text-o"></i> '.tr('Nuovo contratto').'</a>

        <a class="dropdown-item" data-title="'.tr('Aggiungi ordine cliente').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Ordini cliente')->first()->id.'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-o"></i> '.tr('Nuovo ordine cliente').'</a>

        <a class="dropdown-item" data-title="'.tr('Aggiungi ddt in uscita').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Ddt di vendita')->first()->id.'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-truck"></i> '.tr('Nuovo ddt in uscita').'</a>

        <a class="dropdown-item" data-title="'.tr('Aggiungi fattura di vendita').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Fatture di vendita')->first()->id.'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file"></i> '.tr('Nuova fattura di vendita').'</a>

        <a class="dropdown-item" data-title="'.tr('Aggiungi registrazione contabile').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Prima nota')->first()->id.'&id_anagrafica='.$record['idanagrafica'].'"><i class="fa fa-euro"></i> '.tr('Nuova registrazione contabile (cliente)').'</a>';
    }

    if (in_array($id_fornitore, $tipi_anagrafica)) {
        echo '
            
        <a class="dropdown-item" data-title="'.tr('Aggiungi ordine fornitore').'" data-widget="modal" data-data-href="add.php?id_module='.Module::where('name', 'Ordini fornitore')->first()->id.'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-o fa-flip-horizontal"></i> '.tr('Nuovo ordine fornitore').'</a>

        <a class="dropdown-item" data-title="'.tr('Aggiungi ddt in entrata').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Ddt di acquisto')->first()->id.'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-truck fa-flip-horizontal"></i> '.tr('Nuovo ddt in entrata').'</a>

        <a class="dropdown-item" data-title="'.tr('Aggiungi fattura di acquisto').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Fatture di acquisto')->first()->id.'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file fa-flip-horizontal"></i> '.tr('Nuova fattura di acquisto').'</a>

        <a class="dropdown-item" data-title="'.tr('Aggiungi registrazione contabile').'" data-widget="modal" data-href="add.php?id_module='.Module::where('name', 'Prima nota')->first()->id.'&id_anagrafica='.$record['idanagrafica'].'"><i class="fa fa-euro"></i> '.tr('Nuova registrazione contabile (fornitore)').'</a>';
    }

    echo ' 
    </div>
</div>';
}

if (in_array($id_agente, $tipi_anagrafica)) {
    // Aggiunta liquidazione provvigioni per agente
    echo '
        <button type="button" class="btn btn-primary" data-title="'.tr('Liquida Provvigioni').'" data-data-href="'.base_path().'/modules/anagrafiche/liquida_provvigioni.php?nome_stampa=Provvigioni&id_record='.$id_record.'" >
            <i class="fa fa-print"></i> '.tr('Liquida Provvigioni').'
        </button>';
}
