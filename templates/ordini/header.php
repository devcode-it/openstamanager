<?php

echo '
$default_header$
<br>

<div class="row">

    <!-- Dati Ordine -->
    <div class="col-xs-6">
		<div class="text-center" style="height:5mm;">
			<b>$tipo_doc$</b>
		</div>

		<table class="table table-bordered">
			<tr>
				<td class="border-full text-center">
					<p class="small-bold">'.tr('Nr. documento', [], ['upper' => true]).'</p>
					<p>$numero_doc$</p>
				</td>

				<td class="border-right border-bottom border-top text-center">
					<p class="small-bold">'.tr('Data documento', [], ['upper' => true]).'</p>
					<p>$data$</p>
				</td>

				<td class="border-right border-bottom border-top text-center">
					<p class="small-bold">'.tr('Pagamento', [], ['upper' => true]).'</p>
					<p>$pagamento$</p>
				</td>

				<td class="border-right border-bottom border-top center text-center">
					<p class="small-bold">'.tr('Foglio', [], ['upper' => true]).'</p>
					<p>{PAGENO}/{nb}</p>
				</td>
			</tr>
		</table>
	</div>
	
	<!-- Dati Cliente/Fornitore -->
	<div class="col-xs-5 col-xs-offset-1">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td class="border-full" style="height:20mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$<br> $c_citta_full$</p>
                </td>
            </tr>
        </table>
    </div>
	
	
</div>';
