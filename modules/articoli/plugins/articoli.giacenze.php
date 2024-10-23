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

include_once __DIR__.'/../../../core.php';

$impegnato = 0;
$ordinato = 0;

$query = 'SELECT
        `or_ordini`.`id` AS id,
        `or_ordini`.`numero`,
        `or_ordini`.`numero_esterno`,
        `data`,
        SUM(`or_righe_ordini`.`qta`) AS qta_ordinata,
        SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS qta_impegnata,
        `or_righe_ordini`.`um`
    FROM 
        `or_ordini`
        INNER JOIN `or_righe_ordini` ON `or_ordini`.`id` = `or_righe_ordini`.`idordine`
        INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine`=`or_statiordine`.`id`
        INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine`=`or_tipiordine`.`id`
    WHERE 
        `idarticolo` = '.prepare($articolo->id)."
        AND `or_tipiordine`.`dir`= '|dir|'
        AND (`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) > 0
        AND `or_righe_ordini`.`confermato` = 1
        AND `or_statiordine`.`impegnato` = 1
    GROUP BY
        `or_ordini`.`id`
    HAVING 
        `qta_ordinata` > 0";

echo '
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">'.tr('Articolo').'</h3>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <span><b>'.tr('Codice: ').'</b>'.$articolo->codice.'</span>
            </div>

            <div class="col-md-6">
                <span><b>'.tr('Descrizione: ').'</b>'.$articolo->getTranslation('title').'</span>
            </div>
        </div>
    </div>
</div>';

/*
 ** Impegnato
 */
echo '
<div class="row">
	<div class="col-md-3">
		<div class="card card-primary">
			<div class="card-header">
                <h3 class="card-title">'.tr('Impegnato').'<span class="tip pull-right" title="'.tr('Quantità impegnate in ordini cliente che non siano già completamente evasi.').'">
                <i class="fa fa-question-circle-o"></i></span></h3>
			</div>
			<div class="card-body" style="min-height:98px;">';

$ordini = $dbo->fetchArray(str_replace('|dir|', 'entrata', $query));
$impegnato = sum(array_column($ordini, 'qta_impegnata'));
if (!empty($ordini)) {
    echo '
                <table class="table table-bordered table-sm table-striped">
                    <thead>
                        <tr>
                            <th>'.tr('Descrizione').'</th>
                            <th class="text-right">'.$record['um'].'</th>
                        </tr>
                    </thead>

                    <tbody>';

    foreach ($ordini as $documento) {
        $numero = !empty($documento['numero_esterno']) ? $documento['numero_esterno'] : $documento['numero'];
        $qta = $documento['qta_impegnata'];

        echo '
                    <tr>
                        <td>
                            <small>
                                '.Modules::link('Ordini cliente', $documento['id'], tr('Ordine num. _NUM_ del _DATE_', [
            '_NUM_' => $numero,
            '_DATE_' => dateFormat($documento['data']),
        ])).'
                            </small>
                        </td>
                        <td class="text-right">
                            <small>'.numberFormat($qta, 'qta').'</small>
                        </td>
                    </tr>';
    }

    echo '
                    <tr>
                        <td class="text-right">
                            <small><b>'.tr('Totale').'</b></small>
                        </td>
                        <td class="text-right">
                            <small>'.numberFormat($impegnato, 'qta').'</small>
                        </td>
                    </tr>

                </table>';
} else {
    echo '
                <p>'.tr('Nessun ordine cliente con quantità da evadere individuato').'.</p>';
}
echo '
			</div>
		</div>
	</div>';

/*
 ** In ordine
 */
echo '
	<div class="col-md-3">
		<div class="card card-primary">
			<div class="card-header">
				<h3 class="card-title">'.tr('In ordine').'<span class="tip pull-right" title="'.tr('Quantità ordinate al fornitore in ordini che non siano già completamente evasi.').'">
                <i class="fa fa-question-circle-o"></i></span></h3>
			</div>
			<div class="card-body" style="min-height:98px;">';

$ordini = $dbo->fetchArray(str_replace('|dir|', 'uscita', $query));

if (!empty($ordini)) {
    $ordinato = sum(array_column($ordini, 'qta_ordinata'));
    echo '
                <table class="table table-bordered table-sm table-striped">
                    <thead>
                        <tr>
                            <th>'.tr('Descrizione').'</th>
                            <th class="text-right">'.$record['um'].'</th>
                        </tr>
                    </thead>

                    <tbody>';

    foreach ($ordini as $documento) {
        $numero = !empty($documento['numero_esterno']) ? $documento['numero_esterno'] : $documento['numero'];
        $qta = $documento['qta_ordinata'];

        echo '
                    <tr>
                        <td>
                            <small>
                                '.Modules::link('Ordini fornitore', $documento['id'], tr('Ordine num. _NUM_ del _DATE_', [
            '_NUM_' => $numero,
            '_DATE_' => dateFormat($documento['data']),
        ])).'
                                </small>
                        </td>
                        <td class="text-right">
                            <small>'.numberFormat($qta, 'qta').'</small>
                        </td>
                    </tr>';
    }

    echo '
                    <tr>
                        <td class="text-right">
                            <small><b>'.tr('Totale').'</b></small>
                        </td>
                        <td class="text-right">
                            <small>'.numberFormat($ordinato, 'qta').'</small>
                        </td>
                    </tr>

                </table>';
} else {
    echo '
                <p>'.tr('Nessun ordine fornitore con quantità da evadere individuato').'.</p>';
}

echo '
			</div>
		</div>
	</div>';

/**
 ** Da ordinare.
 */
$qta_presente = $articolo->qta > 0 ? $articolo->qta : 0;
$diff = ($qta_presente - $impegnato + $ordinato) * -1;
$da_ordinare = (($diff <= 0) ? 0 : $diff);

echo '
	<div class="col-md-3">
		<div class="card card-primary">
			<div class="card-header">
				<h3 class="card-title">'.tr('Da ordinare').'<span class="tip pull-right" title="'.tr('Quantità richieste dal cliente meno le quantità già ordinate.').'">
                <i class="fa fa-question-circle-o"></i></span></h3>
			</div>
			<div class="card-body">
              <div class="row">
                 <div class="col-md-12 text-center" style="font-size:35pt;">
                       '.numberFormat($da_ordinare, 'qta').' '.$articolo->um.'
			       </div>
			   </div>
			</div>
		</div>
	</div>';

/**
 ** Disponibile.
 */
$disponibile = $qta_presente - $impegnato;
echo '
	<div class="col-md-3">
		<div class="card card-primary">
			<div class="card-header">
				<h3 class="card-title">'.tr('Disponibile').'<span class="tip pull-right" title="'.tr('Quantità disponibili nel magazzino.').'">
                <i class="fa fa-question-circle-o"></i></span></h3>
			</div>
			<div class="card-body">

              <div class="row">
                 <div class="col-md-12 text-center" style="font-size:35pt;">
                       '.numberFormat($disponibile, 'qta').' '.$articolo->um.'
			       </div>
			   </div>

			</div>
		</div>
	</div>
</div>';

$sedi = $dbo->fetchArray('(SELECT "0" AS id, IF(indirizzo!=\'\', CONCAT_WS(" - ", "'.tr('Sede legale').'", CONCAT(citta, \' (\', indirizzo, \')\')), CONCAT_WS(" - ", "'.tr('Sede legale').'", citta)) AS nomesede FROM an_anagrafiche WHERE idanagrafica = '.prepare(setting('Azienda predefinita')).') UNION (SELECT id, IF(indirizzo!=\'\',CONCAT_WS(" - ", nomesede, CONCAT(citta, \' (\', indirizzo, \')\')), CONCAT_WS(" - ", nomesede, citta )) AS nomesede FROM an_sedi WHERE idanagrafica='.prepare(setting('Azienda predefinita')).')');
$giacenze = $articolo->getGiacenze();

echo '
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">'.tr('Giacenze').'</h3>
            </div>

            <div class="card-body">
                <table class="table table-striped table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>'.tr('Sede').'</th>
                            <th width="20%" class="text-center">'.tr('Q.tà').'</th>
                            <th width="5%" class="text-center">#</th>
                        </tr>
                    </thead>

                    <tbody>';

foreach ($sedi as $sede) {
    echo '
                        <tr>
                            <td>'.$sede['nomesede'].'</td>
                            <td class="text-right">'.numberFormat($giacenze[$sede['id']][0], 'qta').' '.$articolo->um.'</td>
                            <td class="text-center">
                                <a class="btn btn-xs btn-info" title="Dettagli" onclick="getDettagli('.$sede['id'].');">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>';
}

echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';

echo '
<script>

function getDettagli(idsede) {
    // Apertura modal
    openModal("'.tr('Dettagli').'", "'.$rootdir.'/modules/articoli/plugins/dettagli_giacenze.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&idsede=" + idsede );
}

</script>';
