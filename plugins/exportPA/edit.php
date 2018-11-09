<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

if (!empty($fattura_pa)) {
    $disabled = false;
    $generated = file_exists($upload_dir.'/'.$fattura_pa->getFilename());

    //Ulteriore controllo sulla data generazione file
    $rs_generated = $dbo->fetchArray("SELECT xml_generated_at FROM co_documenti WHERE id=".prepare($id_record));
    if(empty($rs_generated[0]['xml_generated_at'])){
        $generated = false;
    }

} else {
    echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i>
    <b>'.tr('Attenzione!').'</b> '.tr('Per generare la fattura elettronica è necessario che sia in stato "Emessa"').'.
</div>';

    $disabled = true;
    $generated = false;
}

// Campi obbligatori per l'anagrafica Azienda
$azienda = Plugins\ExportPA\FatturaElettronica::getAzienda();
$fields = [
    'piva' => 'Partita IVA',
    // 'codice_fiscale' => 'Codice Fiscale',
    'citta' => 'Città',
    'indirizzo' => 'Indirizzo',
    'cap' => 'C.A.P.',
    'nazione' => 'Nazione',
];

$missing = [];
foreach ($fields as $key => $name) {
    if (empty($azienda[$key])) {
        $missing[] = $name;
    }
}

if (!empty($missing)) {
    echo '
<div class="alert alert-warning">
    <p><i class="fa fa-warning"></i> '.tr("Prima di procedere alla generazione della fattura elettronica completa i seguenti campi dell'anagrafica Azienda: _FIELDS_", [
        '_FIELDS_' => '<b>'.implode(', ', $missing).'</b>',
    ]).'</p>
    <p>'.Modules::link('Anagrafiche', $azienda['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</p>
</div>';
}

// Campi obbligatori per l'anagrafica Cliente
$cliente = $dbo->fetchOne('SELECT *, (SELECT `iso2` FROM `an_nazioni` WHERE `an_nazioni`.`id` = `an_anagrafiche`.`id_nazione`) AS nazione FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare($record['idanagrafica']));
$fields = [
    // 'piva' => 'Partita IVA',
    // 'codice_fiscale' => 'Codice Fiscale',
    'citta' => 'Città',
    'indirizzo' => 'Indirizzo',
    'cap' => 'C.A.P.',
    'nazione' => 'Nazione',
    'pec' => 'PEC',
];

$missing = [];
foreach ($fields as $key => $name) {
    if (empty($cliente[$key])) {
        $missing[] = $name;
    }
}

if (!empty($missing)) {
    echo '
<div class="alert alert-warning">
    <p><i class="fa fa-warning"></i> '.tr("Prima di procedere alla generazione della fattura elettronica completa i seguenti campi dell'anagrafica Cliente: _FIELDS_", [
        '_FIELDS_' => '<b>'.implode(', ', $missing).'</b>',
    ]).'</p>
    <p>'.Modules::link('Anagrafiche', $record['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</p>
</div>';
}

echo '
<p>'.tr("Per effettuare la generazione dell'XML della fattura elettronica clicca sul pulsante _BTN_", [
    '_BTN_' => '<b>Genera</b>',
]).'. '.tr('Successivamente sarà possibile procedere alla visualizzazione e al download della fattura generata attraverso i pulsanti dedicati').'.</p>';

echo '
<div class="text-center">
    <form action="" method="post" role="form" style="display:inline-block" id="form-xml">
        <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
        <input type="hidden" name="id_record" value="'.$id_record.'">
        <input type="hidden" name="backto" value="record-edit">
        <input type="hidden" name="op" value="generate">

        <button id="genera" type="submit" class="btn btn-primary btn-lg '.($disabled ? 'disabled' : '').'" '.($disabled ? ' disabled' : null).'>
            <i class="fa fa-file"></i> '.tr('Genera').'
        </button>
    </form>';

echo '
    <i class="fa fa-arrow-right fa-fw text-muted"></i>

    <a href="'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'&op=download" class="btn btn-success btn-lg '.($generated ? '' : 'disabled').'" target="_blank" '.($generated ? '' : 'disabled').'>
        <i class="fa fa-download"></i> '.tr('Scarica').'
    </a>

    <i class="fa fa-arrow-right fa-fw text-muted"></i>';

echo '

    <a href="'.ROOTDIR.'/plugins/exportPA/view.php?id_record='.$id_record.'" class="btn btn-info btn-lg '.($generated ? '' : 'disabled').'" target="_blank" '.($generated ? '' : 'disabled').'>
        <i class="fa fa-eye"></i> '.tr('Visualizza').'
    </a>

</div>';

if($generated){
    echo '
<script>
    $("#genera").click(function(event){
        event.preventDefault();
        swal({
          title: "Sei sicuro?",
          text: "Sarà generata nuovamente la fattura elettronica",
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#30d64b",
          cancelButtonColor: "#d33",
          confirmButtonText: "Genera"
        }).then((result) => {
          if (result) {
            $("#form-xml").submit();
          }
      });
  });
</script>';
}
