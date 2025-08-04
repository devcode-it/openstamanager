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

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;

/**
 * Questo file gestisce la lettura delle informazioni di Scadenze e Fatture indicate per la generazione della Prima Nota. Per maggiori informazioni sulla grafica inerente alla visualizzazione delle diverse righe, consulare il file `movimenti.php`.
 *
 * Questo file prevede diverse operazioni per la generazione di un singolo array `$movimenti` contenente tutti i movimenti da presentare nella Prima Nota. In particolare:
 *  - Individua Scadenze e Fatture per ID da URL
 *  - Legge le informazioni relative alle Scadenze per presentare i movimenti in Dare e Avere
 *  - Legge le informazioni relative alla Scadenze le Fatture indicate (sola della prima Scadenza insoluta se `is_insoluto` impostato):
 *      - Per Fatture di vendita, il totale è Avere (a meno di Note di credito oppure insoluto)
 *      - Per Fatture di acquisto, il totale è Dare (a meno di Note di credito oppure insoluto)
 *  - Inverte Dare e Avere se l'importo indicato è negativo [TODO: documentare la casistica]
 *  - Genera la causale predefinita per la Prima Nota sulla base delle Scadenze indicate
 *
 * Nel caso in cui sia indicato una singola Scadenza (con o senza Fattura associata) viene permessa la gestione attraverso un Modello di Prima Nota, che prevede una compilazione di base per alcuni movimenti specificati nel relativo modulo.
 * Nota: questo comportamento viene abilitato dalla variabile `$permetti_modelli`.
 */
$id_module = Module::where('name', 'Prima nota')->first()->id;
$movimenti = [];

// Registrazione da remoto
$id_records = get('id_records');
if (!empty($id_records)) {
    $id_records = str_replace(';', ',', $id_records);
    if (get('origine') == 'fatture') {
        $id_documenti = $id_records;
    } else {
        $id_scadenze = $id_records;
    }
}

// ID predefiniti
$singola_scadenza = get('single') != null;
$is_insoluto = get('is_insoluto') != null;

$id_documenti = $id_documenti ?: get('id_documenti');
$id_documenti = $id_documenti ? explode(',', (string) $id_documenti) : [];

$id_scadenze = $id_scadenze ?: get('id_scadenze');
$id_scadenze = $id_scadenze ? explode(',', (string) $id_scadenze) : [];

// Controllo per l'utilizzo dei modelli di Prima Nota (per singolo documento o singola scadenza)
$permetti_modelli = (count($id_documenti) + count($id_scadenze)) <= 1;

// Scadenze
foreach ($id_scadenze as $id_scadenza) {
    $scadenza = $database->fetchOne('SELECT *, SUM(da_pagare - pagato) AS rata FROM co_scadenziario WHERE id='.prepare($id_scadenza));
    if (!empty($scadenza['iddocumento'])) {
        $id_documenti[] = $scadenza['iddocumento'];
        continue;
    }
    $dir = $scadenza['rata'] > 0 ? 'entrata' : 'uscita';
    $scadenza['rata'] = abs($scadenza['rata']);

    $descrizione_conto = ($dir == 'entrata') ? 'Riepilogativo clienti' : 'Riepilogativo fornitori';
    $conto = $database->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = '.prepare($descrizione_conto));
    $id_conto_controparte = $conto['id'];

    $righe_documento = [];
    $righe_documento[] = [
        'iddocumento' => null,
        'id_scadenza' => $scadenza['id'],
        'id_conto' => null,
        'dare' => ($dir == 'uscita') ? 0 : $scadenza['rata'],
        'avere' => ($dir == 'uscita') ? $scadenza['rata'] : 0,
    ];

    $righe_documento[] = [
        'iddocumento' => null,
        'id_scadenza' => $scadenza['id'],
        'id_conto' => $id_conto_controparte,
        'dare' => ($dir == 'uscita') ? $scadenza['rata'] : 0,
        'avere' => ($dir == 'uscita') ? 0 : $scadenza['rata'],
    ];

    // Se è un insoluto, inverto i valori
    if ($is_insoluto) {
        foreach ($righe_documento as $key => $value) {
            $tmp = $value['avere'];
            $righe_documento[$key]['avere'] = $righe_documento[$key]['dare'];
            $righe_documento[$key]['dare'] = $tmp;
        }
    }

    $movimenti = array_merge($movimenti, $righe_documento);
}

