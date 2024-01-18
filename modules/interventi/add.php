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

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../core.php';

// Lettura dei parametri di interesse
$id_anagrafica = filter('idanagrafica');
$id_sede = filter('idsede');
$richiesta = filter('richiesta');
$descrizione = filter('descrizione');
$id_tipo = filter('id_tipo');

$origine_dashboard = get('ref') !== null;
$module_anagrafiche = Modules::get('Anagrafiche');
$id_plugin_sedi = Plugins::get('Sedi')['id'];

// Calcolo dell'orario di inizio e di fine sulla base delle informazioni fornite
$orario_inizio = filter('orario_inizio');
$orario_fine = filter('orario_fine');
if (null == $orario_inizio || '00:00:00' == $orario_inizio) {
    $orario_inizio = date('H').':00:00';
    $orario_fine = date('H').':00:00';
}

// Un utente del gruppo Tecnici può aprire attività solo a proprio nome
$id_tecnico = filter('id_tecnico');
$id_cliente = null;

if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
    $id_tecnico = $user['idanagrafica'];
} elseif ($user['gruppo'] == 'Clienti' && !empty($user['idanagrafica'])) {
    $id_cliente = $user['idanagrafica'];
}

// Se è indicata un'anagrafica relativa, si carica il tipo di intervento di default impostato
if (!empty($id_anagrafica)) {
    $anagrafica = $dbo->fetchOne('SELECT idtipointervento_default, idzona FROM an_anagrafiche WHERE idanagrafica='.prepare($id_anagrafica));
    $id_tipo ??= $anagrafica['idtipointervento_default'];
    $id_zona = $anagrafica['idzona'];
}

// Gestione dell'impostazione dei Contratti
$id_intervento = filter('id_intervento');
$id_contratto = filter('idcontratto');
$id_promemoria_contratto = filter('idcontratto_riga');
$id_ordine = null;

