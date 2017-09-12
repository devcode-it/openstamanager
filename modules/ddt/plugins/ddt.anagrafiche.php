<?php

include_once __DIR__.'/../../../core.php';

$rsddt = $dbo->fetchArray('SELECT *, dt_ddt.note, dt_ddt.idpagamento, dt_ddt.id AS idddt, dt_statiddt.descrizione AS `stato`, dt_tipiddt.descrizione AS `descrizione_tipodoc` FROM ((dt_ddt LEFT OUTER JOIN dt_statiddt ON dt_ddt.idstatoddt=dt_statiddt.id) INNER JOIN an_anagrafiche ON dt_ddt.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN dt_tipiddt ON dt_ddt.idtipoddt=dt_tipiddt.id LEFT OUTER JOIN dt_righe_ddt ON dt_ddt.id=dt_righe_ddt.idddt WHERE an_anagrafiche.idanagrafica='.prepare($id_record));

if (!empty($rsddt)) {
    echo '
<table id="tabella" class="table table-striped table-bordered display nowrap datatables" cellspacing="0" width="100%">

    <thead>
        <tr>
            <th >#</th>
            <th>'.tr('Numero').'</th>
            <th>'.tr('Data').'</th>
            <th>'.tr('Articolo').'</th>
            <th>'.tr('Qtà').'</th>
        </tr>
    </thead>

    <tfoot>
        <tr>
            <th>#</th>
            <th>'.tr('Numero').'</th>
            <th>'.tr('Data').'</th>
            <th>'.tr('Articolo').'</th>
            <th>'.tr('Qtà').'</th>
        </tr>
    </tfoot>

    <tbody>';

    foreach ($rsddt as $key => $r) {
        echo '
        <tr>
            <td>
                <span>'.($key + 1).'</span>
            </td>
            <td>
                '.Modules::link('Ddt di vendita', $r['idddt'], !empty($r['numero_esterno']) ? $r['numero_esterno'] : $r['numero']).'
            </td>
            <td>
                <span>'.(!empty($r['data']) ? Translator::dateToLocale($r['data']) : '').'</span>
            </td>
            <td>
                <span>'.$r['descrizione'].'</span>
            </td>
            <td>
                <span>'.Translator::numberToLocale($r['qta']).' '.$r['um'].'</span>
            </td>
        </tr>';
    } ?>


    </tbody>
</table>

<?php

} else {
    echo '
<div class="alert alert-info" role="alert">
    <i class="fa fa-info-circle"></i> '.tr('Nessun ddt di vendita per questa anagrafica').'.
</div>';
}
