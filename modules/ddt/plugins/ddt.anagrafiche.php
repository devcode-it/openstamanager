<?php

include_once __DIR__.'/../../../core.php';

$rsddt = $dbo->fetchArray('SELECT *, dt_ddt.note, dt_ddt.idpagamento, dt_ddt.id AS idddt, dt_statiddt.descrizione AS `stato`, dt_tipiddt.descrizione AS `descrizione_tipodoc` FROM ((dt_ddt LEFT OUTER JOIN dt_statiddt ON dt_ddt.idstatoddt=dt_statiddt.id) INNER JOIN an_anagrafiche ON dt_ddt.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN dt_tipiddt ON dt_ddt.idtipoddt=dt_tipiddt.id LEFT OUTER JOIN dt_righe_ddt ON dt_ddt.id=dt_righe_ddt.idddt WHERE an_anagrafiche.idanagrafica='.prepare($id_record));

if (!empty($rsddt)) {
    echo '
<table id="tabella" class="table table-striped table-bordered display nowrap datatables" cellspacing="0" width="100%">

    <thead>
        <tr>
            <th >#</th>
            <th>'._('Numero').'</th>
            <th>'._('Data').'</th>
            <th>'._('Articolo').'</th>
            <th>'._('Qtà').'</th>
        </tr>
    </thead>

    <tfoot>
        <tr>
            <th>#</th>
            <th>'._('Numero').'</th>
            <th>'._('Data').'</th>
            <th>'._('Articolo').'</th>
            <th>'._('Qtà').'</th>
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
                '.Modules::link('Ddt di vendita', $rsddt[$i]['idddt'], $rsddt[$i]['numero_esterno']).'
            </td>
            <td>
                <span>'.Translator::dateToLocale($rsddt[$i]['data']).'</span>
            </td>
            <td>
                <span>'.$rsddt[$i]['descrizione'].'</span>
            </td>
            <td>
                <span>'.Translator::numberToLocale($rsddt[$i]['qta']).' '.$rsddt[$i]['um'].'</span>
            </td>
        </tr>';
    } ?>


    </tbody>
</table>

<script>

function attivaricerca (){
    // Setup - add a text input to each footer cell
    $('#tabella thead th').each( function () {
        var title = $(this).text();
        $(this).html( '<b>'+title+'</b><br><input type="text" class="filter form-control" placeholder="Filtra... '+title+'" />' );
    });

    table.columns.adjust().draw();

    // Apply the search
    table.columns().eq(0).each(function(colIdx) {
        $('input', table.column(colIdx).header()).on('keyup change', function() {
            table
                .column(colIdx)
                .search(this.value)
                .draw();
        });

        $('input', table.column(colIdx).header()).on('click', function(e) {
            e.stopPropagation();
        });
    });
}

$(document).ready(function() {
    setTimeout(function(){
        attivaricerca()
    }, 1000);
});

</script>

<?php

} else {
    echo '
<div class="alert alert-info" role="alert">
    <i class="fa fa-info-circle"></i> '._('Nessun ddt di vendita per questa anagrafica').'.
</div>';
}