// Fatture
$numeri_fatture = [];
$counter = 0;
$is_ultimo_importo_avere = false;

$id_documenti = array_unique($id_documenti);
$id_anagrafica_movimenti = null;
foreach ($id_documenti as $id_documento) {
    $fattura = Fattura::find($id_documento);
    $tipo = $fattura->tipo;
    $dir = $fattura->direzione;

    // Inclusione delle sole fatture in stato Emessa, Parzialmente pagato o Pagato
    if (!in_array($fattura->stato->name, ['Emessa', 'Parzialmente pagato', 'Pagato'])) {
        ++$counter;
        continue;
    }

    if ($id_anagrafica_movimenti == null) {
        $id_anagrafica_movimenti = $fattura->idanagrafica;
    } elseif ($fattura->idanagrafica != $id_anagrafica_movimenti) {
        $id_anagrafica_movimenti = 0;
    }

    $numeri_fatture[] = !empty($fattura['numero_esterno']) ? $fattura['numero_esterno'] : $fattura['numero'];

    $is_nota_credito = $tipo->reversed;
    $is_importo_avere = ($dir == 'entrata' && !$is_nota_credito && !$is_insoluto) || ($dir == 'uscita' && ($is_nota_credito || $is_insoluto));

    // Predisposizione prima riga
    $banca = Banca::find($fattura->id_banca_azienda);
    $conto_field = 'idconto_'.($dir == 'entrata' ? 'vendite' : 'acquisti');
    $id_conto_aziendale = $banca->id_pianodeiconti3 ?: ($fattura->pagamento[$conto_field] ?: setting('Conto aziendale predefinito'));

    // Se sto registrando un insoluto, leggo l'ultima scadenza pagata altrimenti leggo la scadenza della fattura
    if ($is_insoluto) {
        $scadenze = $database->fetchArray('SELECT id, ABS(da_pagare) AS rata, iddocumento, tipo FROM co_scadenziario WHERE iddocumento='.prepare($id_documento).' AND ABS(da_pagare) = ABS(pagato) ORDER BY updated_at DESC LIMIT 0, 1');
    } else {
        $scadenze = $database->fetchArray('SELECT id, ABS(da_pagare - pagato) AS rata, iddocumento, tipo FROM co_scadenziario WHERE iddocumento='.prepare($id_documento).' AND ABS(da_pagare) > ABS(pagato)'.(!empty($id_scadenze) ? 'AND id IN('.implode(',', $id_scadenze).')' : '').' ORDER BY YEAR(scadenza) ASC, MONTH(scadenza) ASC');
    }

    // Selezione prima scadenza
    if ($singola_scadenza && !empty($scadenze)) {
        $scadenze = [$scadenze[0]];
    }

    $righe_documento = [];

    // Riga controparte
    foreach ($scadenze as $scadenza) {
        // Predisposizione conto
        if ($scadenza['tipo'] == 'ritenutaacconto') {
            $id_conto_controparte = setting("Conto per Erario c/ritenute d'acconto");
        } else {
            $conto_field = 'idconto_'.($dir == 'entrata' ? 'cliente' : 'fornitore');
            $id_conto_controparte = $fattura->anagrafica[$conto_field];
        }

        $righe_documento[] = [
            'iddocumento' => $scadenza['iddocumento'],
            'id_scadenza' => $scadenza['id'],
            'id_conto' => $id_conto_controparte,
            'dare' => $is_importo_avere ? 0 : $scadenza['rata'],
            'avere' => $is_importo_avere ? $scadenza['rata'] : 0,
        ];
    }

    // Riga aziendale
    $totale = sum(array_column($scadenze, 'rata'));

    $righe_documento[] = [
        'iddocumento' => $scadenze[0]['iddocumento'],
        'id_scadenza' => $scadenze[0]['id'],
        'id_conto' => $id_conto_aziendale,
        'dare' => $is_importo_avere ? $totale : 0,
        'avere' => $is_importo_avere ? 0 : $totale,
    ];

    $is_ultimo_importo_avere = $is_importo_avere;
    $movimenti = array_merge($movimenti, $righe_documento);
}

