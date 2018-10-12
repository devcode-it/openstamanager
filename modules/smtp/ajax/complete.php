<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
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

        // Tutti gli agenti
        $q = "SELECT DISTINCT(email), ragione_sociale, an_anagrafiche.idanagrafica FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE idtipoanagrafica = (SELECT id FROM an_tipianagrafiche WHERE descrizione='Agente') AND email != '' ORDER BY idanagrafica";

        $rs = $dbo->fetchArray($q);
        foreach ($rs as $r) {
            $results[] = [
                'value' => $r['email'],
                'label' => $r['ragione_sociale'].' <'.$r['email'].'>',
            ];
        }

        // Email del cliente
        $query = "SELECT DISTINCT(pec) AS email, ragione_sociale, idanagrafica FROM an_anagrafiche WHERE email != '' ".$where;
        if (empty(get('type'))) {
            $query .= " UNION SELECT DISTINCT(email), ragione_sociale, idanagrafica FROM an_anagrafiche WHERE email != '' ".$where;
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
}
