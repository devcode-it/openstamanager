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

    // Elenco e-mail
    case 'get_email':
        $id_anagrafica = get('id_anagrafica');

        if (!empty($id_anagrafica)) {
            $where = 'AND idanagrafica = '.prepare($id_anagrafica);
        }

        $results = [];

        // Tutti i referenti per questo cliente
        $q = "SELECT DISTINCT(email), idanagrafica, nome AS ragione_sociale FROM an_referenti WHERE email != '' ".$where.' ORDER BY idanagrafica';

        $rs = $dbo->fetchArray($q);
        foreach ($rs as $r) {
            $results[] = [
                'value' => $r['email'],
                'label' => $r['ragione_sociale'].' <'.$r['email'].'>',
            ];
        }

         // Tutti le sedi per questo cliente
         $q = "SELECT DISTINCT(email), id AS idanagrafica, nomesede AS ragione_sociale FROM an_sedi WHERE email != '' ".$where.' ORDER BY id';

         $rs = $dbo->fetchArray($q);
         foreach ($rs as $r) {
             $results[] = [
                 'value' => $r['email'],
                 'label' => $r['ragione_sociale'].' <'.$r['email'].'>',
             ];
         }

        // Tutti gli agenti
        $q = "SELECT DISTINCT(email), ragione_sociale, an_anagrafiche.idanagrafica FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE idtipoanagrafica = (SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Agente') AND email != '' ORDER BY idanagrafica";

        $rs = $dbo->fetchArray($q);
        foreach ($rs as $r) {
            $results[] = [
                'value' => $r['email'],
                'label' => $r['ragione_sociale'].' <'.$r['email'].'>',
            ];
        }

        // Email del cliente
        $query = "SELECT DISTINCT(email) AS email, ragione_sociale, idanagrafica FROM an_anagrafiche WHERE email != '' ".$where;
        // Se type pec, propongo anche la pec
        if (get('type') == 'pec') {
            $query .= " UNION SELECT DISTINCT(pec), ragione_sociale, idanagrafica FROM an_anagrafiche WHERE email != '' ".$where;
        }
        $query .= ' ORDER BY idanagrafica';

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            $results[] = [
                'value' => $r['email'],
                'label' => $r['ragione_sociale'].' <'.$r['email'].'>',
            ];
        }

        echo json_encode($results);

        break;

    case 'get_mansioni':
        $q = 'SELECT DISTINCT mansione FROM an_referenti';
        $rs = $dbo->fetchArray($q);
        $n = sizeof($rs);

        for ($i = 0; $i < $n; ++$i) {
            echo html_entity_decode($rs[$i]['mansione']);
            if (($i + 1) < $n) {
                echo '|';
            }
        }
        break;
}