// Inverto dare e avere per importi negativi
foreach ($movimenti as $key => $value) {
    if ($movimenti[$key]['dare'] < 0 || $movimenti[$key]['avere'] < 0) {
        $tmp = abs($movimenti[$key]['dare']);
        $movimenti[$key]['dare'] = abs($movimenti[$key]['avere']);
        $movimenti[$key]['avere'] = $tmp;
    }
}

// Descrizione
$numero_scadenze = count($id_scadenze);
$numero_documenti = count($id_documenti);
if ($numero_documenti + $numero_scadenze > 1) {
    if (!empty($id_anagrafica_movimenti)) {
        $anagrafica_movimenti = Anagrafica::find($id_anagrafica_movimenti);

        $descrizione = $is_ultimo_importo_avere ? tr('Inc. fatture _NAME_ num. _LIST_') : tr('Pag. fatture _NAME_ num. _LIST_');
        $descrizione = replace($descrizione, [
            '_NAME_' => $anagrafica_movimenti->ragione_sociale ?: '',
            '_LIST_' => implode(', ', $numeri_fatture),
        ]);
    } else {
        $descrizione = $is_ultimo_importo_avere ? tr('Inc. fatture num. _LIST_') : tr('Pag. fatture _NAME_ num. _LIST_');
        $descrizione = replace($descrizione, [
            '_LIST_' => implode(', ', $numeri_fatture),
        ]);
    }
} elseif ($numero_documenti == 1) {
    $numero_fattura = !empty($fattura['numero_esterno']) ? $fattura['numero_esterno'] : $fattura['numero'];
    $tipo_fattura = $fattura->isNota() ? $tipo->getTranslation('title') : tr('Fattura');

    if (!empty($is_insoluto)) {
        $operation = tr('Registrazione insoluto');
    } else {
        $operation = $is_ultimo_importo_avere ? tr('Inc.') : tr('Pag.');
    }

    $descrizione = tr('_OP_ _DOC_ num. _NUM_ del _DATE_ (_NAME_)', [
        '_OP_' => $operation,
        '_DOC_' => strtolower((string) $tipo_fattura),
        '_NUM_' => $numero_fattura,
        '_DATE_' => Translator::dateToLocale($fattura['data']),
        '_NAME_' => $fattura->anagrafica['ragione_sociale'],
    ]);
} elseif ($numero_scadenze == 1) {
    $descrizione = tr('Pag. _OP_ del _DATE_', [
        '_OP_' => $scadenza['descrizione'],
        '_DATE_' => Translator::dateToLocale($scadenza['scadenza']),
    ]);
}

if (!empty($id_records) && get('origine') == 'fatture' && $counter > 0) {
    $descrizione_stati = [];
    $stati = Stato::whereIn('name', ['Emessa', 'Parzialmente pagato', 'Pagato'])->get()->pluck('name');
    $descrizione_stati = implode(', ', $stati->toArray());

    echo '
<div class="alert alert-info">
'.tr('Solo le fatture in stato _STATE_ possono essere registrate contabilmente', [
        '_STATE_' => '<strong>'.$descrizione_stati.'</strong>',
    ]).'.
