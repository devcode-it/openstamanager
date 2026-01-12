<?php

include_once __DIR__.'/../../../core.php';

use Models\PrintTemplate;

$idautomezzo = get('id_record');

// Recupero i dati dell'automezzo
$automezzo = $dbo->fetchOne('SELECT * FROM an_sedi WHERE id='.prepare($idautomezzo));

if (empty($automezzo)) {
    echo '<p>'.tr('Automezzo non trovato').'</p>';
    exit;
}

// Recupero ID stampa
$id_print = PrintTemplate::where('name', 'Registro viaggio')->first()->id;

// Recupero il range di date dei viaggi
$date_range = $dbo->fetchOne('SELECT MIN(data_inizio) as min_date, MAX(data_fine) as max_date FROM an_automezzi_viaggi WHERE idsede='.prepare($idautomezzo));

$data_inizio_default = $_SESSION['period_start'];
$data_fine_default = $_SESSION['period_end'];

?>

<form action="" method="post" id="form-stampa" target="_blank">
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> <strong><?php echo tr('Automezzo'); ?>:</strong> <?php echo $automezzo['nome'].' - '.$automezzo['targa']; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "date", "label": "<?php echo tr('Data inizio'); ?>", "name": "data_inizio", "required": 1, "value": "<?php echo $data_inizio_default; ?>" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "date", "label": "<?php echo tr('Data fine'); ?>", "name": "data_fine", "required": 1, "value": "<?php echo $data_fine_default; ?>" ]}
        </div>
    </div>

    <div class="modal-footer">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-print"></i> <?php echo tr('Stampa registro'); ?>
            </button>
        </div>
    </div>
</form>

<script type="text/javascript">
    $(document).ready(function() {
        $('#form-stampa').on('submit', function(e) {
            e.preventDefault();
            
            var data_inizio = $('input[name="data_inizio"]').val();
            var data_fine = $('input[name="data_fine"]').val();
            
            if (!data_inizio || !data_fine) {
                alert('<?php echo tr('Inserire data inizio e data fine'); ?>');
                return false;
            }
            
            // Apro la stampa in una nuova finestra
            var url = '<?php echo base_path_osm(); ?>/pdfgen.php?id_print=<?php echo $id_print; ?>&id_record=<?php echo $idautomezzo; ?>&data_inizio=' + data_inizio + '&data_fine=' + data_fine;
            window.open(url, '_blank');
            
            // Chiudo il modale
            $('#modals > div').modal('hide');
        });
    });
</script>