// Trasformazione di un Promemoria dei Contratti in Intervento
if (!empty($id_contratto) && !empty($id_promemoria_contratto)) {
    $contratto = $dbo->fetchOne('SELECT *, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = co_contratti.idanagrafica) AS idzona FROM co_contratti WHERE id = '.prepare($id_contratto));
    $id_anagrafica = $contratto['idanagrafica'];
    $id_zona = $contratto['idzona'];

    // Informazioni del Promemoria
    $promemoria = $dbo->fetchOne('SELECT *, (SELECT tempo_standard FROM in_tipiintervento WHERE idtipointervento = co_promemoria.idtipointervento) AS tempo_standard FROM co_promemoria WHERE idcontratto='.prepare($id_contratto).' AND id = '.prepare($id_promemoria_contratto));
    $id_tipo = $promemoria['idtipointervento'];
    $data = filter('data') ?? $promemoria['data_richiesta'];
    $richiesta = $promemoria['richiesta'];
    $descrizione = $promemoria['descrizione'];
    $id_sede = $promemoria['idsede'];
    $impianti_collegati = $promemoria['idimpianti'];

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
    $intervento = $dbo->fetchOne('SELECT *, (SELECT idcontratto FROM co_promemoria WHERE idintervento = in_interventi.id LIMIT 0,1) AS idcontratto, in_interventi.id_preventivo as idpreventivo, (SELECT tempo_standard FROM in_tipiintervento WHERE idtipointervento = in_interventi.idtipointervento) AS tempo_standard FROM in_interventi WHERE id = '.prepare($id_intervento));

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
    $id_zona = $intervento['idzona'];

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
$data ??= filter('data') ?? date('Y-m-d');

// Impostazione della data di fine da Dashboard
$data_fine ??= filter('data_fine') ?? $data;

$inizio_sessione = $data.' '.$orario_inizio;
$fine_sessione = $data_fine.' '.$orario_fine;

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="ref" value="'.get('ref').'">
	<input type="hidden" name="backto" value="record-edit">
    
    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">';

if (!empty($id_promemoria_contratto)) {
    echo '<input type="hidden" name="idcontratto_riga" value="'.$id_promemoria_contratto.'">';
}

if (!empty($id_intervento)) {
    echo '<input type="hidden" name="id_intervento" value="'.$id_intervento.'">';
}

echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Cliente').'", "name": "idanagrafica", "required": 1, "value": "'.(!$id_cliente ? $id_anagrafica : $id_cliente).'", "ajax-source": "clienti", "icon-after": "add|'.$module_anagrafiche['id'].'|tipoanagrafica=Cliente&readonly_tipo=1", "readonly": "'.((empty($id_anagrafica) && empty($id_cliente)) ? 0 : 1).'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Sede destinazione').'", "name": "idsede_destinazione", "value": "'.$id_sede.'", "ajax-source": "sedi", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica]).', "icon-after": "add|'.$module_anagrafiche['id'].'|id_plugin='.$id_plugin_sedi.'&id_parent='.$id_anagrafica.'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Per conto di').'", "name": "idclientefinale", "value": "'.$id_cliente_finale.'", "ajax-source": "clienti" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Impianto').'", "multiple": 1, "name": "idimpianti[]", "value": "'.$impianti_collegati.'", "ajax-source": "impianti-cliente", "select-options": {"idanagrafica": '.($id_anagrafica ?: '""').', "idsede_destinazione": '.($id_sede ?: '""').'}, "icon-after": "add|'.Modules::get('Impianti')['id'].'|id_anagrafica='.$id_anagrafica.'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Componenti').'", "multiple": 1, "name": "componenti[]", "placeholder": "'.tr('Seleziona prima un impianto').'", "ajax-source": "componenti" ]}
        </div>

        <div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module, 'is_sezionale' => 1]).', "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}
		</div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Preventivo').'", "name": "idpreventivo", "value": "'.$id_preventivo.'", "ajax-source": "preventivi", "readonly": "'.(empty($id_preventivo) ? 0 : 1).'", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica]).' ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Contratto').'", "name": "idcontratto", "value": "'.$id_contratto.'", "ajax-source": "contratti", "readonly": "'.(empty($id_contratto) ? 0 : 1).'", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica]).', "icon-after": "add|'.Modules::get('Contratti')['id'].'|pianificabile=1&idanagrafica='.$id_anagrafica.'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Ordine').'", "name": "idordine", "ajax-source": "ordini-cliente", "value": "'.$id_ordine.'", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica]).' ]}
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
            {[ "type": "select", "label": "'.tr('Stato').'", "name": "idstatointervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL ORDER BY descrizione", "value": "'.($origine_dashboard ? setting('Stato predefinito dell\'attività da Dashboard') : setting('Stato predefinito dell\'attività')).'" ]}
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
    'value' => htmlentities($richiesta),
    'extra' => 'style=\'max-height:80px;\'',
]);
echo '
        </div>
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">';
echo input([
    'type' => 'ckeditor',
    'label' => tr('Descrizione'),
    'name' => 'descrizione',
    'id' => 'descrizione_add',
    'value' => htmlentities($descrizione),
    'extra' => 'style=\'max-height:80px;\'',
]);
echo '
        </div>
    </div>

    <!-- POSIZIONE -->
    <div class="box box-info collapsable collapsed-box">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Posizione').'</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse" onclick="autoload_mappa=true; caricaMappa();">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>

        <div class="box-body">
            <div id="map-add" style="height: 300px;width: 100%;display: flex;align-items: center;justify-content: center;"></div>
        </div>
    </div>';

$espandi_dettagli = setting('Espandi automaticamente la sezione "Dettagli aggiuntivi"');
echo '
    <!-- DATI AGGIUNTIVI -->
    <div class="box box-info collapsable '.(empty($espandi_dettagli) ? 'collapsed-box' : '').'">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Dettagli aggiuntivi').'</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-'.(empty($espandi_dettagli) ? 'plus' : 'minus').'"></i>
                </button>
            </div>
        </div>

		<div class="box-body">
			<div class="row">
                <div class="col-md-4">
                    {[ "type": "timestamp", "label": "'.tr('Data/ora scadenza').'", "name": "data_scadenza", "required": 0, "value": "'.$data_scadenza.'" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Referente').'", "name": "idreferente", "ajax-source": "referenti", "select-options": '.json_encode(['idanagrafica' => $id_anagrafica, 'idclientefinale' => $id_cliente_finale]).', "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|id_plugin='.Plugins::get('Referenti')['id'].'&id_parent='.$id_anagrafica.'" ]}
                </div>
			</div>
		</div>
	</div>';

// if (empty($id_intervento)) {
echo '
	<!-- ASSEGNAZIONE TECNICI -->
    <div class="box box-info collapsable collapsed-box">
        <div class="box-header with-border">
			<h3 class="box-title">'.tr('Assegnazione tecnici').'</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
		</div>

		<div class="box-body">
	        <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "label": "'.tr('Tecnici assegnati').'", "multiple": "1", "name": "tecnici_assegnati[]", "ajax-source": "tecnici", "value": "'.$tecnici_assegnati.'", "icon-after": "add|'.$module_anagrafiche['id'].'|tipoanagrafica=Tecnico&readonly_tipo=1", "readonly": '.intval($id_intervento).'  ]}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="btn-group" >
                                <button type="button" class="btn btn-xs btn-primary '.(intval($id_intervento) ? 'disabled' : '').'" onclick="assegnaTuttiTecnici()">
                                    '.tr('Tutti').'
                                </button>

                                <button type="button" class="btn btn-xs btn-danger '.(intval($id_intervento) ? 'disabled' : '').'" onclick="deassegnaTuttiTecnici()">
                                <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
				</div>
			</div>
        </div>
    </div>';
