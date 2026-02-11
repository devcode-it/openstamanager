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

use Models\Module;
use Models\Plugin;

include_once __DIR__.'/../../core.php';

// Lettura dei parametri di interesse
$id_anagrafica = filter('idanagrafica');
$id_sede = filter('idsede');
$richiesta = filter('richiesta');
$descrizione = filter('descrizione');
$id_tipo = filter('id_tipo');

$origine_dashboard = get('ref') == 'dashboard' ? true : false;
$id_modulo_anagrafiche = Module::where('name', 'Anagrafiche')->first()->id;
$id_plugin_sedi = Plugin::where('name', 'Sedi aggiuntive')->first()->id;

// Calcolo dell'orario di inizio e di fine sulla base delle informazioni fornite
$orario_inizio = filter('orario_inizio');
$orario_fine = filter('orario_fine');
if (null == $orario_inizio || '00:00:00' == $orario_inizio) {
    $orario_inizio = date('H').':00:00';
    $orario_fine = date('H').':00:00';
}

// Un utente del gruppo Tecnici può aprire attività solo a proprio nome
$id_tecnico = filter('id_tecnico') ?: filter('idtecnico');
$id_cliente = null;

if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
    $id_tecnico = $user['idanagrafica'];
} elseif ($user['gruppo'] == 'Clienti' && !empty($user['idanagrafica'])) {
    $id_cliente = $user['idanagrafica'];
}

// Gestione dell'impostazione dei Contratti
$id_intervento = filter('id_intervento');
$id_contratto = filter('idcontratto');
$id_promemoria_contratto = filter('idcontratto_riga');
$id_ordine = null;

if (empty($id_anagrafica)) {
    $id_anagrafica = Modules\Interventi\Intervento::where('id', $id_intervento)->first()->idanagrafica;
}

$anagrafica = $dbo->fetchOne('SELECT idtipointervento_default, idzona FROM an_anagrafiche WHERE idanagrafica='.prepare($id_anagrafica));
$id_tipo = $anagrafica['idtipointervento_default'];
$id_zona = $anagrafica['idzona'];

// Trasformazione di un Promemoria dei Contratti in Intervento
if (!empty($id_contratto) && !empty($id_promemoria_contratto)) {
    $contratto = $dbo->fetchOne('SELECT *, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = co_contratti.idanagrafica) AS idzona FROM co_contratti WHERE id = '.prepare($id_contratto));
    $id_anagrafica = $contratto['idanagrafica'];
    $id_zona = $contratto['idzona'];

    // Informazioni del Promemoria
    $promemoria = $dbo->fetchOne('SELECT *, (SELECT `tempo_standard` FROM `in_tipiintervento` WHERE `id` = `co_promemoria`.`idtipointervento`) AS tempo_standard FROM `co_promemoria` WHERE `idcontratto`='.prepare($id_contratto).' AND `co_promemoria`.`id` = '.prepare($id_promemoria_contratto));
    $id_tipo = $promemoria['idtipointervento'];
    $data = filter('data') ?? $promemoria['data_richiesta'];
    $richiesta = $promemoria['richiesta'];
    $descrizione = $promemoria['descrizione'];
    $id_sede = $promemoria['idsede'];
    $impianti_collegati = $promemoria['idimpianti'];
    $tecnici_assegnati = $promemoria['idtecnici'];
    $data_scadenza = $promemoria['data_scadenza'];

    // Generazione dell'orario di fine sulla base del tempo standard definito dal Promemoria
    if (!empty($promemoria['tempo_standard'])) {
        $orario_fine = date('H:i:s', strtotime($orario_inizio) + ((60 * 60) * $promemoria['tempo_standard']));
    }

    // Caricamento degli impianti a Contratto se non definiti in Promemoria
    if (empty($impianti_collegati)) {
        $rs = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_contratti WHERE idcontratto = '.prepare($id_contratto));
        $impianti_collegati = implode(',', array_column($rs, 'idimpianto'));
    }
}

// Gestione dell'aggiunta di una sessione a un Intervento senza sessioni (Promemoria intervento) da Dashboard
elseif (!empty($id_intervento)) {
    $intervento = $dbo->fetchOne('SELECT *, (SELECT `idcontratto` FROM `co_promemoria` WHERE `idintervento` = `in_interventi`.`id` LIMIT 0,1) AS idcontratto, `in_interventi`.`id_preventivo` as idpreventivo, (SELECT `tempo_standard` FROM `in_tipiintervento` WHERE `id` = `in_interventi`.`idtipointervento`) AS tempo_standard FROM `in_interventi` WHERE `id` = '.prepare($id_intervento));

    $id_tipo = $intervento['idtipointervento'];
    $data = filter('data') ?? $intervento['data_richiesta'];
    $data_richiesta = $intervento['data_richiesta'];
    $data_scadenza = $intervento['data_scadenza'];
    $richiesta = $intervento['richiesta'];
    $descrizione = $intervento['descrizione'];
    $id_sede = $intervento['idsede_destinazione'];
    $id_anagrafica = $intervento['idanagrafica'];
    $id_cliente_finale = $intervento['idclientefinale'];
    $id_contratto = $intervento['idcontratto'];
    $id_preventivo = $intervento['idpreventivo'];
    $id_zona = $intervento['idzona'] ?: $id_zona;

    // Generazione dell'orario di fine sulla base del tempo standard definito dall'Intervento
    if (!empty($intervento['tempo_standard'])) {
        $orario_fine = date('H:i:s', strtotime($orario_inizio) + ((60 * 60) * $intervento['tempo_standard']));
    }

    $rs = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_interventi WHERE idintervento = '.prepare($id_intervento));
    $impianti_collegati = implode(',', array_column($rs, 'idimpianto'));

    $rs = $dbo->fetchArray('SELECT id_tecnico FROM in_interventi_tecnici_assegnati WHERE id_intervento = '.prepare($id_intervento));
    $tecnici_assegnati = implode(',', array_column($rs, 'id_tecnico'));
}

