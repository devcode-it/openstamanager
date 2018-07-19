<?php

include_once __DIR__.'/../../core.php';

$id_azienda = $dbo->fetchArray("SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Azienda'")[0]['idtipoanagrafica'];
$id_cliente = $dbo->fetchArray("SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Cliente'")[0]['idtipoanagrafica'];
$id_fornitore = $dbo->fetchArray("SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Fornitore'")[0]['idtipoanagrafica'];
$id_tecnico = $dbo->fetchArray("SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Tecnico'")[0]['idtipoanagrafica'];

switch (post('op')) {
    case 'update':
        $partita_iva = trim(strtoupper(post('piva')));
        $codice_fiscale = trim(strtoupper(post('codice_fiscale')));

        // Leggo tutti i valori passati dal POST e li salvo in un array
        $dbo->update('an_anagrafiche', [
            'ragione_sociale' => post('ragione_sociale'),
            'tipo' => post('tipo'),
            'piva' => $partita_iva,
            'codice_fiscale' => $codice_fiscale,
            'data_nascita' => post('data_nascita'),
            'luogo_nascita' => post('luogo_nascita'),
            'sesso' => post('sesso'),
            'capitale_sociale' => post('capitale_sociale'),
            'indirizzo' => post('indirizzo'),
            'indirizzo2' => post('indirizzo2'),
            'citta' => post('citta'),
            'cap' => post('cap'),
            'provincia' => post('provincia'),
            'km' => post('km'),
            'id_nazione' => !empty(post('id_nazione')) ? post('id_nazione') : null,
            'telefono' => post('telefono'),
            'cellulare' => post('cellulare'),
            'fax' => post('fax'),
            'email' => post('email'),
            'pec' => post('pec'),
            'idsede_fatturazione' => post('idsede_fatturazione'),
            'note' => post('note'),
            'codiceri' => post('codiceri'),
            'codicerea' => post('codicerea'),
            'appoggiobancario' => post('appoggiobancario'),
            'filiale' => post('filiale'),
            'codiceiban' => post('codiceiban'),
            'bic' => post('bic'),
            'diciturafissafattura' => post('diciturafissafattura'),
            'idpagamento_acquisti' => post('idpagamento_acquisti'),
            'idpagamento_vendite' => post('idpagamento_vendite'),
            'idlistino_acquisti' => post('idlistino_acquisti'),
            'idlistino_vendite' => post('idlistino_vendite'),
            'idiva_acquisti' => post('idiva_acquisti'),
            'idiva_vendite' => post('idiva_vendite'),
            'idbanca_acquisti' => post('idbanca_acquisti'),
            'idbanca_vendite' => post('idbanca_vendite'),
            'settore' => post('settore'),
            'marche' => post('marche'),
            'dipendenti' => post('dipendenti'),
            'macchine' => post('macchine'),
            'idagente' => post('idagente'),
            'idrelazione' => post('idrelazione'),
            'sitoweb' => post('sitoweb'),
            'idzona' => post('idzona'),
            'nome_cognome' => post('nome_cognome'),
            'iscrizione_tribunale' => post('iscrizione_tribunale'),
            'cciaa' => post('cciaa'),
            'cciaa_citta' => post('cciaa_citta'),
            'n_alboartigiani' => post('n_alboartigiani'),
            'foro_competenza' => post('foro_competenza'),
            'colore' => post('colore'),
            'idtipointervento_default' => post('idtipointervento_default'),
            'gaddress' => post('gaddress'),
            'lat' => post('lat'),
            'lng' => post('lng'),
        ], ['idanagrafica' => $id_record]);

        flash()->info(str_replace('_NAME_', '"'.post('ragione_sociale').'"', "Informazioni per l'anagrafica _NAME_ salvate correttamente!"));

        // Validazione della Partita IVA
        $check_vat_number = Validate::isValidVatNumber(strtoupper($partita_iva));
        if (empty($check_vat_number)) {
            flash()->error(tr('Attenzione: la partita IVA _IVA_ sembra non essere valida', [
                '_IVA_' => $partita_iva,
            ]));
        }

        // Aggiorno il codice anagrafica se non è già presente, altrimenti lo ignoro
        $esiste = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE codice='.prepare(post('codice')).' AND NOT idanagrafica='.prepare($id_record));

        // Verifica dell'esistenza codice anagrafica
        if ($esiste) {
            flash()->error(tr("Il codice anagrafica inserito esiste già! Inserirne un'altro..."));
        } else {
            $dbo->query('UPDATE an_anagrafiche SET codice='.prepare(post('codice')).' WHERE idanagrafica='.prepare($id_record));
        }

        // Aggiorno gli agenti collegati
        $dbo->sync('an_anagrafiche_agenti', ['idanagrafica' => $id_record], ['idagente' => (array) post('idagenti')]);

        // Se l'agente di default è stato elencato anche tra gli agenti secondari lo rimuovo
        if (!empty(post('idagente'))) {
            $dbo->query('DELETE FROM an_anagrafiche_agenti WHERE idanagrafica='.prepare($id_record).' AND idagente='.prepare(post('idagente')));
        }

        // Aggiorno le tipologie di anagrafica
        $idtipoanagrafica = (array) post('idtipoanagrafica');
        if (in_array($id_azienda, $tipi_anagrafica)) {
            $idtipoanagrafica[] = $id_azienda;
        }

        $dbo->sync('an_tipianagrafiche_anagrafiche', ['idanagrafica' => $id_record], ['idtipoanagrafica' => $idtipoanagrafica]);

        // Verifico se esiste già l'associazione dell'anagrafica a conti del partitario
        $rs = $dbo->fetchArray('SELECT idconto_cliente, idconto_fornitore FROM an_anagrafiche WHERE idanagrafica='.prepare($id_record));
        $idconto_cliente = $rs[0]['idconto_cliente'];
        $idconto_fornitore = $rs[0]['idconto_fornitore'];

        // Creo il relativo conto nel partitario se non esiste
        if (empty($idconto_cliente) && in_array($id_cliente, $idtipoanagrafica)) {
            // Calcolo prossimo numero cliente
            $rs = $dbo->fetchArray("SELECT MAX(CAST(co_pianodeiconti3.numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti2.descrizione='Crediti clienti e crediti diversi'");
            $new_numero = $rs[0]['max_numero'] + 1;
            $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

            $dbo->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare(post('ragione_sociale')).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Crediti clienti e crediti diversi'), 1, 1)");
            $idconto = $dbo->lastInsertedID();

            // Collegamento conto
            $dbo->query('UPDATE an_anagrafiche SET idconto_cliente='.prepare($idconto).' WHERE idanagrafica='.prepare($id_record));
        }

        if (empty($idconto_fornitore) && in_array($id_fornitore, $idtipoanagrafica)) {
            // Calcolo prossimo numero cliente
            $rs = $dbo->fetchArray("SELECT MAX(CAST(co_pianodeiconti3.numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti2.descrizione='Debiti fornitori e debiti diversi'");
            $new_numero = $rs[0]['max_numero'] + 1;
            $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

            $dbo->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare(post('ragione_sociale')).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Debiti fornitori e debiti diversi'), 1, 1)");
            $idconto = $dbo->lastInsertedID();

            // Collegamento conto
            $dbo->query('UPDATE an_anagrafiche SET idconto_fornitore='.prepare($idconto).' WHERE idanagrafica='.prepare($id_record));
        }

        break;

    case 'add':
        $idtipoanagrafica = post('idtipoanagrafica');
        $ragione_sociale = post('ragione_sociale');

        // Inserimento anagrafica base
        // Leggo l'ultimo codice anagrafica per calcolare il successivo
        $rs = $dbo->fetchArray('SELECT codice FROM an_anagrafiche ORDER BY CAST(codice AS SIGNED) DESC LIMIT 0, 1');
        $codice = Util\Generator::generate(setting('Formato codice anagrafica'), $rs[0]['codice']);

        // Se ad aggiungere un cliente è un agente, lo imposto come agente di quel cliente
        // Lettura tipologia dell'utente loggato
        $agente_is_logged = false;

        $rs = $dbo->fetchArray('SELECT descrizione FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica = an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica = '.prepare($user['idanagrafica']));

        for ($i = 0; $i < count($rs); ++$i) {
            if ($rs[$i]['descrizione'] == 'Agente') {
                $agente_is_logged = true;
                $i = count($rs);
            }
        }

        $idagente = ($agente_is_logged && in_array($id_cliente, $idtipoanagrafica)) ? $user['idanagrafica'] : 0;

        $partita_iva = trim(strtoupper(post('piva')));
        $codice_fiscale = trim(strtoupper(post('codice_fiscale')));

        // Inserisco l'anagrafica
        $dbo->insert('an_anagrafiche', [
            'ragione_sociale' => $ragione_sociale,
            'codice' => $codice,
            'piva' => $partita_iva,
            'codice_fiscale' => $codice_fiscale,
            'indirizzo' => post('indirizzo'),
            'citta' => post('citta'),
            'cap' => post('cap'),
            'provincia' => post('provincia'),
            'telefono' => post('telefono'),
            'cellulare' => post('cellulare'),
            'email' => post('email'),
            'idrelazione' => post('idrelazione'),
            'idagente' => $idagente,
        ]);

        $new_id = $dbo->lastInsertedID();

        // Inserisco il rapporto dell'anagrafica (cliente, tecnico, ecc)
        $dbo->sync('an_tipianagrafiche_anagrafiche', ['idanagrafica' => $new_id], ['idtipoanagrafica' => (array) $idtipoanagrafica]);

        if (in_array($id_azienda, $idtipoanagrafica)) {
            Settings::setValue('Azienda predefinita', $new_id);

            flash()->info(tr('Anagrafica Azienda impostata come predefinita. Per ulteriori informazionioni, visitare "Strumenti -> Impostazioni -> Generali"'));
        }

        //se sto inserendo un tecnico, mi copio già le tariffe per le varie attività
        if (in_array($id_tecnico, $idtipoanagrafica)) {
            //per ogni tipo di attività
            $rs_tipiintervento = $dbo->fetchArray('SELECT * FROM in_tipiintervento');

            for ($i = 0; $i < count($rs_tipiintervento); ++$i) {
                if ($dbo->query('INSERT INTO in_tariffe( idtecnico, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico ) VALUES( '.prepare($new_id).', '.prepare($rs_tipiintervento[$i]['idtipointervento']).', (SELECT costo_orario FROM in_tipiintervento WHERE idtipointervento='.prepare($rs_tipiintervento[$i]['idtipointervento']).'), (SELECT costo_km FROM in_tipiintervento WHERE idtipointervento='.prepare($rs_tipiintervento[$i]['idtipointervento']).'), (SELECT costo_diritto_chiamata FROM in_tipiintervento WHERE idtipointervento='.prepare($rs_tipiintervento[$i]['idtipointervento']).'),   (SELECT costo_orario_tecnico FROM in_tipiintervento WHERE idtipointervento='.prepare($rs_tipiintervento[$i]['idtipointervento']).'), (SELECT costo_km_tecnico FROM in_tipiintervento WHERE idtipointervento='.prepare($rs_tipiintervento[$i]['idtipointervento']).'), (SELECT costo_diritto_chiamata_tecnico FROM in_tipiintervento WHERE idtipointervento='.prepare($rs_tipiintervento[$i]['idtipointervento']).') )')) {
                    //flash()->info(tr('Informazioni salvate correttamente!'));
                } else {
                    flash()->error(tr("Errore durante l'importazione tariffe!"));
                }
            }
        }

        // Creo il relativo conto nel partitario (cliente)
        if (in_array($id_cliente, $idtipoanagrafica)) {
            // Calcolo prossimo numero cliente
            $rs = $dbo->fetchArray("SELECT MAX(CAST(co_pianodeiconti3.numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti2.descrizione='Crediti clienti e crediti diversi'");
            $new_numero = $rs[0]['max_numero'] + 1;
            $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

            // Creazione conto
            $dbo->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare($ragione_sociale).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Crediti clienti e crediti diversi'), 1, 1)");
            $idconto = $dbo->lastInsertedID();

            // Collegamento conto
            $dbo->query('UPDATE an_anagrafiche SET idconto_cliente='.prepare($idconto).' WHERE idanagrafica='.prepare($new_id));
        }

        // Creo il relativo conto nel partitario (fornitore)
        if (in_array($id_fornitore, $idtipoanagrafica)) {
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

        // Lettura tipologia della nuova anagrafica
        if (!empty($idtipoanagrafica)) {
            $rs = $dbo->fetchArray('SELECT descrizione FROM an_tipianagrafiche WHERE idtipoanagrafica IN ('.implode(',', $idtipoanagrafica).')');
            $tipoanagrafica_dst = implode(', ', array_column($rs, 'descrizione'));
        }

        if (isAjaxRequest() && str_contains($tipoanagrafica_dst, post('tipoanagrafica'))) {
            echo json_encode(['id' => $id_record, 'text' => $ragione_sociale]);
        }

        flash()->info(tr('Aggiunta nuova anagrafica di tipo _TYPE_', [
            '_TYPE_' => '"'.$tipoanagrafica_dst.'"',
        ]));

        break;

    case 'delete':
        // Se l'anagrafica non è l'azienda principale, la disattivo
        if (!in_array($id_azienda, $tipi_anagrafica)) {
            $dbo->query('UPDATE an_anagrafiche SET deleted_at = NOW() WHERE idanagrafica = '.prepare($id_record).Modules::getAdditionalsQuery($id_module));

            // Se l'anagrafica è collegata ad un utente lo disabilito
            $dbo->query('UPDATE zz_users SET enabled = 0 WHERE idanagrafica = '.prepare($id_record).Modules::getAdditionalsQuery($id_module));

            flash()->info(tr('Anagrafica eliminata!'));
        }

        break;
}

// Operazioni aggiuntive per il logo
if (filter('op') == 'link_file') {
    $nome = 'Logo stampe';

    if (setting('Azienda predefinita') == $id_record && filter('nome_allegato') == $nome) {
        Settings::setValue($nome, $upload);
    }
}
