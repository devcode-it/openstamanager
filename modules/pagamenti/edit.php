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

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-8">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "value": "$title$", "required": 1 ]}
                </div>

                <div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Codice Modalità (Fatturazione Elettronica)'); ?>", "name": "codice_modalita_pagamento_fe", "value": "$codice_modalita_pagamento_fe$", "values": "query=SELECT `codice` as id, CONCAT(`codice`, ' - ', `title`) AS descrizione FROM `fe_modalita_pagamento` LEFT JOIN `fe_modalita_pagamento_lang` ON (`fe_modalita_pagamento_lang`.`id_record`=`fe_modalita_pagamento`.`codice` AND `fe_modalita_pagamento_lang`.`id_lang`=<?php echo prepare(Models\Locale::getDefault()->id); ?>)", "required": 1, "help": "<?php echo tr('Impostando il codice MP12 il pagamento viene considerato di tipo Ri.Ba.: nelle fatture verrà visualizzata la banca della controparte'); ?>" ]}
				</div>
            </div>

			<div class="row">
                <div class="col-md-6">
					{[ "type": "select", "label": "<?php echo tr('Conto predefinito per le vendite'); ?>", "name": "idconto_vendite", "value": "$idconto_vendite$", "ajax-source": "conti" ]}
                </div>

                <div class="col-md-6">
					{[ "type": "select", "label": "<?php echo tr('Conto predefinito per gli acquisti'); ?>", "name": "idconto_acquisti", "value": "$idconto_acquisti$", "ajax-source": "conti" ]}
				</div>
			</div>
		</div>
	</div>

    <div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Spese di incasso in fattura'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Descrizione riga'); ?>", "name": "descrizione_incasso", "value": "$descrizione_incasso$" ]}
                </div>

                <div class="col-md-3">
					{[ "type": "number", "label": "<?php echo tr('Importo fisso'); ?>", "name": "importo_fisso_incasso", "value": "$importo_fisso_incasso$", "icon-after": "<?php echo currency(); ?>" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Importo percentuale'); ?>", "name": "importo_percentuale_incasso", "value": "$importo_percentuale_incasso$", "icon-after": "%" ]}
				</div>
			</div>
		</div>
	</div>

	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Rate'); ?></h3>
		</div>

		<div class="card-body">
			<div id="elenco-rate">
<?php
$giorni_pagamento = [];
for ($i = 1; $i <= 31; ++$i) {
    $giorni_pagamento[] = [
        'id' => $i,
        'text' => $i,
    ];
}

$tipi_scadenza_pagamento = [
    [
        'id' => 1,
        'text' => tr('Data fatturazione'),
    ],
    [
        'id' => 2,
        'text' => tr('Data fatturazione fine mese'),
    ],
    [
        'id' => 3,
        'text' => tr('Data fatturazione giorno fisso'),
    ],
    [
        'id' => 4,
        'text' => tr('Data fatturazione fine mese (giorno fisso)'),
    ],
];

$results = $dbo->fetchArray('SELECT *, `co_pagamenti`.`id` as id FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `title`='.prepare($record['name']).' ORDER BY `num_giorni` ASC');
$numero_rata = 1;
foreach ($results as $result) {
    $tipo_scadenza_pagamento = 3;
    if ($result['giorno'] == 0) {
        $tipo_scadenza_pagamento = 1;
    } elseif ($result['giorno'] == -1) {
        $tipo_scadenza_pagamento = 2;
    } elseif ($result['giorno'] < -1) {
        $tipo_scadenza_pagamento = 4;
    }

    $giorno_pagamento = null;
    if ($result['giorno'] != 0 && $result['giorno'] != -1) {
        $giorno_pagamento = ($result['giorno'] < -1) ? -$result['giorno'] - 1 : $result['giorno'];
    }

    // Collegamenti diretti
    $elementi = $dbo->fetchArray('SELECT `co_documenti`.`id`, `co_documenti`.`data`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_tipidocumento_lang`.`title` AS tipo_documento, `co_tipidocumento`.`dir`, NULL AS `deleted_at` FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id`=`co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).') WHERE `co_documenti`.`idpagamento` = '.prepare($id_record).'

UNION
SELECT `or_ordini`.`id`, `or_ordini`.`data`, `or_ordini`.`numero`, `or_ordini`.`numero_esterno`, `or_tipiordine_lang`.`title` AS tipo_documento, `or_tipiordine`.`dir`, NULL AS `deleted_at` FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_tipiordine`.`id` = `or_ordini`.`idtipoordine` LEFT JOIN `or_tipiordine_lang` ON (`or_tipiordine`.`id`=`or_tipiordine_lang`.`id_record` AND `or_tipiordine_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).') WHERE `or_ordini`.`idpagamento` = '.prepare($id_record).'

UNION
SELECT `dt_ddt`.`id`, `dt_ddt`.`data`, `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`, `dt_tipiddt_lang`.`title` AS tipo_documento, `dt_tipiddt`.`dir`, NULL AS `deleted_at` FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` LEFT JOIN `dt_tipiddt_lang` ON (`dt_tipiddt`.`id`=`dt_tipiddt_lang`.`id_record` AND `dt_tipiddt_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).') WHERE `dt_ddt`.`idpagamento` = '.prepare($id_record).'

UNION
SELECT `co_contratti`.`id`, `co_contratti`.`data_bozza`, `co_contratti`.`numero`, 0 AS numero_esterno , "Contratto" AS tipo_documento, 0 AS dir, NULL AS `deleted_at` FROM `co_contratti` WHERE `co_contratti`.`idpagamento` = '.prepare($id_record).'

UNION
SELECT `co_preventivi`.`id`, `co_preventivi`.`data_bozza`, `co_preventivi`.`numero`, 0 AS numero_esterno , "Preventivo" AS tipo_documento, 0 AS dir, NULL AS `deleted_at` FROM `co_preventivi` WHERE `co_preventivi`.`idpagamento` = '.prepare($id_record).'

ORDER BY `data`');

    echo '
				<div class="card card-success">
					<div class="card-header with-border">
						<h3 class="card-title">'.tr('Rata _NUMBER_', [
        '_NUMBER_' => $numero_rata,
    ]).'</h3>';
    if (empty($elementi)) {
        echo '   
						<button type="button" class="btn btn-danger pull-right" onclick="rimuoviRata('.$result['id'].')">
						    <i class="fa fa-trash"></i> '.tr('Elimina').'
                            </button>';
    }
    echo '
					</div>
					<div class="card-body">
						<input type="hidden" value="'.$result['id'].'" name="id['.$numero_rata.']">

						<div class="row">
							<div class="col-md-6">
								{[ "type": "number", "label": "'.tr('Percentuale').'", "name": "percentuale['.$numero_rata.']", "decimals": "2", "min-value": "0", "value": "'.$result['prc'].'", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
							</div>

							<div class="col-md-6">
								{[ "type": "select", "label": "'.tr('Scadenza').'", "name": "scadenza['.$numero_rata.']", "values": '.json_encode($tipi_scadenza_pagamento).', "value": "'.$tipo_scadenza_pagamento.'" ]}
							</div>
                        </div>

                        <div class="row">
							<div class="col-md-6">
								{[ "type": "select", "label": "'.tr('Giorno').'", "name": "giorno['.$numero_rata.']", "values": '.json_encode($giorni_pagamento).', "value": "'.$giorno_pagamento.'", "extra": "';
    if ($result['giorno'] == 0 || $result['giorno'] == -1) {
        echo ' disabled';
    }
    echo '" ]}
							</div>

							<div class="col-md-6">
								{[ "type": "number", "label": "'.tr('Distanza in giorni').'", "name": "distanza['.$numero_rata.']", "decimals": "0", "min-value": "0", "value": "'.$result['num_giorni'].'" ]}
							</div>
						</div>
					</div>
				</div>';
    ++$numero_rata;
}

if (empty($elementi)) {
    ?>
			</div>

			<div class="float-right d-none d-sm-inline">
				<button type="button" class="btn btn-info" onclick="aggiungiRata()">
                    <i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?>
                </button>

				<button type="submit" class="btn btn-success">
                    <i class="fa fa-check"></i> <?php echo tr('Salva'); ?>
                </button>
			</div>
		</div>
	</div>
</form>

<div class="card card-warning card-solid text-center hide" id="wait">
	<div class="card-header with-border">
		<h3 class="card-title">
            <i class="fa fa-warning"></i> <?php echo tr('Attenzione!'); ?>
        </h3>
	</div>
	<div class="card-body">
		<p><?php echo tr('Prima di poter continuare con il salvataggio è necessario che i valori percentuali raggiungano in totale il 100%'); ?>.</p>
	</div>
</div>


<?php
echo '
<form class="hide" id="template">
    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">'.tr('Nuova rata').'</h3>
        </div>
        <div class="card-body">
            <input type="hidden" value="" name="id[-id-]">

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "number", "label": "'.tr('Percentuale').'", "decimals": "2", "name": "percentuale[-id-]", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Scadenza').'", "name": "scadenza[-id-]", "values": '.json_encode($tipi_scadenza_pagamento).', "value": 1 ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Giorno').'", "name": "giorno[-id-]", "values": '.json_encode($giorni_pagamento).' ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "number", "label": "'.tr('Distanza in giorni').'", "name": "distanza[-id-]", "decimals": "0" ]}
                </div>
            </div>
        </div>
    </div>
</form>';
} else {
    echo '
            </div>
        </div>
	</div>
</form>
<div class="card card-warning collapsable collapsed-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-warning"></i> '.tr('Documenti collegati: _NUM_', [
        '_NUM_' => count($elementi),
    ]).'</h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_  _NUM_ del _DATE_ _DELETED_AT_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
            '_DELETED_AT_' => (!empty($elemento['deleted_at']) ? tr('Eliminato il:').' '.Translator::dateToLocale($elemento['deleted_at']) : ''),
        ]);

        // se non è un preventivo è un ddt o una fattura
        // se non è un ddt è una fattura.
        if (in_array($elemento['tipo_documento'], ['Preventivo'])) {
            $modulo = 'Preventivi';
        } elseif (in_array($elemento['tipo_documento'], ['Contratto'])) {
            $modulo = 'Contratti';
        } elseif (in_array($elemento['tipo_documento'], ['Ordine cliente', 'Ordine fornitore'])) {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Ordini cliente' : 'Ordini fornitore';
        } elseif (in_array($elemento['tipo_documento'], ['Ddt in uscita', 'Ddt in entrata'])) {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Ddt in uscita' : 'Ddt in entrata';
        } else {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';
        }

        $id = $elemento['id'];

        echo '
            <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
    }

    echo '
        </ul>
    </div>
