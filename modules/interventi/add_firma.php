<?php

include_once __DIR__.'/../../core.php';
$module_name = 'Interventi';

if (get('anteprima') !== null) {
    // Lettura dati intervento
    $query = "SELECT id, codice, idanagrafica, (SELECT MIN(DATE_FORMAT(`orario_inizio`, '%d/%m/%Y')) FROM in_interventi_tecnici WHERE in_interventi_tecnici.`idintervento`=in_interventi.id ) AS data_inizio, (SELECT MAX(DATE_FORMAT(`orario_inizio`, '%d/%m/%Y')) FROM in_interventi_tecnici WHERE in_interventi_tecnici.`idintervento`=in_interventi.id )  AS data_fine FROM in_interventi WHERE in_interventi.id=".prepare($id_record);
    $rs = $dbo->fetchArray($query);

    if (empty($rs)) {
        echo tr('Intervento inesistente!');
        exit();
    }

    $idanagrafica = $rs[0]['idanagrafica'];
    $idcliente = $rs[0]['idanagrafica'];
    $data_intervento = $rs[0]['data_inizio'];

    // Gestione della stampa
    $rapportino_nome = sanitizeFilename('Rapportino'.$rs[0]['codice'].'.pdf');
    $filename = $docroot.'/files/interventi/'.$rapportino_nome;

    $_GET['idintervento'] = $id_record; // Fix temporaneo per la stampa
    $idintervento = $id_record; // Fix temporaneo per la stampa
    $ptype = 'interventi';

    require $docroot.'/pdfgen.php';

    // HTML per la visualizzazione
    echo '
<button type="button" class="btn btn-success btn-block btn-lg" id="firma" onclick="$(\'.canvas\').removeClass(\'hide\'); $(this).addClass(\'hide\'); $(\'#pdf\').addClass(\'hide\');">
    <i class="fa fa-pencil"></i> '.tr('Firma').'
</button>
<div class="clearfix"></div>';

    echo '<div class="hide" id="pdf">';

    if (isMobile()) {
        echo '<iframe src="'.$rootdir.'/assets/dist/viewerjs/#'.$rootdir.'/files/interventi/'.$rapportino_nome.'" allowfullscreen="" webkitallowfullscreen="" width="100%" height="550" ></iframe>';
    } else {
        echo  '<object data="'.$rootdir.'/files/interventi/'.$rapportino_nome.'#view=fitH&scrollbar=0&toolbar=0&navpanes=0" id ="rapportino_pdf"  type="application/pdf" width="100%">
        alt : <a href="'.$rootdir.'/files/interventi/'.$rapportino_nome.'" target="_blank">'.$rapportino_nome.'</a>
        <span>'.tr('Plugin PDF mancante').'</span>
    </object>';
    }

    echo '</div>';
}

?>
<form class="canvas" action="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>" method="post" id="form-firma">
    <input type="hidden" name="op" value="firma">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "<?php echo tr('Nome e cognome'); ?>", "name": "firma_nome", "required": 1 ]}
        </div>
    </div>

    <div id="signature-pad" class="signature-pad">
        <canvas id="canvas" onselectstart="return false"></canvas>
        <input type="hidden" name="firma_base64" id="firma_base64" value="">
    </div>


    <div class="btn-group pull-right">
        <button type="button" class="btn btn-danger" data-action="clear">
            <i class="fa fa-eraser"></i> <?php echo tr('Cancella firma'); ?>
        </button>
        <button type="submit" class="btn btn-success" data-action="save">
            <i class="fa fa-check"></i> <?php echo tr('Salva firma'); ?>
        </button>
    </div>

</form>
<div class="clearfix"></div>

<script type="text/javascript">
    $(document).ready( function(){
        $('button').removeClass('hide');
        $('#pdf').removeClass('hide');
        $('.canvas').addClass('hide');
        $('#firma').removeClass('hide');
        $('#rapportino_pdf').css('height', ($(window).height()-200));

        var wrapper = document.getElementById("signature-pad"),
            clearButton = document.querySelector("[data-action=clear]"),
            saveButton = document.querySelector("[data-action=save]"),
            canvas = document.getElementById("canvas");

        var signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255,255,255)'
        });

        function resizeCanvas() {
            image_data = signaturePad.toDataURL();

            var ratio =  Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();

            signaturePad.fromDataURL(image_data);
        }

        window.addEventListener("resize", resizeCanvas);
        $('#firma').click(resizeCanvas);

        clearButton.addEventListener("click", function (event) {
            signaturePad.clear();
        });

        saveButton.addEventListener("click", function (event) {
            if (signaturePad.isEmpty()) {
                alert("Please provide signature first.");
            } else {
                image_data = signaturePad.toDataURL("image/jpeg", 100);
                $('#firma_base64').val(image_data);
            }
        });
    });
</script>
