<?php

include_once __DIR__.'/../../core.php';

?><form action="<?php echo ROOTDIR ?>/editor.php?id_module=<?php echo Modules::get('Modelli prima nota')['id']; ?>" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-8">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "<?php echo $descrizione; ?>" ]}
		</div>
	</div>


	<?php

    // Salvo l'elenco conti in un array (per non fare il ciclo ad ogni riga)

    /*
        Form di aggiunta riga movimento
    */
    echo '
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
            <th>'.tr('Conto').'</th>
        </tr>';

    for ($i = 0; $i < 10; ++$i) {
        
		($i<=1) ? $required = 1 : $required = 0;
		// Conto
        echo '
			<tr>
				<td>
					{[ "type": "select", "name": "idconto['.$i.']", "value": "';
        if ($i == 0) {
            echo $idconto_controparte;
        } elseif ($i == 1) {
            echo $idconto_aziendale;
        }
        echo '", "ajax-source": "conti", "required": "'.$required.'" ]}
				</td>';

		echo '
			</tr>';
    }

    echo '
        </table>';
    ?>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>

	<script type="text/javascript">
		$(document).ready( function(){
			$('select').on('change', function(){
                if($(this).parent().parent().find('input[disabled]').length != 1){
                    if($(this).val()) {
                        $(this).parent().parent().find('input').prop("disabled", false);
                    }
                    else{
                        $(this).parent().parent().find('input').prop("disabled", true);
                        $(this).parent().parent().find('input').val("");
                    }
                }
			});
		});
	</script>
</form>

