<?php

include_once __DIR__.'/../../core.php';

if (get('anteprima') !== null) {
    // Lettura dati intervento
    $query = 'SELECT codice FROM in_interventi WHERE id='.prepare($id_record);
    $rs = $dbo->fetchArray($query);

    if (empty($rs)) {
        echo tr('Intervento inesistente!');
        exit();
    }

    // Gestione della stampa
    $directory = $docroot.'/files/interventi/';
    $id_print = setting('Stampa per anteprima e firma');

    // HTML per la visualizzazione
    echo '
<div id="preview">
    <button type="button" class="btn btn-success btn-block btn-lg" id="firma">
        <i class="fa fa-pencil"></i> '.tr('Firma').'
    </button>
    <br>

    <div class="clearfix"></div>

    <iframe src="'.Prints::getPreviewLink($id_print, $id_record, $directory).'" frameborder="0" width="100%" height="550"></iframe>
</div>';
}

?>
<form action="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>" method="post" id="form-firma" class="hide">
    <input type="hidden" name="op" value="firma">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "<?php echo tr('Nome e cognome'); ?>", "name": "firma_nome", "required": 1 ]}
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div id="signature-pad" class="signature-pad">
                <canvas id="canvas" onselectstart="return false"></canvas>
                <input type="hidden" name="firma_base64" id="firma_base64" value="">
            </div>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-6">
            <button type="button" class="btn btn-danger" data-action="clear">
                <i class="fa fa-eraser"></i> <?php echo tr('Cancella firma'); ?>
            </button>
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-success pull-right" data-action="save">
                <i class="fa fa-check"></i> <?php echo tr('Salva firma'); ?>
            </button>
        </div>
    </div>

</form>
<div class="clearfix"></div>

<script type="text/javascript">
    $(document).ready( function(){
        $('#firma').on('click', function(){
            $('#preview').addClass('hide');

            $('#form-firma').removeClass('hide');
        })

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
