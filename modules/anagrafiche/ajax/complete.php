<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'get_sedi':
        $idanagrafica = get('idanagrafica');
        $q = "SELECT id, CONCAT_WS( ' - ', nomesede, citta ) AS descrizione FROM an_sedi WHERE idanagrafica='".$idanagrafica."' ".Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY id';
        $rs = $dbo->fetchArray($q);
        $n = sizeof($rs);

        for ($i = 0; $i < $n; ++$i) {
            echo html_entity_decode($rs[$i]['id'].':'.$rs[$i]['descrizione']);
            if (($i + 1) < $n) {
                echo '|';
            }
        }
        break;

    // Elenco sedi con <option>
    case 'get_sedi_select':
        $idanagrafica = get('idanagrafica');
        $q = "SELECT id, CONCAT_WS( ' - ', nomesede, citta ) AS descrizione FROM an_sedi WHERE idanagrafica='".$idanagrafica."' ".Modules::getAdditionalsQuery('Anagrafiche').' ORDER BY id';
        $rs = $dbo->fetchArray($q);
        $n = sizeof($rs);

        echo "<option value=\"-1\">- Nessuna -</option>\n";
        echo "<option value=\"0\">Sede legale</option>\n";

        for ($i = 0; $i < $n; ++$i) {
            echo '<option value="'.$rs[$i]['id'].'">'.$rs[$i]['descrizione']."</option>\n";
        }
        break;
}
