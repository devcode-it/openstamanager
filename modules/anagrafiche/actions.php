<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        // Leggo tutti i valori passati dal POST e li salvo in un array
        $dbo->update('an_anagrafiche', [
            'ragione_sociale' => $post['ragione_sociale'],
            'tipo' => $post['tipo'],
            'piva' => $post['piva'],
            'codice_fiscale' => $post['codice_fiscale'],
            'data_nascita' => $post['data_nascita'],
            'luogo_nascita' => $post['luogo_nascita'],
            'sesso' => $post['sesso'],
            'capitale_sociale' => $post['capitale_sociale'],
            'indirizzo' => $post['indirizzo'],
            'indirizzo2' => $post['indirizzo2'],
            'citta' => $post['citta'],
            'cap' => $post['cap'],
            'provincia' => $post['provincia'],
            'km' => $post['km'],
            'id_nazione' => !empty($post['id_nazione']) ? $post['id_nazione'] : null,
            'telefono' => $post['telefono'],
            'cellulare' => $post['cellulare'],
            'fax' => $post['fax'],
            'email' => $post['email'],
            'idsede_fatturazione' => $post['idsede_fatturazione'],
            'note' => $post['note'],
            'codiceri' => $post['codiceri'],
            'codicerea' => $post['codicerea'],
            'appoggiobancario' => $post['appoggiobancario'],
            'filiale' => $post['filiale'],
            'codiceiban' => $post['codiceiban'],
            'bic' => $post['bic'],
            'diciturafissafattura' => $post['diciturafissafattura'],
            'idpagamento_acquisti' => $post['idpagamento_acquisti'],
            'idpagamento_vendite' => $post['idpagamento_vendite'],
            'idlistino' => $post['idlistino'],
            'idiva' => $post['idiva'],
            'settore' => $post['settore'],
            'marche' => $post['marche'],
            'dipendenti' => $post['dipendenti'],
            'macchine' => $post['macchine'],
            'idagente' => $post['idagente'],
            'idrelazione' => $post['idrelazione'],
            'sitoweb' => $post['sitoweb'],
            'idzona' => $post['idzona'],
            'nome_cognome' => $post['nome_cognome'],
            'iscrizione_tribunale' => $post['iscrizione_tribunale'],
            'cciaa' => $post['cciaa'],
            'cciaa_citta' => $post['cciaa_citta'],
            'n_alboartigiani' => $post['n_alboartigiani'],
            'foro_competenza' => $post['foro_competenza'],
            'colore' => $post['colore'],
            'idtipointervento_default' => $post['idtipointervento_default'],
        ], ['idanagrafica' => $id_record]);

        $_SESSION['infos'][] = str_replace('_NAME_', '"'.$post['ragione_sociale'].'"', "Informazioni per l'anagrafica _NAME_ salvate correttamente!");

        // Aggiorno il codice anagrafica se non è già presente, altrimenti lo ignoro
        $esiste = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE codice='.prepare($post['codice']).' AND NOT idanagrafica='.prepare($id_record));

        // Verifica dell'esistenza codice anagrafica
        if ($esiste) {
            $_SESSION['errors'][] = _("Il codice anagrafica inserito esiste già! Inserirne un'altro...");
        } else {
            $dbo->query('UPDATE an_anagrafiche SET codice='.prepare($post['codice']).' WHERE idanagrafica='.prepare($id_record));
        }

        // Aggiorno gli agenti secondari collegati
        $dbo->query('DELETE FROM an_anagrafiche_agenti WHERE idanagrafica='.prepare($id_record));

        if (!empty($post['idagenti'])) {
            foreach ($post['idagenti'] as $idagente) {
                $dbo->query('INSERT INTO an_anagrafiche_agenti(idanagrafica, idagente) VALUES ('.prepare($id_record).', '.prepare($idagente).')');
            }
        }

        // Se l'agente di default è stato elencato anche tra gli agenti secondari lo rimuovo
        if(!empty($post['idagente'])){
                $dbo->query('DELETE FROM an_anagrafiche_agenti WHERE idanagrafica='.prepare($id_record).' AND idagente='.prepare($post['idagente']));
        }

        // Aggiorno le tipologie di anagrafica
        $dbo->query('DELETE FROM an_tipianagrafiche_anagrafiche WHERE idanagrafica='.prepare($id_record));

        $tipi = array_unique($post['idtipoanagrafica']);
        if (!empty($tipi)) {
            foreach ($tipi as $idtipoanagrafica) {
                $dbo->query('INSERT INTO an_tipianagrafiche_anagrafiche(idtipoanagrafica, idanagrafica) VALUES('.prepare($idtipoanagrafica).', '.prepare($id_record).')');
            }
        }

        // Verifico se esiste già l'associazione dell'anagrafica a conti del partitario
        $rs = $dbo->fetchArray('SELECT idconto_cliente, idconto_fornitore FROM an_anagrafiche WHERE idanagrafica='.prepare($id_record));
        $idconto_cliente = $rs[0]['idconto_cliente'];
        $idconto_fornitore = $rs[0]['idconto_fornitore'];

        // Creo il relativo conto nel partitario se non esiste
        if (empty($idconto_cliente)) {
            foreach ($post['idtipoanagrafica'] as $idtipoanagrafica) {
                $rs = $dbo->fetchArray('SELECT descrizione FROM an_tipianagrafiche WHERE idtipoanagrafica='.prepare($idtipoanagrafica));

                if ($rs[0]['descrizione'] == 'Cliente') {
                    // Calcolo prossimo numero cliente
                    $rs = $dbo->fetchArray("SELECT MAX(CAST(co_pianodeiconti3.numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti2.descrizione='Crediti clienti e crediti diversi'");
                    $new_numero = $rs[0]['max_numero'] + 1;
                    $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

                    $dbo->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare($post['ragione_sociale']).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Crediti clienti e crediti diversi'), 1, 1)");
                    $idconto = $dbo->lastInsertedID();

                    // Collegamento conto
                    $dbo->query('UPDATE an_anagrafiche SET idconto_cliente='.prepare($idconto).' WHERE idanagrafica='.prepare($id_record));
                }
            }
        }

        if (empty($idconto_fornitore)) {
            foreach ($post['idtipoanagrafica'] as $idtipoanagrafica) {
                $rs = $dbo->fetchArray('SELECT descrizione FROM an_tipianagrafiche WHERE idtipoanagrafica='.prepare($idtipoanagrafica));

                if ($rs[0]['descrizione'] == 'Fornitore') {
                    // Calcolo prossimo numero cliente
                    $rs = $dbo->fetchArray("SELECT MAX(CAST(co_pianodeiconti3.numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti2.descrizione='Debiti fornitori e debiti diversi'");
                    $new_numero = $rs[0]['max_numero'] + 1;
                    $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

                    $dbo->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare($post['ragione_sociale']).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Debiti fornitori e debiti diversi'), 1, 1)");
                    $idconto = $dbo->lastInsertedID();

                    // Collegamento conto
                    $dbo->query('UPDATE an_anagrafiche SET idconto_fornitore='.prepare($idconto).' WHERE idanagrafica='.prepare($id_record));
                }
            }
        }

        break;

    case 'add':
        $idtipoanagrafica = post('idtipoanagrafica');
        $ragione_sociale = post('ragione_sociale');

        // Inserimento anagrafica base
        if (count($idtipoanagrafica) > 0 && $ragione_sociale != '') {
            // Leggo l'ultimo codice anagrafica per calcolare il successivo
            $rs = $dbo->fetchArray('SELECT codice FROM an_anagrafiche ORDER BY CAST(codice AS SIGNED) DESC LIMIT 0, 1');
            $codice = get_next_code($rs[0]['codice'], 1, get_var('Formato codice anagrafica'));

            // Se ad aggiungere un cliente è un agente, lo imposto come agente di quel cliente
            // Lettura tipologia della nuova anagrafica
            for ($t = 0; $t < count($idtipoanagrafica); ++$t) {
                $rs = $dbo->fetchArray('SELECT descrizione FROM an_tipianagrafiche WHERE idtipoanagrafica='.prepare($idtipoanagrafica[$t]));
                $tipoanagrafica_dst .= $rs[0]['descrizione'];
                if ($t < count($idtipoanagrafica) - 1) {
                    $tipoanagrafica_dst .= ', ';
                }
            }

            // Lettura tipologia dell'utente loggato
            $agente_is_logged = false;

            $rs = $dbo->fetchArray('SELECT descrizione FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica = an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica = '.prepare($user['idanagrafica']));

            for ($i = 0; $i < count($rs); ++$i) {
                if ($rs[$i]['descrizione'] == 'Agente') {
                    $agente_is_logged = true;
                    $i = count($rs);
                }
            }

           $idagente = ($agente_is_logged && str_contains($tipoanagrafica_dst, 'Cliente')) ? $user['idanagrafica'] :  0;

           // Inserisco l'anagrafica
           $query = 'INSERT INTO an_anagrafiche(ragione_sociale, codice, idagente) VALUES ('.prepare($ragione_sociale).', '.prepare($codice).', '.prepare($idagente).')';
           $dbo->query($query);

           $new_id = $dbo->lastInsertedID();
        }

        // Inserisco il rapporto dell'anagrafica (cliente, tecnico, ecc)
        for ($t = 0; $t < count($idtipoanagrafica); ++$t) {
            $query = 'INSERT INTO an_tipianagrafiche_anagrafiche(idanagrafica, idtipoanagrafica) VALUES ('.prepare($new_id).', '.prepare($idtipoanagrafica[$t]).')';
            $dbo->query($query);
        }

        if (str_contains($tipoanagrafica_dst, 'Azienda')) {
            $dbo->query('UPDATE zz_settings SET valore='.prepare($new_id)." WHERE nome='Azienda predefinita'");
            $_SESSION['infos'][] = _('Anagrafica Azienda impostata come predefinita. Per ulteriori informazionioni, visitare "Strumenti -> Impostazioni -> Generali".');
        }

        // Creo il relativo conto nel partitario
        if (str_contains($tipoanagrafica_dst, 'Cliente')) {
            // Calcolo prossimo numero cliente
            $rs = $dbo->fetchArray("SELECT MAX(CAST(co_pianodeiconti3.numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti2.descrizione='Crediti clienti e crediti diversi'");
            $new_numero = $rs[0]['max_numero'] + 1;
            $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

            // Creazione conto
            $dbo->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare($ragione_sociale).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Crediti clienti e crediti diversi'), 1, 1)");
            $idconto = $dbo->lastInsertedID();

            // Collegamento conto
            $dbo->query('UPDATE an_anagrafiche SET idconto_cliente='.prepare($idconto).' WHERE idanagrafica='.prepare($new_id));
        } elseif (str_contains($tipoanagrafica_dst, 'Fornitore')) {
            // Calcolo prossimo numero cliente
            $rs = $dbo->fetchArray("SELECT MAX(CAST(co_pianodeiconti3.numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti2.descrizione='Debiti fornitori e debiti diversi'");
            $new_numero = $rs[0]['max_numero'] + 1;
            $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

            // Creazione conto
            $dbo->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare($ragione_sociale).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Debiti fornitori e debiti diversi'), 1, 1)");
            $idconto = $dbo->lastInsertedID();

            // Collegamento conto
            $dbo->query('UPDATE an_anagrafiche SET idconto_fornitore='.prepare($idconto).' WHERE idanagrafica='.prepare($new_id));
        }

        $id_record = $new_id;

        if (isAjaxRequest() && str_contains($tipoanagrafica_dst, post('tipoanagrafica'))) {
            echo json_encode(['id' => $id_record, 'text' => $ragione_sociale]);
        }

        $_SESSION['infos'][] = str_replace('_TYPE_', '"'.$tipoanagrafica_dst.'"', _('Aggiunta nuova anagrafica di tipo _TYPE_'));

        break;

    case 'delete':
        // Disattivo l'anagrafica, solo se questa non è l'azienda principale
        if (str_contains($records[0]['idtipianagrafica'], $id_azienda) === false) {
            $dbo->query('UPDATE an_anagrafiche SET deleted = 1 WHERE idanagrafica = '.prepare($id_record).Modules::getAdditionalsQuery($id_module));

            $_SESSION['infos'][] = _('Anagrafica eliminata!');
        }

        break;
}
