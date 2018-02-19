<?php

include_once __DIR__.'/../../core.php';

// Articolo
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Articolo').'", "name": "idarticolo", "required": 1, "value": "'.$result['idarticolo'].'", "ajax-source": "articoli" ]}
        </div>
    </div>';

echo App::internalLoad('riga.php', $result, $options);

// Informazioni aggiuntive
if ($module['name'] != 'Contratti' && $module['name'] != 'Preventivi') {
    echo '
    <div class="row" id="prezzi_articolo">
        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-sm btn-info btn-block disabled" onclick="$(\'#prezzi\').toggleClass(\'hide\'); $(\'#prezzi\').load(\''.ROOTDIR."/ajax_complete.php?module=Articoli&op=getprezzi&idarticolo=' + $('#idarticolo option:selected').val() + '&idanagrafica=".$options['idanagrafica'].'\');" disabled>
                <i class="fa fa-search"></i> '.tr('Visualizza ultimi prezzi (cliente)').'
            </button>
            <div id="prezzi" class="hide"></div>
        </div>

        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-sm btn-info btn-block disabled" onclick="$(\'#prezziacquisto\').toggleClass(\'hide\'); $(\'#prezziacquisto\').load(\''.ROOTDIR."/ajax_complete.php?module=Articoli&op=getprezziacquisto&idarticolo=' + $('#idarticolo option:selected').val() + '&idanagrafica=".$options['idanagrafica'].'\');" disabled>
                <i class="fa fa-search"></i> '.tr('Visualizza ultimi prezzi (acquisto)').'
            </button>
            <div id="prezziacquisto" class="hide"></div>
        </div>

        <div class="col-md-4 text-center">
            <button type="button" class="btn btn-sm btn-info btn-block disabled" onclick="$(\'#prezzivendita\').toggleClass(\'hide\'); $(\'#prezzivendita\').load(\''.ROOTDIR."/ajax_complete.php?module=Articoli&op=getprezzivendita&idarticolo=' + $('#idarticolo option:selected').val() + '&idanagrafica=".$options['idanagrafica'].'\');" disabled>
                <i class="fa fa-search"></i> '.tr('Visualizza ultimi prezzi (vendita)').'
            </button>
            <div id="prezzivendita" class="hide"></div>
        </div>
    </div>
    <br>';
}

echo '
    <script>
    $(document).ready(function () {
        $("#idarticolo").on("change", function(){
            // Autoimpostazione dei valori relativi
            if ($(this).val()) {
                session_set("superselect,idarticolo", $(this).val(), 0);
                $data = $(this).selectData();

                $("#prezzo").val($data.prezzo_'.($options['dir'] == 'entrata' ? 'vendita' : 'acquisto').');
                $("#descrizione_riga").val($data.descrizione);
                $("#idiva").selectSet($data.idiva_vendita, $data.iva_vendita);
                $("#um").selectSetNew($data.um, $data.um);
            }';

if ($module['name'] != 'Contratti' && $module['name'] != 'Preventivi') {
    echo '

            // Operazioni sui prezzi in fondo alla pagina
            $("#prezzi_articolo button").attr("disabled", !$(this).val());

            if ($(this).val()) {
                $("#prezzi_articolo button").removeClass("disabled");
            } else {
                $("#prezzi_articolo button").addClass("disabled");
            }

            $("#prezzi").html("");
            $("#prezzivendita").html("");
            $("#prezziacquisto").html("");';
}

echo '
        });
    });
    </script>';
