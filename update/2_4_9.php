<?php

include_once DOCROOT.'/modules/fatture/modutil.php';
include_once DOCROOT.'/modules/interventi/modutil.php';

// Aggiornamento sconti incodizionati per Interventi
$id_iva = setting('Iva predefinita');
$iva = $dbo->fetchOne('SELECT * FROM co_iva WHERE id='.prepare($id_iva));

$interventi = $dbo->fetchArray('SELECT * FROM in_interventi WHERE sconto_globale != 0 AND sconto_globale != NULL');
foreach ($interventi as $intervento) {
    $costi = get_costi_intervento($intervento['id']);
    $sconto_globale = $costi['sconto_globale'];

    if ($intervento['tipo_sconto_globale'] == 'PRC') {
        $descrizione = $sconto_globale >= 0 ? tr('Sconto percentuale') : tr('Maggiorazione percentuale');
        $descrizione .= ' '.Translator::numberToLocale($intervento['sconto_globale']).'%';
    } else {
        $descrizione = $sconto_globale >= 0 ? tr('Sconto unitario') : tr('Maggiorazione unitaria');
    }

    $valore_iva = $sconto_globale * $iva['percentuale'] / 100;

    $dbo->insert('in_righe_interventi', [
        'idintervento' => $intervento['id'],
        'descrizione' => $descrizione,
        'qta' => 1,
        'sconto' => $sconto_globale,
        'sconto_unitario' => $sconto_globale,
        'tipo_sconto' => 'UNT',
        'is_sconto' => 1,
        'idiva' => $id_iva['id'],
        'desc_iva' => $iva['descrizione'],
        'iva' => $valore_iva,
    ]);
}

$dbo->query('ALTER TABLE `in_interventi` DROP `sconto_globale`, DROP `tipo_sconto_globale`, DROP `tipo_sconto`');

// File e cartelle deprecate
$files = [
    'plugins/xml/AT_v1.0.xml',
    'plugins/xml/DT_v1.0.xml',
    'plugins/xml/EC_v1.0.xml',
    'plugins/xml/MC_v1.0.xml',
    'plugins/xml/MT_v1.0.xml',
    'plugins/xml/NE_v1.0.xml',
    'plugins/xml/NS_v1.0.xml',
    'plugins/xml/RC_v1.0.xml',
    'plugins/xml/SE_v1.0.xml',
    'plugins/exportFE/view.php',
    'plugins/exportFE/src/stylesheet-1.2.1.xsl',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);

//Calcolo la descrizione per il nuovo campo descrizione in scadenzario
$rs = $dbo->fetchArray('SELECT * FROM co_scadenziario');

for ($i = 0; $i < sizeof($rs); ++$i) {
    $dbo->query("UPDATE co_scadenziario SET descrizione=(SELECT CONCAT(co_tipidocumento.descrizione, CONCAT(' numero ', IF(numero_esterno!='', numero_esterno, numero))) FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='".$rs[$i]['iddocumento']."') WHERE co_scadenziario.id='".$rs[$i]['id']."'");
}
