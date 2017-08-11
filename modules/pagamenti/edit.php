<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" role="form" id="check">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Dati') ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-xs-12 col-md-12">
					{[ "type": "text", "label": "<?php echo _('Descrizione') ?>", "name": "descrizione",  "value": "$descrizione$" ]}
				</div>
			</div>
		</div>
	</div>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Rate') ?></h3>
		</div>

		<div class="panel-body">
			<div class="data">
<?php
$values = '';
for ($i = 1; $i <= 31; ++$i) {
    $values .= '\"'.$i.'\": \"'.$i.'\"';
    if ($i != 31) {
        $values .= ',';
    }
}

$results = $dbo->fetchArray('SELECT * FROM `co_pagamenti` WHERE descrizione='.prepare($records[0]['descrizione']).' ORDER BY `num_giorni` ASC');
$cont = 1;
foreach ($results as $result) {
    echo '
				<div class="box box-success">
					<div class="box-header with-border">
						<h3 class="box-title">'.str_replace('_NUMBER_', $cont, _('Rata _NUMBER_')).'</h3>
						<a class=" btn btn-danger pull-right" onclick="';
    echo "if(confirm('"._('Eliminare questo elemento?')."')){ location.href='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=delete_rata&id='.$result['id']."'; }";
    echo '"><i class="fa fa-trash"></i> '._('Elimina').'</a>
					</div>
					<div class="box-body">
						<input type="hidden" value="'.$result['id'].'" name="id[]">

						<div class="row">
							<div class="col-xs-12 col-md-6">
								{[ "type": "number", "label": "'._('Percentuale').'", "name": "percentuale[]", "value": "'.$result['prc'].'", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
							</div>

							<div class="col-xs-12 col-md-6">
								{[ "type": "select", "label": "'._('Scadenza').'", "name": "scadenza[]", "values": "list=\"1\":\"'._('Data fatturazione').'\",\"2\":\"'._('Data fatturazione fine mese').'\",\"3\":\"'._('Data fatturazione giorno fisso').'\",\"4\":\"'._('Data fatturazione fine mese (giorno fisso)').'\"", "value": "';
    if ($select['giorno'] == 0) {
        $select = 1;
    } elseif ($select['giorno'] == -1) {
        $select = 2;
    } elseif ($select['giorno'] < -1) {
        $select = 4;
    } elseif ($select['giorno'] > 0) {
        $select = 3;
    }
    echo $select;
    echo '" ]}
							</div>
                        </div>

                        <div class="row">
							<div class="col-xs-12 col-md-6">
								{[ "type": "select", "label": "'._('Giorno').'", "name": "giorno[]", "values": "list='.$values.'", "value": "';
    if ($result['giorno'] != 0 && $result['giorno'] != -1) {
        echo ($result['giorno'] < -1) ? -$result['giorno'] - 1 : $result['giorno'];
    }
    echo '", "extra": "';
    if ($result['giorno'] == 0 || $result['giorno'] == -1) {
        echo ' disabled';
    }
    echo '" ]}
							</div>

							<div class="col-xs-12 col-md-6">
								{[ "type": "number", "label": "'._('Distanza in giorni').'", "name": "distanza[]", "decimals": "0", "value": "'.$result['num_giorni'].'" ]}
							</div>
						</div>
					</div>
				</div>';
    ++$cont;
}
?>
			</div>
			<div class="pull-right">
				<button type="button" class="btn btn-info" id="add"><i class="fa fa-plus"></i> <?php echo _('Aggiungi'); ?></button>
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva'); ?></button>
			</div>
		</div>
	</div>

</form>

<div class="box box-warning box-solid text-center hide" id="wait">
	<div class="box-header with-border">
		<h3 class="box-title"><i class="fa fa-warning"></i> <?php echo _('Attenzione!'); ?></h3>
	</div>
	<div class="box-body">
		<p><?php echo _('Prima di poter continuare con il salvataggio è necessario che i valori percentuali raggiungano in totale il 100%'); ?>.</p>
	</div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo _('Elimina'); ?>
</a>
<?php
echo '
<form class="hide" id="template">
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">'._('Nuova rata').'</h3>
        </div>
        <div class="box-body">
            <input type="hidden" value="" name="id[]">

            <div class="row">
                <div class="col-xs-12 col-md-6">
                    {[ "type": "number", "label": "'._('Percentuale').'", "name": "percentuale[]", "icon-after": "<i class=\"fa fa-percent\"></i>" ]}
                </div>

                <div class="col-xs-12 col-md-6">
                    {[ "type": "select", "label": "'._('Scadenza').'", "name": "scadenza[]", "values": "list=\"1\":\"'._('Data fatturazione').'\",\"2\":\"'._('Data fatturazione fine mese').'\",\"3\":\"'._('Data fatturazione giorno fisso').'\",\"4\":\"'._('Data fatturazione fine mese (giorno fisso)').'\"", "value": 1 ]}
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-md-6">
                    {[ "type": "select", "label": "'._('Giorno').'", "name": "giorno[]", "values": "list='.$values.'" ]}
                </div>

                <div class="col-xs-12 col-md-6">
                    {[ "type": "number", "label": "'._('Distanza in giorni').'", "name": "distanza[]", "decimals": "0" ]}
                </div>
            </div>
        </div>
    </div>
</form>';

?>

<script>
$(document).ready(function(){
	$(document).on('click', '#add', function(){
        $("#template .superselect, #template_filter .superselectajax").select2().select2("destroy");

        $(this).parent().parent().find('.data').append($('#template').html());

        start_superselect();
	});

	$(document).on('change', '[id*=scadenza]', function(){
        if($(this).val() == 1 || $(this).val() == 2){
            $(this).parentsUntil('.box').find('[id*=giorno]').prop('disabled', true);
        }else{
            $(this).parentsUntil('.box').find('[id*=giorno]').prop('disabled', false);
        }
    });

	$('#check').submit(function(event) {
		var tot = 0;

		$(this).find('[id*=percentuale]').each(function(){
            prc = $(this).val().toEnglish();
            prc = !isNaN(prc) ? prc : 0;

			tot += prc;
		});

		if(tot != 100) {
			$('#wait').removeClass("hide");
			event.preventDefault();
		}
	});
});
</script>
