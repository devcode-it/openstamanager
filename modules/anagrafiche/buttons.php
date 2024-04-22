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
    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-magic"></i>
        '.tr('Crea').'... <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">';

    // Aggiunta utente per i tecnici
    if (in_array($id_tecnico, $tipi_anagrafica)) {
        echo '
        <li><a data-toggle="modal" data-title="'.tr('Aggiungi utente').'" data-href="modules/utenti/user.php?id_module='.(new Module())->getByField('title', 'Utenti e permessi', Models\Locale::getPredefined()->id)->id_record.'&id_record='.(new Group())->getByField('title', 'Tecnici', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-user"></i>'.tr('Nuovo utente').'
        </a></li>';
    }

    if (in_array($id_cliente, $tipi_anagrafica)) {
        echo '
        <li><a data-toggle="modal" data-title="'.tr('Aggiungi attività').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Attività', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-wrench"></i>'.tr('Nuova attività').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi preventivo').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Preventivi', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-text"></i>'.tr('Nuovo preventivo').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi contratto').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Contratti', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-text-o"></i>'.tr('Nuovo contratto').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi ordine cliente').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Ordini cliente', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-o"></i>'.tr('Nuovo ordine cliente').'
        </a></li>

		<li><a data-toggle="modal" data-title="'.tr('Aggiungi ddt uscita').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Ddt di vendita', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-truck"></i>'.tr('Nuovo ddt in uscita').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi fattura di vendita').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Fatture di vendita', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file"></i>'.tr('Nuova fattura di vendita').'
        </a></li>

        <li><a data-toggle="modal" data-title="'.tr('Aggiungi registrazione contabile').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Prima nota', Models\Locale::getPredefined()->id).'&id_anagrafica='.$record['idanagrafica'].'"><i class="fa fa-euro"></i>'.tr('Nuova registrazione contabile (cliente)').'
        </a></li>';
    }

    if (in_array($id_fornitore, $tipi_anagrafica)) {
        echo '<li><a data-toggle="modal" data-title="'.tr('Aggiungi ordine fornitore').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Ordini fornitore', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file-o fa-flip-horizontal"></i>'.tr('Nuovo ordine fornitore').'
    </a></li>

    <li><a data-toggle="modal" data-title="'.tr('Aggiungi ddt entrata').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Ddt di acquisto', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-truck fa-flip-horizontal"></i>'.tr('Nuovo ddt in entrata').'
    </a></li>

    <li><a data-toggle="modal" data-title="'.tr('Aggiungi fattura di acquisto').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Fatture di acquisto', Models\Locale::getPredefined()->id).'&idanagrafica='.$record['idanagrafica'].'"><i class="fa fa-file fa-flip-horizontal"></i>'.tr('Nuova fattura di acquisto').'
    </a></li>

    <li><a data-toggle="modal" data-title="'.tr('Aggiungi registrazione contabile').'" data-href="add.php?id_module='.(new Module())->getByField('title', 'Prima nota', Models\Locale::getPredefined()->id).'&id_anagrafica='.$record['idanagrafica'].'"><i class="fa fa-euro"></i>'.tr('Nuova registrazione contabile (fornitore)').'
    </a></li>';
    }

    echo '
    </ul>
</div>';
}

if (in_array($id_agente, $tipi_anagrafica)) {
    // Aggiunta liquidazione provvigioni per agente
    echo '
    <button type="button" class="btn btn-primary" data-title="'.tr('Liquida Provvigioni').'" data-href="'.base_path().'/modules/anagrafiche/liquida_provvigioni.php?nome_stampa=Provvigioni&id_record='.$id_record.'" ><i class="fa fa-print"></i> '.tr('Liquida Provvigioni').'</button>';
}