</div>';
}

if (!empty($elementi)) {
    echo '
<div class="alert alert-danger">
    '.tr('Eliminando questo metodo di pagamento si potrebbero verificare problemi nelle altre sezioni del gestionale').'.
</div>
<a class="btn btn-danger ask disabled" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
} else {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
?>

<script>
var indice_rata = "<?php echo $numero_rata; ?>";
$(document).ready(function() {
	$(document).on("change", "[id^=scadenza]", function() {
        const giorno = $(this).parentsUntil(".box").find("[id*=giorno]");
        const giorno_input = input(giorno[0]);

        const tipo_scadenza = parseInt(input(this).get());

        giorno_input.setDisabled(tipo_scadenza === 1 || tipo_scadenza === 2);
    });

	$(document).on("change", "input[id^=percentuale]", function() {
        controllaRate();
	});

	$("#edit-form").submit(function(event) {
	    const result = controllaRate();
	    if (!result) {
            event.preventDefault();
            return false;
        }
	});
});

function aggiungiRata() {
    aggiungiContenuto("#elenco-rate>div:last-of-type", "#template", {"-id-": indice_rata});
    indice_rata++;
}

function controllaRate() {
    let totale = 0;

    $("#elenco-rate").find("input[id^=percentuale]").each(function() {
        totale += input(this).get();
    });

    if(totale !== 100) {
        $("#wait").removeClass("hide");
    } else {
        $("#wait").addClass("hide");
    }

    return totale === 100;
}

function rimuoviRata(id) {
    if(confirm("<?php echo tr('Eliminare questo elemento?'); ?>")){
        location.href = "<?php echo base_path_osm(); ?>/editor.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&op=delete_rata&id=" + id;
    }
}
</script>
