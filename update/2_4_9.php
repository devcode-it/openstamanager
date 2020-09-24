<?php

include_once base_dir().'/modules/fatture/modutil.php';

function get_costi_intervento_fix($id_intervento)
{
    $dbo = database();

    $decimals = setting('Cifre decimali per importi');

    $idiva = setting('Iva predefinita');
    $rs_iva = $dbo->fetchArray('SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));

    $tecnici = $dbo->fetchArray('SELECT

	COALESCE(SUM(
        ROUND(prezzo_ore_unitario_tecnico*ore, '.$decimals.')
    ), 0) AS manodopera_costo,
    COALESCE(SUM(
        ROUND(prezzo_ore_unitario*ore, '.$decimals.')
    ), 0) AS manodopera_addebito,
    COALESCE(SUM(
        ROUND(prezzo_ore_unitario*ore, '.$decimals.') - ROUND(sconto, '.$decimals.')
    ), 0) AS manodopera_scontato,


    COALESCE(SUM(
        ROUND(prezzo_dirittochiamata_tecnico, '.$decimals.')
    ), 0) AS dirittochiamata_costo,
    COALESCE(SUM(
        ROUND(prezzo_dirittochiamata, '.$decimals.')
    ), 0) AS dirittochiamata_addebito,
    COALESCE(SUM(
        ROUND(prezzo_dirittochiamata, '.$decimals.')
    ), 0) AS dirittochiamata_scontato,

    COALESCE(SUM(
        ROUND(prezzo_km_consuntivo_tecnico, '.$decimals.')
    ), 0) AS viaggio_costo,
    COALESCE(SUM(
        ROUND(prezzo_km_consuntivo, '.$decimals.')
    ), 0) viaggio_addebito,
    COALESCE(SUM(
        ROUND(prezzo_km_consuntivo, '.$decimals.') - ROUND(scontokm, '.$decimals.')
    ), 0) AS viaggio_scontato

    FROM in_interventi_tecnici WHERE idintervento='.prepare($id_intervento));

    $articoli = $dbo->fetchArray('SELECT
    COALESCE(SUM(
        ROUND(prezzo_acquisto, '.$decimals.') * ROUND(qta, '.$decimals.')
    ), 0) AS ricambi_costo,
    COALESCE(SUM(
        ROUND(prezzo_vendita, '.$decimals.') * ROUND(qta, '.$decimals.')
    ), 0) AS ricambi_addebito,
    COALESCE(SUM(
        ROUND(prezzo_vendita, '.$decimals.') * ROUND(qta, '.$decimals.') - ROUND(sconto, '.$decimals.')
    ), 0) AS ricambi_scontato,
    ROUND(
        (SELECT percentuale FROM co_iva WHERE co_iva.id=mg_articoli_interventi.idiva), '.$decimals.'
        ) AS ricambi_iva

    FROM mg_articoli_interventi WHERE idintervento='.prepare($id_intervento));

    $altro = $dbo->fetchArray('SELECT
    COALESCE(SUM(
        ROUND(prezzo_acquisto, '.$decimals.') * ROUND(qta, '.$decimals.')
    ), 0) AS altro_costo,
    COALESCE(SUM(
        ROUND(prezzo_vendita, '.$decimals.') * ROUND(qta, '.$decimals.')
    ), 0) AS altro_addebito,
    COALESCE(SUM(
        ROUND(prezzo_vendita, '.$decimals.') * ROUND(qta, '.$decimals.') - ROUND(sconto, '.$decimals.')
    ), 0) AS altro_scontato,
    ROUND(
        (SELECT percentuale FROM co_iva WHERE co_iva.id=in_righe_interventi.idiva), '.$decimals.'
        ) AS altro_iva

    FROM in_righe_interventi WHERE idintervento='.prepare($id_intervento));

    $result = array_merge($tecnici[0], $articoli[0], $altro[0]);

    $result['totale_costo'] = sum([
        $result['manodopera_costo'],
        $result['dirittochiamata_costo'],
        $result['viaggio_costo'],
        $result['ricambi_costo'],
        $result['altro_costo'],
    ]);

    $result['totale_addebito'] = sum([
        $result['manodopera_addebito'],
        $result['dirittochiamata_addebito'],
        $result['viaggio_addebito'],
        $result['ricambi_addebito'],
        $result['altro_addebito'],
    ]);

    $result['totale_scontato'] = sum([
        $result['manodopera_scontato'],
        $result['dirittochiamata_scontato'],
        $result['viaggio_scontato'],
        $result['ricambi_scontato'],
        $result['altro_scontato'],
    ]);

    $result['iva_costo'] = sum([
        $result['manodopera_costo'] * $rs_iva[0]['percentuale'] / 100,
        $result['dirittochiamata_costo'] * $rs_iva[0]['percentuale'] / 100,
        $result['viaggio_costo'] * $rs_iva[0]['percentuale'] / 100,
        $result['ricambi_costo'] * $result['ricambi_iva'] / 100,
        $result['altro_costo'] * $result['altro_iva'] / 100,
    ]);

    $result['iva_addebito'] = sum([
        $result['manodopera_addebito'] * $rs_iva[0]['percentuale'] / 100,
        $result['dirittochiamata_addebito'] * $rs_iva[0]['percentuale'] / 100,
        $result['viaggio_addebito'] * $rs_iva[0]['percentuale'] / 100,
        $result['ricambi_addebito'] * $result['ricambi_iva'] / 100,
        $result['altro_addebito'] * $result['altro_iva'] / 100,
    ]);

    $result['iva_totale'] = sum([
        $result['manodopera_scontato'] * $rs_iva[0]['percentuale'] / 100,
        $result['dirittochiamata_scontato'] * $rs_iva[0]['percentuale'] / 100,
        $result['viaggio_scontato'] * $rs_iva[0]['percentuale'] / 100,
        $result['ricambi_scontato'] * $result['ricambi_iva'] / 100,
        $result['altro_scontato'] * $result['altro_iva'] / 100,
    ]);

    $result['totaleivato_costo'] = sum([
        $result['manodopera_costo'] + ($result['manodopera_costo'] * $rs_iva[0]['percentuale'] / 100),
        $result['dirittochiamata_costo'] + ($result['dirittochiamata_costo'] * $rs_iva[0]['percentuale'] / 100),
        $result['viaggio_costo'] + ($result['viaggio_costo'] * $rs_iva[0]['percentuale'] / 100),
        $result['ricambi_costo'] + ($result['ricambi_costo'] * $result['ricambi_iva'] / 100),
        $result['altro_costo'] + ($result['altro_costo'] * $result['altro_iva'] / 100),
    ]);

    $result['totaleivato_addebito'] = sum([
        $result['manodopera_addebito'] + ($result['manodopera_addebito'] * $rs_iva[0]['percentuale'] / 100),
        $result['dirittochiamata_addebito'] + ($result['dirittochiamata_addebito'] * $rs_iva[0]['percentuale'] / 100),
        $result['viaggio_addebito'] + ($result['viaggio_addebito'] * $rs_iva[0]['percentuale'] / 100),
        $result['ricambi_addebito'] + ($result['ricambi_addebito'] * $result['ricambi_iva'] / 100),
        $result['altro_addebito'] + ($result['altro_addebito'] * $result['altro_iva'] / 100),
    ]);

    $result['totale'] = sum([
        $result['manodopera_scontato'] + ($result['manodopera_scontato'] * $rs_iva[0]['percentuale'] / 100),
        $result['dirittochiamata_scontato'] + ($result['dirittochiamata_scontato'] * $rs_iva[0]['percentuale'] / 100),
        $result['viaggio_scontato'] + ($result['viaggio_scontato'] * $rs_iva[0]['percentuale'] / 100),
        $result['ricambi_scontato'] + ($result['ricambi_scontato'] * $result['ricambi_iva'] / 100),
        $result['altro_scontato'] + ($result['altro_scontato'] * $result['altro_iva'] / 100),
    ]);

    // Calcolo dello sconto incondizionato
    $sconto = $dbo->fetchArray('SELECT sconto_globale, tipo_sconto_globale FROM in_interventi WHERE id='.prepare($id_intervento))[0];
    $result['sconto_globale'] = ($sconto['tipo_sconto_globale'] == 'PRC') ? $result['totale_scontato'] * $sconto['sconto_globale'] / 100 : $sconto['sconto_globale'];
    $result['sconto_globale'] = round($result['sconto_globale'], $decimals);

    $result['totale_scontato'] = sum($result['totale_scontato'], -$result['sconto_globale']);
    $result['iva_totale'] = sum($result['iva_totale'], -($result['sconto_globale'] * $rs_iva[0]['percentuale'] / 100));
    $result['totale'] = sum($result['totale'], -($result['sconto_globale'] + ($result['sconto_globale'] * $rs_iva[0]['percentuale'] / 100)));

    return $result;
}

// Aggiornamento sconti incodizionati per Interventi
$id_iva = setting('Iva predefinita');
$iva = $dbo->fetchOne('SELECT * FROM co_iva WHERE id='.prepare($id_iva));

$interventi = $dbo->fetchArray('SELECT * FROM in_interventi WHERE sconto_globale != 0 AND sconto_globale != NULL');
foreach ($interventi as $intervento) {
    $costi = get_costi_intervento_fix($intervento['id']);
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
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

// Calcolo la descrizione per il nuovo campo descrizione in scadenzario
$rs = $dbo->fetchArray('SELECT * FROM co_scadenziario');

for ($i = 0; $i < sizeof($rs); ++$i) {
    $dbo->query("UPDATE co_scadenziario SET descrizione=(SELECT CONCAT(co_tipidocumento.descrizione, CONCAT(' numero ', IF(numero_esterno!='', numero_esterno, numero))) FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id='".$rs[$i]['iddocumento']."') WHERE co_scadenziario.id='".$rs[$i]['id']."'");
}
