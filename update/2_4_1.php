<?php

/*
* Inserimento valori di default
*/

// Permessi di default delle viste
$gruppi = $database->fetchArray('SELECT `id` FROM `zz_groups`');
$results = $database->fetchArray('SELECT `id` FROM `zz_views` WHERE `id` NOT IN (SELECT `id_vista` FROM `zz_group_view`)');

$array = [];
foreach ($results as $result) {
    foreach ($gruppi as $gruppo) {
        $array[] = [
            'id_gruppo' => $gruppo['id'],
            'id_vista' => $result['id'],
        ];
    }
}
if (!empty($array)) {
    $database->insert('zz_group_view', $array);
}

// Script per aggiornare le date dei movimenti con le date dei documenti

$rs = $dbo->fetchArray('SELECT * FROM mg_movimenti');

for ($i = 0; $i < sizeof($rs); ++$i) {
    if ($rs[$i]['idintervento'] != '') {
        $rs_data = $dbo->fetchArray("SELECT IFNULL(MAX(orario_fine), data_richiesta) AS data FROM in_interventi LEFT JOIN in_interventi_tecnici ON in_interventi.id=in_interventi_tecnici.idintervento WHERE in_interventi.id='".$rs[$i]['idintervento']."'");
        $data = $rs_data[0]['data'];
        $dbo->query("UPDATE mg_movimenti SET data='".$data."' WHERE id='".$rs[$i]['id']."'");
    } elseif ($rs[$i]['idddt'] != '0') {
        $rs_data = $dbo->fetchArray("SELECT data FROM dt_ddt WHERE id='".$rs[$i]['idddt']."'");
        $data = $rs_data[0]['data'];
        $dbo->query("UPDATE mg_movimenti SET data='".$data."' WHERE id='".$rs[$i]['id']."'");
    } elseif ($rs[$i]['iddocumento'] != '0') {
        $rs_data = $dbo->fetchArray("SELECT data FROM co_documenti WHERE id='".$rs[$i]['iddocumento']."'");
        $data = $rs_data[0]['data'];
    }
}