</div>
<div class="alert alert-warning">
'.tr('Fatture ignorate: _NUM_', [
        '_NUM_' => '<strong>'.$counter.'</strong>',
    ]).'
</div>';
}
if (!empty(get('id_anagrafica'))) {
    $id_anagrafica = get('id_anagrafica');
}
if (empty($id_anagrafica)) {
    $id_anagrafica = $dbo->fetchOne('SELECT idanagrafica FROM co_documenti WHERE id IN('.($id_documenti ? implode(',', $id_documenti) : 0).')')['idanagrafica'];
}
if (empty($id_anagrafica)) {
    $id_anagrafica = $dbo->fetchOne('SELECT idanagrafica FROM co_scadenziario WHERE id IN('.($id_scadenze ? implode(',', $id_scadenze) : 0).')')['idanagrafica'];
}
echo '
<form action="'.base_path().'/controller.php?id_module='.$id_module.'" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="crea_modello" id="crea_modello" value="0">
	<input type="hidden" name="idmastrino" id="idmastrino" value="0">
    <input type="hidden" name="is_insoluto" value="'.$is_insoluto.'">
    <input type="hidden" name="id_anagrafica" id="id_anagrafica" value="'.$id_anagrafica.'">';

if ($permetti_modelli) {
    echo '
	<div class="row">
		<div class="col-md-12">
			{[ "type": "select", "label": "'.tr('Modello prima nota').'", "name": "modello_primanota", "values": "query=SELECT idmastrino AS id, nome AS descrizione, descrizione as causale FROM co_movimenti_modelli GROUP BY idmastrino" ]}
		</div>
	</div>';
}

echo '
	<div class="row">
		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data movimento').'", "name": "data_add", "required": 1, "value": "-now-" ]}
		</div>

		<div class="col-md-8">
			{[ "type": "text", "label": "'.tr('Causale').'", "name": "descrizione_add", "id": "desc_add", "required": 1, "value": '.json_encode($descrizione).' ]}
		</div>
    </div>';

if (!empty($id_anagrafica)) {
    $id_conto_anticipo_fornitori = setting('Conto anticipo fornitori');
    $id_conto_anticipo_clienti = setting('Conto anticipo clienti');

    $anticipo_cliente = $dbo->fetchOne('SELECT ABS(SUM(totale)) AS totale FROM co_movimenti WHERE  co_movimenti.id_anagrafica='.prepare($id_anagrafica).' AND  co_movimenti.idconto='.prepare($id_conto_anticipo_clienti));

    $anticipo_fornitore = $dbo->fetchOne('SELECT ABS(SUM(totale)) AS totale FROM co_movimenti WHERE  co_movimenti.id_anagrafica='.prepare($id_anagrafica).' AND  co_movimenti.idconto='.prepare($id_conto_anticipo_fornitori));

    if ($anticipo_fornitore['totale'] != 0) {
        echo '
        <div class="alert alert-warning">
            '.tr('Attenzione: è stato anticipato al fornitore un importo di _TOTALE_',
            [
                '_TOTALE_' => moneyFormat($anticipo_fornitore['totale']),
            ]
        ).'
        </div>';
    }

    if ($anticipo_cliente['totale'] != 0) {
        echo '
        <div class="alert alert-warning">
            '.tr('Attenzione: è stato ricevuto un anticipo dal cliente di _TOTALE_',
            [
                '_TOTALE_' => moneyFormat($anticipo_cliente['totale']),
            ]
        ).'
        </div>';
    }
}

include $structure->filepath('movimenti.php');

// Possibilità di forzare la chiusura della scadenza per le scadenze generiche
if (empty($id_documenti) && !empty($id_scadenze)) {
    echo '
    <div class="row">
		<div class="offset-md-9 col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Forza chiusura scadenza').'", "name": "chiudi_scadenza_add" ]}
        </div>
    </div>';
}