// Selezione dei tecnici predefiniti per gli impianti selezionati
if (!empty($impianti_collegati)) {
    $tecnici_impianti = $dbo->fetchArray('SELECT idtecnico FROM my_impianti WHERE id IN ('.prepare($impianti_collegati).')');
    $id_tecnico = array_unique(array_column($tecnici_impianti, 'idtecnico'));
}

// Impostazione della data se mancante
$data = (!empty(filter('data')) ? filter('data') : date('Y-m-d'));

// Impostazione della data di fine da Dashboard
$data_fine = (!empty(filter('data')) ? filter('data') : $data);

$inizio_sessione = $data.' '.$orario_inizio;
$fine_sessione = $data_fine.' '.$orario_fine;

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="ref" value="'.get('ref').'">
	<input type="hidden" name="backto" value="record-edit">

    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="0">
    <input type="hidden" name="idzona" id="idzona_hidden" value="'.$id_zona.'">';

if (!empty($id_promemoria_contratto)) {
    echo '<input type="hidden" name="idcontratto_riga" value="'.$id_promemoria_contratto.'">';
}

if (!empty($id_intervento)) {
    echo '<input type="hidden" name="id_intervento" value="'.$id_intervento.'">';
}

echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Cliente').'", "name": "idanagrafica", "required": 1, "value": "'.(!$id_cliente ? $id_anagrafica : $id_cliente).'", "ajax-source": "clienti", "icon-after": "add|'.$id_modulo_anagrafiche.'|tipoanagrafica=Cliente&readonly_tipo=1", "readonly": "'.((empty($id_anagrafica) && empty($id_cliente)) ? 0 : 1).'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Sede destinazione').'", "name": "idsede_destinazione", "value": "'.$id_sede.'", "ajax-source": "sedi", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica]).', "icon-after": "add|'.$id_modulo_anagrafiche.'|id_plugin='.$id_plugin_sedi.'&id_parent='.$id_anagrafica.'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Per conto di').'", "name": "idclientefinale", "value": "'.$id_cliente_finale.'", "ajax-source": "clienti" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Contratto').'", "name": "idcontratto", "value": "'.$id_contratto.'", "ajax-source": "contratti", "readonly": "'.(empty($id_contratto) ? 0 : 1).'", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica]).', "icon-after": "add|'.Module::where('name', 'Contratti')->first()->id.'|pianificabile=1&idanagrafica='.$id_anagrafica.'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Impianto').'", "multiple": 1, "name": "idimpianti[]", "value": "'.$impianti_collegati.'", "ajax-source": "impianti-cliente", "select-options": {"idanagrafica": '.($id_anagrafica ?: '""').', "idsede_destinazione": '.($id_sede ?: '0').', "idcontratto": '.($id_contratto ?: '""').'}, "icon-after": "add|'.Module::where('name', 'Impianti')->first()->id.'|id_anagrafica='.$id_anagrafica.'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Componenti').'", "multiple": 1, "name": "componenti[]", "placeholder": "'.tr('Seleziona prima un impianto').'", "ajax-source": "componenti" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Preventivo').'", "name": "idpreventivo", "value": "'.$id_preventivo.'", "ajax-source": "preventivi", "readonly": "'.(empty($id_preventivo) ? 0 : 1).'", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica]).' ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Ordine').'", "name": "idordine", "ajax-source": "ordini-cliente", "value": "'.$id_ordine.'", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica]).' ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module, 'is_sezionale' => 1]).', "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "timestamp", "label": "'.tr('Data/ora richiesta').'", "name": "data_richiesta", "required": 1, "value": "'.($data_richiesta ?: '-now-').'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Tipo').'", "name": "idtipointervento", "required": 1, "value": "'.$id_tipo.'", "ajax-source": "tipiintervento" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Stato').'", "name": "id", "required": 1, "values": "query=SELECT `in_statiintervento`.`id`, `in_statiintervento_lang`.`title` as descrizione, `colore` AS _bgcolor_ FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `deleted_at` IS NULL ORDER BY `title`", "value": "'.($origine_dashboard ? setting('Stato predefinito dell\'attività da Dashboard') : setting('Stato predefinito dell\'attività')).'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">';
echo input([
    'type' => 'ckeditor',
    'label' => tr('Richiesta'),
    'name' => 'richiesta',
    'id' => 'richiesta_add',
    'required' => 1,
    'value' => htmlentities((string) $richiesta),
    'extra' => 'style=\'max-height:80px;\'',
    'help' => tr('Descrivi brevemente la richiesta del cliente'),
]);
echo '
        </div>
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">';
echo input([
    'type' => 'ckeditor',
    'label' => tr('Descrizione'),
    'name' => 'descrizione',
    'id' => 'descrizione_add',
    'value' => htmlentities((string) $descrizione),
    'extra' => 'style=\'max-height:80px;\'',
    'help' => tr('Aggiungi dettagli e note sull\'attività da svolgere'),
]);
echo '
        </div>
    </div>

	<!-- NAVBAR TABS -->
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs bg-light nav-justified" id="intervento-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active text-bold text-center" id="dettagli-cliente-tab" data-toggle="tab" href="#tab_dettagli_cliente" role="tab" aria-controls="tab_dettagli_cliente" aria-selected="true">
                    <i class="fa fa-user text-primary"></i> '.tr('Dettagli cliente').'
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-bold text-center" id="posizione-tab" data-toggle="tab" href="#tab_posizione" role="tab" aria-controls="tab_posizione" aria-selected="false" onclick="autoload_mappa=true; caricaMappa();">
                    <i class="fa fa-map-marker text-success"></i> '.tr('Posizione').'
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-bold text-center" id="dettagli-aggiuntivi-tab" data-toggle="tab" href="#tab_dettagli_aggiuntivi" role="tab" aria-controls="tab_dettagli_aggiuntivi" aria-selected="false">
                    <i class="fa fa-info-circle text-info"></i> '.tr('Dettagli aggiuntivi').'
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-bold text-center" id="tecnici-sessioni-tab" data-toggle="tab" href="#tab_tecnici_sessioni" role="tab" aria-controls="tab_tecnici_sessioni" aria-selected="false">
                    <i class="fa fa-users text-warning"></i> '.tr('Tecnici e sessioni').'
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-bold text-center" id="ricorrenza-tab" data-toggle="tab" href="#tab_ricorrenza" role="tab" aria-controls="tab_ricorrenza" aria-selected="false">
                    <i class="fa fa-repeat text-danger"></i> '.tr('Ricorrenza').'
                </a>
            </li>
        </ul>

        <div class="tab-content p-0 border-left border-right border-bottom" id="intervento-tabs-content" style="height: 380px; overflow-y: auto; background-color: #fff;">
                <!-- TAB DETTAGLI CLIENTE -->
                <div class="tab-pane fade show active" id="tab_dettagli_cliente" role="tabpanel" aria-labelledby="dettagli-cliente-tab">
                    <div id="dettagli_cliente" class="p-4">
                        <div class="alert alert-light text-center py-4">
                            <i class="fa fa-user fa-2x text-muted mb-2"></i>
                            <h5 class="mb-2"><strong>'.tr('Cliente non selezionato').'</strong></h5>
                            <p class="text-muted mb-0">'.tr('Seleziona un cliente per visualizzare le informazioni').'</p>
                        </div>
                    </div>
                </div>

                <!-- TAB POSIZIONE -->
                <div class="tab-pane fade" id="tab_posizione" role="tabpanel" aria-labelledby="posizione-tab">
                    <div class="p-4">
                        <div id="map-add" style="height: 300px; width: 100%; display: none; align-items: center; justify-content: center; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.12);"></div>
                        <div id="no-client-message" class="alert alert-light text-center py-4 hide">
                            <i class="fa fa-map-marker fa-2x text-muted mb-2"></i>
                            <h5 class="mb-2"><strong>'.tr('Cliente non selezionato').'</strong></h5>
                            <p class="text-muted mb-0">'.tr('Seleziona un cliente per visualizzare la posizione geografica').'</p>
                        </div>
                        <div id="map-warning" class="alert alert-info text-center py-4 hide">
                            <i class="fa fa-info-circle fa-2x mb-2"></i>
                            <h5 class="mb-2"><strong>'.tr('Posizione non definita').'</strong></h5>
                            <p class="mb-0">'.tr('La posizione geografica non è stata definita per questo cliente').'</p>
                        </div>
                    </div>
                </div>

                <!-- TAB DETTAGLI AGGIUNTIVI -->
                <div class="tab-pane fade" id="tab_dettagli_aggiuntivi" role="tabpanel" aria-labelledby="dettagli-aggiuntivi-tab">
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-4">
                                {[ "type": "timestamp", "label": "'.tr('Data/ora scadenza').'", "name": "data_scadenza", "required": 0, "value": "'.$data_scadenza.'" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "select", "label": "'.tr('Referente').'", "name": "idreferente", "ajax-source": "referenti", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica, 'idclientefinale' => $id_cliente_finale]).', "icon-after": "add|'.Module::where('name', 'Anagrafiche')->first()->id.'|id_plugin='.Plugin::where('name', 'Referenti')->first()->id.'&id_parent='.$id_anagrafica.'" ]}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB TECNICI E SESSIONI -->
                <div class="tab-pane fade" id="tab_tecnici_sessioni" role="tabpanel" aria-labelledby="tecnici-sessioni-tab">
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-12">
                                {[ "type": "select", "label": "'.tr('Tecnici assegnati').'", "multiple": "1", "name": "tecnici_assegnati[]", "ajax-source": "tecnici", "value": "'.$tecnici_assegnati.'", "icon-after": "add|'.$id_modulo_anagrafiche.'|tipoanagrafica=Tecnico&readonly_tipo=1", "readonly": '.intval($id_intervento).'  ]}
                                <div class="row">
                                    <div class="col-md-12 mt-3 mb-5">
                                        <div class="btn-group" >
                                            <button type="button" class="btn btn-sm btn-primary '.(intval($id_intervento) ? 'disabled' : '').'" onclick="assegnaTuttiTecnici()">
                                                <i class="fa fa-users"></i> '.tr('Tutti').'
                                            </button>

                                            <button type="button" class="btn btn-sm btn-danger '.(intval($id_intervento) ? 'disabled' : '').'" onclick="deassegnaTuttiTecnici()">
                                            <i class="fa fa-times"></i> '.tr('Nessuno').'
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="text-primary border-bottom pb-2 mb-4"><i class="fa fa-clock-o"></i> '.tr('Sessioni di lavoro').'</h5>
                        <div class="row">
                            <div class="col-md-4">
                                {[ "type": "select", "label": "'.tr('Tipo attività').'", "name": "idtiposessione", "value": "'.$id_tipo.'", "ajax-source": "tipiintervento", "help": "'.tr('Seleziona il tipo di attività per calcolare automaticamente la durata prevista').'." ]}
                            </div>

                            <div class="col-md-2">
                                {[ "type": "timestamp", "label": "'.tr('Inizio attività').'", "name": "orario_inizio", "required": '.($origine_dashboard ? 1 : 0).', "value": "'.$inizio_sessione.'" ]}
                            </div>

                            <div class="col-md-2">
                                {[ "type": "timestamp", "label": "'.tr('Fine attività').'", "name": "orario_fine", "required": '.($origine_dashboard ? 1 : 0).', "value": "'.$fine_sessione.'" ]}
                            </div>

                            <div class="col-md-4">
                                {[ "type": "select", "label": "'.tr('Tecnico').'", "name": "idtecnico", "required": '.($origine_dashboard ? 1 : 0).', "ajax-source": "tecnici", "value": "'.$id_tecnico.'", "icon-after": "add|'.$id_modulo_anagrafiche.'|tipoanagrafica=Tecnico&readonly_tipo=1||'.(empty($id_tecnico) ? '' : 'disabled').'" ]}
                            </div>
                        </div>

                        <div id="sessioni-aggiuntive"></div>

                        <div class="row mt-3">
                            <div class="col-md-8">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> '.tr('Al clic su "Aggiungi sessione", verrà creata una nuova sessione che inizierà automaticamente alla fine della sessione precedente, utilizzando la durata prevista dal tipo di attività selezionato.').'
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-success btn-block" onclick="aggiungiNuovaSessione()">
                                    <i class="fa fa-plus"></i> '.tr('Aggiungi sessione').'
                                </button>
                            </div>
                        </div>

                        <div id="info-conflitti-add" class="mt-4"></div>
                    </div>
                </div>

                <!-- TAB RICORRENZA -->
                <div class="tab-pane fade" id="tab_ricorrenza" role="tabpanel" aria-labelledby="ricorrenza-tab">
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-4">
                                {[ "type": "checkbox", "label": "'.tr('Attività ricorrente').'", "name": "ricorsiva_add", "value": "" ]}
                            </div>
                        </div>

                        <div class="ricorrenza-config" style="display: none;">
                            <input type="hidden" name="data_inizio_ricorrenza" id="data_inizio_ricorrenza_hidden" value="">

                            <div class="row">
                                <div class="col-md-3">
                                    {[ "type": "number", "label": "'.tr('Periodicità').'", "name": "periodicita", "decimals": "0", "icon-after": "choice|period|months", "value": "1" ]}
                                </div>
                                <div class="col-md-3">
                                    {[ "type": "select", "label": "'.tr('Metodo fine ricorrenza').'", "name": "metodo_ricorrenza", "values": "list=\"data\":\"Data fine\",\"numero\":\"Numero ricorrenze\"" ]}
                                </div>
                                <div class="col-md-3 metodo-data">
                                    {[ "type": "date", "label": "'.tr('Data fine').'", "name": "data_fine_ricorrenza" ]}
                                </div>
                                <div class="col-md-3 metodo-numero">
                                    {[ "type": "number", "label": "'.tr('Numero ricorrenze').'", "name": "numero_ricorrenze", "decimals": "0" ]}
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Stato ricorrenze').'", "name": "idstatoricorrenze", "values": "query=SELECT `in_statiintervento`.`id`,`in_statiintervento_lang`.`title` as descrizione, `colore` AS _bgcolor_ FROM `in_statiintervento`  LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `deleted_at` IS NULL AND `is_bloccato`=0 ORDER BY `title`" ]}
                                </div>
                                <div class="col-md-6">
                                    {[ "type": "checkbox", "label": "'.tr('Riporta sessioni di lavoro').'", "name": "riporta_sessioni_add", "value": "" ]}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="modal-footer">
		<div class="col-md-12 text-right">
			<button type="button" class="btn btn-success" onclick="salva(this)">
                <i class="fa fa-check"></i> '.tr('Aggiungi attività').'
            </button>
		</div>
	</div>