// }

echo '
	<!-- ORE LAVORO -->
    <div class="box box-info collapsable '.($origine_dashboard ? '' : 'collapsed-box').'">
        <div class="box-header with-border">
			<h3 class="box-title">'.tr('Sessioni di lavoro').'</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-'.($origine_dashboard ? 'minus' : 'plus').'"></i>
                </button>
            </div>
		</div>

		<div class="box-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "timestamp", "label": "'.tr('Inizio attività').'", "name": "orario_inizio", "required": '.($origine_dashboard ? 1 : 0).', "value": "'.$inizio_sessione.'" ]}
				</div>

                <div class="col-md-4">
					{[ "type": "timestamp", "label": "'.tr('Fine attività').'", "name": "orario_fine", "required": '.($origine_dashboard ? 1 : 0).', "value": "'.$fine_sessione.'" ]}
				</div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Zona').'", "name": "idzona", "values": "query=SELECT id, CONCAT_WS(\' - \', nome, descrizione) AS descrizione FROM an_zone ORDER BY nome", "placeholder": "'.tr('Nessuna zona').'", "help": "'.tr('La zona viene definita automaticamente in base al cliente selezionato').'.", "readonly": "1", "value": "'.$id_zona.'" ]}
                </div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "select", "label": "'.tr('Tecnici').'", "multiple": "1", "name": "idtecnico[]", "required": '.($origine_dashboard ? 1 : 0).', "ajax-source": "tecnici", "value": "'.$id_tecnico.'", "icon-after": "add|'.$module_anagrafiche['id'].'|tipoanagrafica=Tecnico&readonly_tipo=1||'.(empty($id_tecnico) ? '' : 'disabled').'" ]}
				</div>
			</div>

            <div id="info-conflitti-add"></div>

		</div>
	</div>

    <!-- RICORRENZA -->
    <div class="box box-info collapsable collapsed-box">
        <div class="box-header with-border">
			<h3 class="box-title">'.tr('Ricorrenza').'</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
		</div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Attività ricorrente').'", "name": "ricorsiva", "value": "" ]}
                </div>

                <div class="col-md-4 ricorrenza">
                    {[ "type": "timestamp", "label": "'.tr('Data/ora inizio').'", "name": "data_inizio_ricorrenza", "value": "'.($data_richiesta ?: '-now-').'" ]}
                </div>

                <div class="col-md-4 ricorrenza">
                    {[ "type": "number", "label": "'.tr('Periodicità').'", "name": "periodicita", "decimals": "0", "icon-after": "choice|period|months", "value": "1" ]}
                </div>
            </div>

            <div class="row ricorrenza">
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Metodo fine ricorrenza').'", "name": "metodo_ricorrenza", "values": "list=\"data\":\"Data fine\",\"numero\":\"Numero ricorrenze\"" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "timestamp", "label": "'.tr('Data/ora fine').'", "name": "data_fine_ricorrenza" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "number", "label": "'.tr('Numero ricorrenze').'", "name": "numero_ricorrenze", "decimals": "0" ]}
                </div>
            </div>

            <div class="row ricorrenza">
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Stato ricorrenze').'", "name": "idstatoricorrenze", "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL AND is_completato=0 ORDER BY descrizione" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Riporta sessioni di lavoro').'", "name": "riporta_sessioni", "value": "" ]}
                </div>
            </div>
        </div>
    </div>

	<!-- DETTAGLI CLIENTE -->
    <div class="box box-info collapsable collapsed-box">
        <div class="box-header with-border">
			<h3 class="box-title">'.tr('Dettagli cliente').'</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
		</div>

        <div class="box-body" id="dettagli_cliente">
            '.tr('Seleziona prima un cliente').'...
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" class="btn btn-primary" onclick="salva(this)">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
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
       input("idzona").disable();
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
       input("idzona").disable();
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

            // Carico nel panel i dettagli del cliente
            $.get("'.base_path().'/ajax_complete.php?module=Interventi&op=dettagli&id_anagrafica=" + value, function(data){
                $("#dettagli_cliente").html(data);
            });
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
		    input("idzona").set(data.idzona ? data.idzona : "");
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
            // Carico nel panel i dettagli del cliente
            $.get("'.base_path().'/ajax_complete.php?module=Interventi&op=dettagli&id_anagrafica=" + value, function(data){
                $("#dettagli_cliente").html(data);
            });
        } else {
            $("#dettagli_cliente").html("'.tr('Seleziona prima un cliente').'...");
        }

        plus_sede = $(".modal #idsede_destinazione").parent().find(".btn");
        plus_sede.attr("onclick", plus_sede.attr("onclick").replace(/id_parent=null/, "id_parent=").replace(/id_parent=[0-9]*/, "id_parent=" + value));

        plus_impianto = $(".modal #idimpianti").parent().find(".btn");
        plus_impianto.attr("onclick", plus_impianto.attr("onclick").replace(/id_anagrafica=null/, "id_anagrafica=").replace(/id_anagrafica=[0-9]*/, "id_anagrafica=" + value));

        plus_contratto = $(".modal #idcontratto").parent().find(".btn");
        plus_contratto.attr("onclick", plus_contratto.attr("onclick").replace(/idanagrafica=null/, "idanagrafica=").replace(/idanagrafica=[0-9]*/, "idanagrafica=" + value));

        plus_referente = $(".modal #idreferente").parent().find(".btn");
        plus_referente.attr("onclick", plus_referente.attr("onclick").replace(/id_parent=null/, "id_parent=").replace(/id_parent=[0-9]*/, "id_parent=" + value));
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
		    input("idzona").set(data.idzona ? data.idzona : "");
			// session_set("superselect,idzona", $(this).selectData().idzona, 0);

            caricaMappa(data.lat, data.lng);
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
    input("idtipointervento").change(function() {
        let data = $("#idtipointervento").selectData();
        if (data && data.tempo_standard > 0) {
            let orario_inizio = input("orario_inizio").get();
            let tempo_standard = data.tempo_standard * 60;
            let nuovo_orario_fine = moment(orario_inizio, "DD/MM/YYYY HH:mm").add(tempo_standard, "m").format("DD/MM/YYYY HH:mm");
            input("orario_fine").set(nuovo_orario_fine);
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
        let tecnici = input("idtecnico").get();

        return $("#info-conflitti-add").load("'.$module->fileurl('occupazione_tecnici.php').'", {
            "id_module": globals.id_module,
            "tecnici[]": tecnici,
            "inizio": input("orario_inizio").get(),
            "fine": input("orario_fine").get(),
        });
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

    $("#ricorsiva").on("change", function(){
        if ($(this).is(":checked")) {
            $(".ricorrenza").removeClass("hidden");
            $("#data_inizio_ricorrenza").attr("required", true);
            $("#metodo_ricorrenza").attr("required", true);
            $("#idstatoricorrenze").attr("required", true);
        } else {
            $(".ricorrenza").addClass("hidden");
            $("#data_inizio_ricorrenza").attr("required", false);
            $("#metodo_ricorrenza").attr("required", false);
            $("#idstatoricorrenze").attr("required", false);
        }
    });

    $("#metodo_ricorrenza").on("change", function(){
        if ($(this).val()=="data") {
            input("data_fine_ricorrenza").enable();
            $("#data_fine_ricorrenza").attr("required", true);
            input("numero_ricorrenze").disable();
            input("numero_ricorrenze").set("");  
        } else {
            input("numero_ricorrenze").enable();
            input("data_fine_ricorrenza").disable();
            input("data_fine_ricorrenza").set("");
            $("#data_fine_ricorrenza").attr("required", false);
        }
    });

    var map = null;
    function caricaMappa(lat, lng) {
        if (!autoload_mappa){
            return false;
        }

        //console.log(lat, lng);
        if (typeof lat === "undefined" || typeof lng === "undefined"){
            swal("'.tr('Errore').'", "'.tr('La posizione non è stata definita. Impossibile caricare la mappa.').'", "error");
            return false;
        }
        
        if (input("idanagrafica").getData("select-options")) {
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
            
            var marker = L.marker([lat, lng], {
                icon: icon
            }).addTo(map);
            
            map.setView([lat, lng], 14);
        }
    }
</script>';
