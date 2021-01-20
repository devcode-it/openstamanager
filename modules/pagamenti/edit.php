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
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "value": "$descrizione$", "required": 1 ]}
                </div>

                <div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Codice Modalità (Fatturazione Elettronica)'); ?>", "name": "codice_modalita_pagamento_fe", "value": "$codice_modalita_pagamento_fe$", "values": "query=SELECT codice as id, CONCAT(codice, ' - ', descrizione) AS descrizione FROM fe_modalita_pagamento", "required": 1 ]}
				</div>

				<div class="col-md-2">
					{[ "type": "checkbox", "label": "<?php echo tr('Pagamento di tipo Ri.Ba.'); ?>", "name": "riba", "value": "$riba$", "help": "<?php echo tr('Abilitando questa impostazione, nelle fatture verrà visualizzata la banca della controparte'); ?>" ]}
				</div>
            </div>

			<div class="row">
                <div class="col-md-6">
					{[ "type": "select", "label": "<?php echo tr('Conto predefinito per le vendite'); ?>", "name": "idconto_vendite", "value": "$idconto_vendite$", "ajax-source": "conti"  ]}
                </div>

                <div class="col-md-6">
					{[ "type": "select", "label": "<?php echo tr('Conto predefinito per gli acquisti'); ?>", "name": "idconto_acquisti", "value": "$idconto_acquisti$", "ajax-source": "conti" ]}
				</div>
			</div>
		</div>
	</div>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Rate'); ?></h3>
		</div>

		<div class="panel-body">
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

$results = $dbo->fetchArray('SELECT * FROM `co_pagamenti` WHERE descrizione='.prepare($record['descrizione']).' ORDER BY `num_giorni` ASC');
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

    echo '
				<div class="box box-success">
					<div class="box-header with-border">
						<h3 class="box-title">'.tr('Rata _NUMBER_', [
                            '_NUMBER_' => $numero_rata,
                        ]).'</h3>
						<button type="button" class="btn btn-danger pull-right" onclick="rimuoviRata('.$result['id'].')">
						    <i class="fa fa-trash"></i> '.tr('Elimina').'
						</button>
					</div>
					<div class="box-body">
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
?>
			</div>

			<div class="pull-right">
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

<div class="box box-warning box-solid text-center hide" id="wait">
	<div class="box-header with-border">
		<h3 class="box-title">
            <i class="fa fa-warning"></i> <?php echo tr('Attenzione!'); ?>
        </h3>
	</div>
	<div class="box-body">
		<p><?php echo tr('Prima di poter continuare con il salvataggio è necessario che i valori percentuali raggiungano in totale il 100%'); ?>.</p>
	</div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
<?php
echo '
<form class="hide" id="template">
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Nuova rata').'</h3>
        </div>
        <div class="box-body">
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
    aggiungiContenuto("#elenco-rate", "#template", {"-id-": indice_rata});
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
        location.href = "<?php echo base_path(); ?>/editor.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&op=delete_rata&id=" + id;
    }
}
</script>