</form>';

if (!empty($id_intervento)) {
    echo '
<script type="text/javascript">
    $(document).ready(function() {
       input("idsede_destinazione").disable();
       input("idpreventivo").disable();
       input("idcontratto").disable();
       input("idordine").disable();
       input("idreferente").disable();
       input("componenti").disable();
       input("idanagrafica").disable();
       input("idclientefinale").disable();
       input("idtipointervento").disable();
       input("idstatointervento").disable();
       input("data_richiesta").disable();
    });
</script>';
}

// Disabilito i campi che non devono essere modificati per poter collegare l'Intervento al Promemoria del Contratto
if (!empty($id_contratto) && !empty($id_promemoria_contratto)) {
    echo '
<script type="text/javascript">
    $(document).ready(function() {
       input("idanagrafica").disable();
       input("idclientefinale").disable();
       input("idtipointervento").disable();
    });
</script>';
}

echo '
<script type="text/javascript">
    var anagrafica = input("idanagrafica");
    var sede = input("idsede_destinazione");
    var contratto = input("idcontratto");
    var preventivo = input("idpreventivo");
    var ordine = input("idordine");
    var referente = input("idreferente");
    var cliente_finale = input("idclientefinale");
    var autoload_mappa = false;

	$(document).ready(function() {
        if(!anagrafica.get()){
           sede.disable();
           preventivo.disable();
           contratto.disable();
           ordine.disable();
           referente.disable();
           input("componenti").disable();
        } else{
           let value = anagrafica.get();
           updateSelectOption("idanagrafica", value);
           session_set("superselect,idanagrafica",value, 0);

            // Carico nel card i dettagli del cliente
            $("#dettagli_cliente").html(\'<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>'.tr('Caricamento informazioni cliente...').'</p></div>\');
            $.get("'.base_path_osm().'/ajax_complete.php?module=Interventi&op=dettagli&id_anagrafica=" + value, function(data){
                $("#dettagli_cliente").html(data);
            });
        }

        let data = anagrafica.getData();
        if (data && contratto.get() === "") {
            input("idcontratto").getElement().selectSetNew(data.id_contratto, data.descrizione_contratto);
        }

		// Quando modifico orario inizio, allineo anche l\'orario fine
		let orario_inizio = input("orario_inizio").getElement();
		let orario_fine = input("orario_fine").getElement();
        orario_inizio.on("dp.change", function (e) {
            if(orario_fine.data("DateTimePicker").date() < e.date){
                orario_fine.data("DateTimePicker").date(e.date);
            }
        });

        orario_fine.on("dp.change", function (e) {
            if(orario_inizio.data("DateTimePicker").date() > e.date){
                orario_inizio.data("DateTimePicker").date(e.date);
            }
        });

        // Refresh modulo dopo la chiusura di una pianificazione attività derivante dalle attività
        // da pianificare, altrimenti il promemoria non si vede più nella lista a destra
		// TODO: da gestire via ajax
        if($("input[name=idcontratto_riga]").val()) {
            $("#modals > div button.close").on("click", function() {
                location.reload();
            });
        }

        // Ricorrenza
        $(".ricorrenza").addClass("hidden");

        // Miglioramenti grafici per le tab
        $(".nav-tabs .nav-link").hover(
            function() {
                if (!$(this).hasClass("active")) {
                    $(this).addClass("bg-white");
                }
            },
            function() {
                if (!$(this).hasClass("active")) {
                    $(this).removeClass("bg-white");
                }
            }
        );

        // Evidenzia la tab attiva
        $(".nav-tabs .nav-link").on("shown.bs.tab", function() {
            $(".nav-tabs .nav-link").removeClass("active-tab bg-white");
            $(this).addClass("active-tab");
        });

        caricaMappa();
    });

	input("idtecnico").change(function() {
	    calcolaConflittiTecnici();
	});

    // Gestione della modifica dell\'anagrafica
	anagrafica.change(function() {
        let value = $(this).val();
        updateSelectOption("idanagrafica", value);
        session_set("superselect,idanagrafica",value, 0);

        let selected = !$(this).val();
        let placeholder = selected ? "'.tr('Seleziona prima un cliente').'" : "'.tr("Seleziona un'opzione").'";

        let selected_sede = !$(this).val() || $(this).prop("disabled") ? 1 : 0;
        sede.setDisabled(selected_sede)
            .getElement().selectReset(placeholder);

        preventivo.setDisabled(selected)
            .getElement().selectReset(placeholder);

        contratto.setDisabled(selected)
            .getElement().selectReset(placeholder);

        ordine.setDisabled(selected)
            .getElement().selectReset(placeholder);

        referente.setDisabled(selected)
            .getElement().selectReset(placeholder);


        let data = anagrafica.getData();
		if (data) {
		    $("#idzona_hidden").val(data.idzona ? data.idzona : "");
			// session_set("superselect,idzona", $(this).selectData().idzona, 0);

            // Impostazione del tipo intervento da anagrafica
            input("idtipointervento").getElement()
                .selectSetNew(data.idtipointervento, data.idtipointervento_descrizione);

            // Impostazione del contratto predefinito da anagrafica
            if(data.id_contratto) {
                input("idcontratto").getElement()
                    .selectSetNew(data.id_contratto, data.descrizione_contratto);
            }

            caricaMappa(data.lat, data.lng);
		}

        if (data !== undefined) {
            // Carico nel card i dettagli del cliente
            $("#dettagli_cliente").html(\'<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>'.tr('Caricamento informazioni cliente...').'</p></div>\');
            $.get("'.base_path_osm().'/ajax_complete.php?module=Interventi&op=dettagli&id_anagrafica=" + value, function(data){
                $("#dettagli_cliente").html(data);
            });
        } else {
            $("#dettagli_cliente").html(\'<div class="alert alert-light text-center py-4"><i class="fa fa-user fa-2x text-muted mb-2"></i><h5 class="mb-2"><strong>'.tr('Cliente non selezionato').'</strong></h5><p class="text-muted mb-0">'.tr('Seleziona un cliente per visualizzare le informazioni').'</p></div>\');
            caricaMappa();
        }

        plus_sede = $(".modal #idsede_destinazione").parent().find(".btn");

        if (plus_sede.length == 1) {
            plus_sede.attr("onclick", plus_sede.attr("onclick").replace(/id_parent=null/, "id_parent=").replace(/id_parent=[0-9]*/, "id_parent=" + value));
        }

        plus_impianto = $(".modal #idimpianti").parent().find(".btn");

        if (plus_impianto.length == 1) {
            plus_impianto.attr("onclick", plus_impianto.attr("onclick").replace(/id_anagrafica=null/, "id_anagrafica=").replace(/id_anagrafica=[0-9]*/, "id_anagrafica=" + value));
        }

        plus_contratto = $(".modal #idcontratto").parent().find(".btn");

        if (plus_contratto.length == 1) {
            plus_contratto.attr("onclick", plus_contratto.attr("onclick").replace(/idanagrafica=null/, "idanagrafica=").replace(/idanagrafica=[0-9]*/, "idanagrafica=" + value));
        }

        plus_referente = $(".modal #idreferente").parent().find(".btn");

        if (plus_referente.length == 1) {
            plus_referente.attr("onclick", plus_referente.attr("onclick").replace(/id_parent=null/, "id_parent=").replace(/id_parent=[0-9]*/, "id_parent=" + value));
        }
	});

    //gestione del cliente finale
    cliente_finale.change(function() {
        updateSelectOption("idclientefinale", $(this).val());
        session_set("superselect,idclientefinale", $(this).val(), 0);

        referente.getElement()
            .selectReset("'.tr("Seleziona un'opzione").'");
    });

    // Gestione della modifica della sede selezionato
	sede.change(function() {
        updateSelectOption("idsede_destinazione", $(this).val());
		session_set("superselect,idsede_destinazione", $(this).val(), 0);


        let data = sede.getData();
		if (data) {
		    $("#idzona_hidden").val(data.idzona ? data.idzona : "");
			// session_set("superselect,idzona", $(this).selectData().idzona, 0);

            caricaMappa(data.lat, data.lng);
		} else {
		    caricaMappa();
		}
	});

    // Gestione della modifica dell\'ordine selezionato
	ordine.change(function() {
		if (ordine.get()) {
            contratto.getElement().selectReset();
            preventivo.getElement().selectReset();
        }
	});

    // Gestione della modifica del preventivo selezionato
	preventivo.change(function() {
		if (preventivo.get()){
            contratto.getElement().selectReset();
            ordine.getElement().selectReset();

            input("idtipointervento").getElement()
                .selectSetNew($(this).selectData().idtipointervento, $(this).selectData().idtipointervento_descrizione);
        }
	});

    // Gestione della modifica del contratto selezionato
	contratto.change(function() {
		if (contratto.get()){
            preventivo.getElement().selectReset();
            ordine.getElement().selectReset();

            $("input[name=idcontratto_riga]").val("");

            if ($(this).selectData().idtipointervento) {
                input("idtipointervento").getElement()
                    .selectSetNew($(this).selectData().idtipointervento, $(this).selectData().idtipointervento_descrizione);
            }

            updateSelectOption("idcontratto", contratto.get());
            session_set("superselect,idcontratto",contratto.get(), 0);
        }
	});

    // Gestione delle modifiche agli impianti selezionati
	input("idimpianti").change(function() {
        updateSelectOption("matricola", $(this).val());
		session_set("superselect,matricola", $(this).val(), 0);

        input("componenti").setDisabled(!$(this).val())
            .getElement().selectReset();

        // Selezione anagrafica in automatico in base impianto
        if ($(this).val()[0]) {
            input("idanagrafica").disable();
            input("idsede_destinazione").disable();

            let data = $(this).selectData()[0];
            input("idanagrafica").getElement()
            .selectSetNew(data.idanagrafica, data.ragione_sociale);
            input("idsede_destinazione").getElement()
            .selectSetNew(data.idsede, data.nomesede);
        } else {
            input("idanagrafica").enable();
            input("idsede_destinazione").enable();
        }
	});

    // Automatismo del tempo standard
    input("idtiposessione").change(function() {
        let data = $("#idtiposessione").selectData();
        if (data && data.tempo_standard > 0) {
            let orario_inizio = input("orario_inizio").get();
            if (orario_inizio) {
                let tempo_standard = data.tempo_standard * 60;
                let nuovo_orario_fine = moment(orario_inizio, "DD/MM/YYYY HH:mm").add(tempo_standard, "m").format("DD/MM/YYYY HH:mm");
                input("orario_fine").set(nuovo_orario_fine);
            }
        }
    });

    // Automatismo per calcolare orario di fine quando cambia l\'orario di inizio
    input("orario_inizio").change(function() {
        let data = $("#idtiposessione").selectData();
        if (data && data.tempo_standard > 0) {
            let orario_inizio = input("orario_inizio").get();
            if (orario_inizio) {
                let tempo_standard = data.tempo_standard * 60;
                let nuovo_orario_fine = moment(orario_inizio, "DD/MM/YYYY HH:mm").add(tempo_standard, "m").format("DD/MM/YYYY HH:mm");
                input("orario_fine").set(nuovo_orario_fine);
            }
        }
    });';

if (!$origine_dashboard) {
    echo '
	input("idtecnico").change(function() {
	    var value = $(this).val() > 0 ? true : false;
	    input("orario_inizio").setRequired(value);
	    input("orario_fine").setRequired(value);
	    input("data").setRequired(value);
	});';
}

echo '
	var ref = "'.get('ref').'";

	async function salva(button) {
	    // Validazione ricorrenza prima del salvataggio
	    if ($("#ricorsiva_add").is(":checked")) {
	        calculateDataInizioRicorrenza();
	        var dataInizio = $("#data_inizio_ricorrenza_hidden").val();
	        if (!dataInizio) {
	            swal("Errore", "Impossibile calcolare la data di inizio ricorrenza", "error");
	            return false;
	        }

	        var periodicita = $("#periodicita").val();
	        if (!periodicita || periodicita <= 0) {
	            swal("Errore", "La periodicità deve essere un numero positivo", "error");
	            return false;
	        }

	        var metodo = $("#metodo_ricorrenza").val();
	        if (!metodo) {
	            swal("Errore", "Seleziona un metodo per terminare la ricorrenza", "error");
	            return false;
	        }

	        if (metodo === "data" && !$("#data_fine_ricorrenza").val()) {
	            swal("Errore", "La data di fine ricorrenza è obbligatoria", "error");
	            return false;
	        }

	        if (metodo === "numero" && (!$("#numero_ricorrenze").val() || $("#numero_ricorrenze").val() <= 0)) {
	            swal("Errore", "Il numero di ricorrenze deve essere maggiore di zero", "error");
	            return false;
	        }

	        if (!$("#idstatoricorrenze").val()) {
	            swal("Errore", "Seleziona uno stato per le ricorrenze", "error");
	            return false;
	        }
	    }

	    // Submit attraverso ricaricamento della pagina
	    if (!ref) {
            $("#add-form").submit();
            return;
	    }

	    // Submit dinamico tramite AJAX
        let response = await salvaForm("#add-form", {
            id_module: "'.$id_module.'", // Fix creazione da Dashboard
        }, button);

        // Se l\'aggiunta intervento proviene dalla scheda di pianificazione ordini di servizio della dashboard, la ricarico
        if (ref == "dashboard") {
            $("#modals > div").modal("hide");

            // Aggiornamento elenco interventi da pianificare
            globals.dashboard.calendar.refetchEvents();
        }

        // Se l\'aggiunta intervento proviene dai contratti, faccio il submit via ajax e ricarico la tabella dei contratti
        else if (ref == "interventi_contratti") {
            $("#modals > div").modal("hide");
            parent.window.location.reload();
            //TODO: da gestire via ajax
            //$("#elenco_interventi > tbody").load(globals.rootdir + "/modules/contratti/plugins/contratti.pianificazioneinterventi.php?op=get_interventi_pianificati&idcontratto='.$id_contratto.'");
        }
    }

    function calcolaConflittiTecnici() {
        let tecnico = input("idtecnico").get();

        if (tecnico) {
            return $("#info-conflitti-add").load("'.$module->fileurl('occupazione_tecnici.php').'", {
                "id_module": globals.id_module,
                "tecnici[]": [tecnico],
                "inizio": input("orario_inizio").get(),
                "fine": input("orario_fine").get(),
            });
        } else {
            $("#info-conflitti-add").html("");
        }
    }

    function assegnaTuttiTecnici() {
        deassegnaTuttiTecnici();

        $.getJSON(globals.rootdir + "/ajax_select.php?op=tecnici", function(response) {
            let input_tecnici = input("tecnici_assegnati").getElement();

            $.each(response.results, function(key, result) {
                input_tecnici.append(`<option value="` + result["id"] + `">` + result["descrizione"] + `</option>`);

                input_tecnici.find("option").prop("selected", true);
            });

            $("#tecnici_assegnati").trigger("change");
        });
    }

    function deassegnaTuttiTecnici() {
        input("tecnici_assegnati").getElement().selectReset();
    }

    var sessioneCounter = 1;
    function aggiungiNuovaSessione() {
        sessioneCounter++;

        // Calcola l\'orario di inizio della nuova sessione (fine dell\'ultima sessione)
        let ultimoOrarioFine = calcolaUltimoOrarioFine();

        let nuovoOrarioFine = ""; // Sarà calcolato quando si seleziona il tipo attività

        // Crea il HTML per la nuova sessione con le classi corrette
        let sessioneHtml = `
            <div class="row mt-3" id="sessione-${sessioneCounter}">
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Tipo attività').'", "name": "sessioni[${sessioneCounter}][idtipointervento]", "ajax-source": "tipiintervento", "extra": "onchange=\\"calcolaOrarioFineSessione(${sessioneCounter})\\"" ]}
                </div>
                <div class="col-md-2">
                    {[ "type": "timestamp", "label": "'.tr('Inizio attività').'", "name": "sessioni[${sessioneCounter}][orario_inizio]", "value": "", "class": "text-center", "extra": "onchange=\\"calcolaOrarioFineSessione(${sessioneCounter})\\"" ]}
                </div>
                <div class="col-md-2">
                    {[ "type": "timestamp", "label": "'.tr('Fine attività').'", "name": "sessioni[${sessioneCounter}][orario_fine]", "value": "${nuovoOrarioFine}", "class": "text-center" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Tecnico').'", "name": "sessioni[${sessioneCounter}][idtecnico]", "ajax-source": "tecnici", "icon-after": "add|'.$id_modulo_anagrafiche.'|tipoanagrafica=Tecnico&readonly_tipo=1" ]}
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-block" onclick="rimuoviSessione(${sessioneCounter})" style="margin-top: 27px;">
                        <i class="fa fa-trash"></i> '.tr('Elimina').'
                    </button>
                </div>
            </div>
        `;

        $("#sessioni-aggiuntive").append(sessioneHtml);

        // Reinizializza i componenti della nuova sessione
        init();

        // Aggiungi gli eventi specifici per il calcolo automatico e sistema lo styling
        setTimeout(function() {
            let sessioneSelector = `#sessione-${sessioneCounter}`;
            let tipoSelect = $(`${sessioneSelector} select[name="sessioni[${sessioneCounter}][idtipointervento]"]`);
            let inizioInput = $(`${sessioneSelector} input[name="sessioni[${sessioneCounter}][orario_inizio]"]`);

            // Imposta il valore dell\'orario di inizio dopo l\'inizializzazione
            if (inizioInput.length > 0) {
                inizioInput.val(ultimoOrarioFine);
                inizioInput.trigger("change");
            }

            // Collega gli eventi con selettori più specifici
            if (tipoSelect.length > 0) {
                tipoSelect.on("change", function() {
                    calcolaOrarioFineSessione(sessioneCounter);
                });
            }

            if (inizioInput.length > 0) {
                inizioInput.on("dp.change change", function() {
                    calcolaOrarioFineSessione(sessioneCounter);
                });
            }

            // Sistema lo styling dei componenti
            $(`${sessioneSelector} .select2-container`).css("width", "100%");
            $(`${sessioneSelector} .timestamp-picker`).css("text-align", "center");
        }, 1000);
    }

    function rimuoviSessione(id) {
        $("#sessione-" + id).remove();

        // Aggiorna gli orari delle sessioni rimanenti se necessario
        aggiornaOrariSessioni();
    }

    function calcolaOrarioFineSessione(sessioneId) {
        let tipoSelect = $(`select[name="sessioni[${sessioneId}][idtipointervento]"]`);
        let inizioInput = $(`input[name="sessioni[${sessioneId}][orario_inizio]"]`);
        let fineInput = $(`input[name="sessioni[${sessioneId}][orario_fine]"]`);

        let tipoId = tipoSelect.val();
        let orarioInizio = inizioInput.val();

        if (tipoId && orarioInizio) {
            // Ottieni i dati del tipo attività selezionato
            $.ajax({
                url: "'.base_path_osm().'/ajax_select.php",
                type: "GET",
                dataType: "json",
                data: {
                    op: "tipiintervento",
                    search: "",
                    id: tipoId
                },
                success: function(response) {
                if (response && response.results && response.results.length > 0) {
                    // Trova l\'elemento con l\'ID corrispondente
                    let tipoData = response.results.find(item => item.id == tipoId);

                    if (tipoData && tipoData.tempo_standard && tipoData.tempo_standard > 0) {
                        let tempoStandard = parseFloat(tipoData.tempo_standard) * 60; // Converti ore in minuti
                        let nuovoOrarioFine = moment(orarioInizio, "DD/MM/YYYY HH:mm").add(tempoStandard, "m").format("DD/MM/YYYY HH:mm");

                        fineInput.val(nuovoOrarioFine);
                        fineInput.trigger("change");
                        fineInput.trigger("dp.change");
                    }
                }
                },
                error: function(xhr, status, error) {
                    // Gestione errori silenziosa
                }
            });
        }
    }

    function calcolaUltimoOrarioFine() {
        // Inizia con l\'orario di fine della prima sessione
        let ultimoOrario = input("orario_fine").get();

        // Se non c\'è un orario di fine nella prima sessione, usa l\'orario corrente
        if (!ultimoOrario) {
            ultimoOrario = moment().format("DD/MM/YYYY HH:mm");
        }

        // Controlla tutte le sessioni aggiuntive
        $("#sessioni-aggiuntive input[name*=\'orario_fine\']").each(function() {
            let orario = $(this).val();
            if (orario && moment(orario, "DD/MM/YYYY HH:mm").isAfter(moment(ultimoOrario, "DD/MM/YYYY HH:mm"))) {
                ultimoOrario = orario;
            }
        });

        return ultimoOrario;
    }

    // Gestione ricorrenza
    $("#ricorsiva_add").on("change", function(){
        if ($(this).is(":checked")) {
            $(".ricorrenza-config").slideDown(300);
            $("#metodo_ricorrenza").attr("required", true);
            $("#idstatoricorrenze").attr("required", true);
            $("#periodicita").attr("required", true);
            calculateDataInizioRicorrenza();
        } else {
            $(".ricorrenza-config").slideUp(300);
            $("#metodo_ricorrenza").attr("required", false);
            $("#idstatoricorrenze").attr("required", false);
            $("#periodicita").attr("required", false);
            $("#data_fine_ricorrenza").attr("required", false);
            $("#numero_ricorrenze").attr("required", false);
        }
    });

    $("#metodo_ricorrenza").on("change", function(){
        if ($(this).val() === "data") {
            $(".metodo-data").fadeIn(200);
            $(".metodo-numero").fadeOut(200);
            input("data_fine_ricorrenza").enable();
            $("#data_fine_ricorrenza").attr("required", true);
            input("numero_ricorrenze").disable();
            input("numero_ricorrenze").set("");
            $("#numero_ricorrenze").attr("required", false);
        } else if ($(this).val() === "numero") {
            $(".metodo-numero").fadeIn(200);
            $(".metodo-data").fadeOut(200);
            input("numero_ricorrenze").enable();
            $("#numero_ricorrenze").attr("required", true);
            input("data_fine_ricorrenza").disable();
            input("data_fine_ricorrenza").set("");
            $("#data_fine_ricorrenza").attr("required", false);
        } else {
            $(".metodo-data, .metodo-numero").fadeOut(200);
            input("data_fine_ricorrenza").disable();
            input("numero_ricorrenze").disable();
            $("#data_fine_ricorrenza").attr("required", false);
            $("#numero_ricorrenze").attr("required", false);
        }
    });

    function calculateDataInizioRicorrenza() {
        var dataInizio = "";
        var orarioInizio = $("#orario_inizio").val();

        if (orarioInizio && orarioInizio.length >= 16) {
            dataInizio = orarioInizio;
        } else {
            var dataRichiesta = $("#data_richiesta").val();
            if (dataRichiesta) {
                dataInizio = dataRichiesta + " 09:00:00";
            } else {
                var now = new Date();
                var year = now.getFullYear();
                var month = String(now.getMonth() + 1).padStart(2, "0");
                var day = String(now.getDate()).padStart(2, "0");
                dataInizio = year + "-" + month + "-" + day + " 09:00:00";
            }
        }
        $("#data_inizio_ricorrenza_hidden").val(dataInizio);
    }

    $("#orario_inizio, #data_richiesta").on("change", function() {
        setTimeout(calculateDataInizioRicorrenza, 100);
    });

    // Inizializzazione
    if (!$("#metodo_ricorrenza").val()) {
        $(".metodo-data, .metodo-numero").hide();
    }
    calculateDataInizioRicorrenza();

    var map = null;
    function caricaMappa(lat, lng) {
        if (!autoload_mappa){
            return false;
        }

        // Controllo 1: Verificare se è stato selezionato un cliente
        var clienteSelezionato = input("idanagrafica").get();
        if (!clienteSelezionato) {
            // Nessun cliente selezionato
            $("#no-client-message").removeClass("hide");
            $("#map-warning").addClass("hide");
            $("#map-add").hide();

            // Rimuovi la mappa esistente
            var container = L.DomUtil.get("map-add");
            if (container && container._leaflet_id != null && map) {
                map.remove();
                map = null;
            }
            return false;
        } else {
            // Cliente selezionato
            $("#no-client-message").addClass("hide");
            $("#map-add").css("display", "flex");
        }

        // Controllo 2: Recuperare le coordinate in base alla sede selezionata
        if (lat && lng) {
            // Coordinate passate come parametri (da sede specifica)
            // Non fare nulla, usa quelle
        } else {
            // Recupera coordinate in base alla logica sede
            var sedeSelezionata = input("idsede_destinazione").get();

            if (sedeSelezionata && sedeSelezionata !== "0") {
                // Caso C: Sede specifica selezionata
                var sedeData = input("idsede_destinazione").getData("select-options");
                if (sedeData) {
                    lat = sedeData.lat;
                    lng = sedeData.lng;
                }
            } else {
                // Caso A/B: Nessuna sede o sede legale - usa anagrafica
                var anagraficaData = input("idanagrafica").getData("select-options");
                if (anagraficaData) {
                    lat = anagraficaData.lat;
                    lng = anagraficaData.lng;
                }
            }
        }

        // Controllo più robusto per verificare se le coordinate sono valide
        var hasValidCoordinates = lat && lng &&
                                 typeof lat !== "undefined" && typeof lng !== "undefined" &&
                                 lat !== null && lng !== null &&
                                 lat !== "" && lng !== "" &&
                                 !isNaN(parseFloat(lat)) && !isNaN(parseFloat(lng)) &&
                                 parseFloat(lat) !== 0 && parseFloat(lng) !== 0;

        if (!hasValidCoordinates) {
            $("#map-warning").removeClass("hide");
            $("#map-add").hide();
            // Rimuovi la mappa esistente se le coordinate non sono valide
            var container = L.DomUtil.get("map-add");
            if (container && container._leaflet_id != null && map) {
                map.remove();
                map = null;
            }
        } else {
            $("#map-warning").addClass("hide");
            $("#map-add").css("display", "flex");
        }

        // Renderizza la mappa solo se ci sono coordinate valide e un cliente selezionato
        if (input("idanagrafica").getData("select-options") && hasValidCoordinates) {
            var container = L.DomUtil.get("map-add");
            if(container._leaflet_id != null){
                map.eachLayer(function (layer) {
                    if(layer instanceof L.Marker) {
                        map.removeLayer(layer);
                    }
                });
            } else {
                map = L.map("map-add", {
                    gestureHandling: true
                });

                L.tileLayer("'.setting('Tile server OpenStreetMap').'", {
                    maxZoom: 17,
                    attribution: "© OpenStreetMap"
                }).addTo(map);
            }

            var icon = new L.Icon({
                iconUrl: globals.rootdir + "/assets/dist/img/marker-icon.png",
                shadowUrl:globals.rootdir + "/assets/dist/img/leaflet/marker-shadow.png",
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            var marker = L.marker([parseFloat(lat), parseFloat(lng)], {
                icon: icon
            }).addTo(map);

            map.setView([parseFloat(lat), parseFloat(lng)], 14);
        }
    }
</script>';
