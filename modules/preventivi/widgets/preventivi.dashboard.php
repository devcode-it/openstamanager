<?php
    include_once __DIR__.'/../../../core.php';

    $rs = $dbo->fetchArray("SELECT *, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_preventivi.idanagrafica) AS ragione_sociale FROM co_preventivi WHERE id_stato=(SELECT id FROM co_statipreventivi WHERE descrizione='In lavorazione') ORDER BY data_conclusione ASC");

    if (sizeof($rs) > 0) {
        echo "<table class='table table-hover'>\n";
        echo "<tr><th width='70%'>Preventivo</th>\n";
        echo "<th width='15%'>Data inizio</th>\n";
        echo "<th width='15%'>Data conclusione</th></tr>\n";

        for ($i = 0; $i < sizeof($rs); ++$i) {
            $data_accettazione = date('d/m/Y', strtotime($rs[$i]['data_accettazione']));
            if ($data_accettazione == '01/01/1970') {
                $data_accettazione = '';
            }

            $data_conclusione = date('d/m/Y', strtotime($rs[$i]['data_conclusione']));
            if ($data_conclusione == '01/01/1970') {
                $data_conclusione = '';
            }

            if (strtotime($rs[$i]['data_conclusione']) < strtotime(date('Y-m-d')) && $data_conclusione != '') {
                $attr = ' class="danger"';
            } else {
                $attr = '';
            }

            echo '<tr '.$attr.'><td><a href="'.$rootdir.'/editor.php?id_module='.Modules::get('Preventivi')['id'].'&id_record='.$rs[$i]['id'].'">'.$rs[$i]['nome']."</a><br><small class='help-block'>".$rs[$i]['ragione_sociale']."</small></td>\n";
            echo "<td $attr>".$data_accettazione."</td>\n";
            echo "<td $attr>".$data_conclusione."</td></tr>\n";
        }

        echo "</table>\n";
    } else {
        echo "<p>Non ci sono preventivi in lavorazione.</p>\n";
    }