echo '
	<!-- PULSANTI -->
	<div class="modal-footer">
		<div class="col-md-12 text-right">
			<button type="button" class="btn btn-info" disabled id="modello-button">
			    <i class="fa fa-plus"></i> '.tr('Aggiungi e crea modello').'
            </button>

			<button type="submit" class="btn btn-primary" disabled id="add-submit">
			    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
		</div>
	</div>
</form>';

echo '
<script type="text/javascript">
$("#modals > div #add-form").on("submit", function(e) {
    return controllaConti();
});
</script>';

if ($permetti_modelli) {
    $variables = Module::where('name', 'Anagrafiche')->first()->getPlaceholders($id_anagrafica);

    echo '
<script type="text/javascript">
    globals.prima_nota = {
        variables: '.json_encode($variables).',
        id_documento: "'.$id_documenti[0].'",
        translations: {
            nuovoModello: "'.tr('Aggiungi e crea modello').'",
            modificaModello: "'.tr('Aggiungi e modifica modello').'",
        },
        id_mastrino_input: input("idmastrino"),
        modello_input: input("modello_primanota"),
        modello_button: $("#modello-button"),
    };

    var modello_input = globals.prima_nota.modello_input;
    var id_mastrino_input = globals.prima_nota.id_mastrino_input;
    var modello_button = globals.prima_nota.modello_button;
    modello_button.prop("disabled", false);

    modello_input.change(function() {
        let id_mastrino = modello_input.get();
        if (id_mastrino) {
            $("#modello-button").html(`<i class="fa fa-edit"></i> ` + globals.prima_nota.translations.modificaModello);
            id_mastrino_input.set(id_mastrino);
        } else {
            $("#modello-button").html(`<i class="fa fa-plus"></i> ` + globals.prima_nota.translations.nuovoModello);
            id_mastrino_input.set(0);
            return;
        }

        // Aggiornamento della causale nel caso di Fattura
        let causale = modello_input.getData().causale;
        if (globals.prima_nota.id_documento !== "") {
            for ([key, value] of Object.entries(globals.prima_nota.variables)) {
                causale = causale.replace(key, value);
            }

            $("#modals > div #desc_add").val(causale);
        }

        if ($("#modals > div #desc_add").val() == "") {
            $("#modals > div #desc_add").val(causale);
        }

        $.get(globals.rootdir + "/ajax_complete.php?op=get_conti&idmastrino=" + id_mastrino, function(data) {
            let conti = data.split(",");
            let table = $("#modals table.scadenze").first();
            let button = table.parent().find("button").first();

            // Creazione delle eventuali righe aggiuntive
            let row_needed = conti.length;
            let row_count = table.find("tr").length - 2;
            while (row_count < row_needed) {
                button.click();
                row_count = table.find("tr").length - 2;
            }

            // Iterazione su tutti i conti del Modello
            let righe = table.find("tr");
            for (let i = 0; i < conti.length; i++) {
                const dati_conto = conti[i].split(";");

                let id_conto = parseInt(dati_conto[0]);
                let descrizione_conto = dati_conto[1];
                let totale = dati_conto[2];

                // Sostituzione del conto dell\'Anagrafica
                if (id_conto === -1){
                    id_conto = parseInt(globals.prima_nota.variables["{conto}"]);
                    descrizione_conto = globals.prima_nota.variables["{conto_descrizione}"];
                }

                // Selezione del conto
                let select = $(righe[i + 1]).find("select");
                input(select).getElement()
                    .selectSetNew(id_conto, descrizione_conto);

                if(totale>0){
                    input_field = $(righe[i + 1]).find("input[id*=dare]");
                } else{
                    input_field = $(righe[i + 1]).find("input[id*=avere]");
                    totale = -totale;
                }
                input(input_field).getElement()
                        .val(totale).trigger("change");
            }
        });

    });

    $("#modals > div #modello-button").click(function() {
        $("#modals > div #crea_modello").val("1");
        $("#modals > div #add-form").submit();
    });
</script>';
}
