<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;

switch (post('op')) {
    case 'restore':
        $anagrafica->restore();
        flash()->info(tr('Anagrafica _NAME_ ripristinata correttamente!', [
            '_NAME_' => post('ragione_sociale'),
        ]));

        // no break
    case 'update':
        // Informazioni sulla sede
        $sede = $anagrafica->sedeLegale;
        $sede->indirizzo = post('indirizzo');
        $sede->indirizzo2 = post('indirizzo2');
        $sede->citta = post('citta');
        $sede->cap = post('cap');
        $sede->provincia = post('provincia');
        $sede->km = post('km');
        $sede->id_nazione = post('id_nazione') ?: null;
        $sede->gaddress = post('gaddress');
        $sede->lat = post('lat');
        $sede->lng = post('lng');
        $sede->telefono = post('telefono');
        $sede->cellulare = post('cellulare');
        $sede->fax = post('fax');
        $sede->idzona = post('idzona');
        $sede->email = post('email');

        $opt_out_newsletter = post('disable_newsletter');
        $sede->enable_newsletter = empty($opt_out_newsletter);

        $sede->save();

        // Informazioni sull'anagrafica
        $anagrafica->codice = post('codice');
        $anagrafica->tipo = post('tipo');
        $anagrafica->codice_destinatario = strtoupper(post('codice_destinatario'));
        $anagrafica->ragione_sociale = post('ragione_sociale');
        $anagrafica->nome = post('nome');
        $anagrafica->cognome = post('cognome');
        $anagrafica->tipo = post('tipo');
        $anagrafica->data_nascita = post('data_nascita');
        $anagrafica->luogo_nascita = post('luogo_nascita');
        $anagrafica->sesso = post('sesso');
        $anagrafica->capitale_sociale = post('capitale_sociale');
        $anagrafica->pec = post('pec');
        $anagrafica->idsede_fatturazione = post('idsede_fatturazione');
        $anagrafica->note = post('note');
        $anagrafica->codiceri = post('codiceri');
        $anagrafica->codicerea = post('codicerea');
        $anagrafica->appoggiobancario = post('appoggiobancario');
        $anagrafica->filiale = post('filiale');
        $anagrafica->codiceiban = post('codiceiban');
        $anagrafica->bic = post('bic');
        $anagrafica->diciturafissafattura = post('diciturafissafattura');
        $anagrafica->idpagamento_acquisti = post('idpagamento_acquisti');
        $anagrafica->idpagamento_vendite = post('idpagamento_vendite');
        $anagrafica->idlistino_acquisti = post('idlistino_acquisti');
        $anagrafica->idlistino_vendite = post('idlistino_vendite');
        $anagrafica->idiva_acquisti = post('idiva_acquisti');
        $anagrafica->idiva_vendite = post('idiva_vendite');
        $anagrafica->idbanca_acquisti = post('idbanca_acquisti');
        $anagrafica->idbanca_vendite = post('idbanca_vendite');
        $anagrafica->settore = post('settore');
        $anagrafica->marche = post('marche');
        $anagrafica->dipendenti = post('dipendenti');
        $anagrafica->macchine = post('macchine');
        $anagrafica->idagente = post('idagente');
        $anagrafica->idrelazione = post('idrelazione');
        $anagrafica->sitoweb = post('sitoweb');
        $anagrafica->iscrizione_tribunale = post('iscrizione_tribunale');
        $anagrafica->cciaa = post('cciaa');
        $anagrafica->cciaa_citta = post('cciaa_citta');
        $anagrafica->n_alboartigiani = post('n_alboartigiani');
        $anagrafica->foro_competenza = post('foro_competenza');
        $anagrafica->colore = post('colore');
        $anagrafica->idtipointervento_default = post('idtipointervento_default') ?: null;
        $anagrafica->id_ritenuta_acconto_acquisti = post('id_ritenuta_acconto_acquisti');
        $anagrafica->id_ritenuta_acconto_vendite = post('id_ritenuta_acconto_vendite');
        $anagrafica->split_payment = post('split_payment');

        $anagrafica->tipologie = (array) post('idtipoanagrafica');

        // Avviso durante il salvataggio del codice fiscale se già presente e informo l'utente delle schede presenti
        if (!empty(post('codice_fiscale'))) {
            $idanagrafica = $dbo->fetchOne('SELECT GROUP_CONCAT(idanagrafica) AS idanagrafica FROM an_anagrafiche WHERE codice_fiscale = '.prepare(post('codice_fiscale')).' AND idanagrafica != '.prepare($id_record).' AND deleted_at IS NULL')['idanagrafica'];

            if (!empty($idanagrafica)) {
                $array = explode(',', $idanagrafica);
                foreach ($array as $value) {
                    flash()->warning(tr('Attenzione: il codice fiscale _COD_ è già stato censito. _LINK_', [
                        '_COD_' => '<b>'.post('codice_fiscale').'</b>',
                        '_LINK_' => Modules::link('Anagrafiche', $value, null, null, ''),
                    ]));
                }
            } else {
                $anagrafica->codice_fiscale = strtoupper(post('codice_fiscale'));
            }
        } else {
            $anagrafica->codice_fiscale = strtoupper(post('codice_fiscale'));
        }

        // Avviso durante il salvataggio della partita iva se già presente e informo l'utente delle schede presenti
        if (!empty(post('piva')) && !in_array(post('piva'), ['99999999999', '00000000000'])) {
            $idanagrafica = $dbo->fetchOne('SELECT GROUP_CONCAT(idanagrafica) AS idanagrafica FROM an_anagrafiche WHERE piva = '.prepare(post('piva')).' AND idanagrafica != '.prepare($id_record))['idanagrafica'];

            if (!empty($idanagrafica)) {
                $array = explode(',', $idanagrafica);
                foreach ($array as $value) {
                    flash()->warning(tr('Attenzione: la partita IVA _IVA_ è già stata censita. _LINK_', [
                        '_IVA_' => post('piva'),
                        '_LINK_' => Modules::link('Anagrafiche', $value, null, null, ''),
                    ]));
                }
            } else {
                $anagrafica->partita_iva = post('piva');
            }
        } else {
            $anagrafica->partita_iva = post('piva');
        }

        $anagrafica->save();

        flash()->info(tr("Informazioni per l'anagrafica _NAME_ salvate correttamente!", [
            '_NAME_' => $anagrafica->ragione_sociale,
        ]));

        // Validazione della Partita IVA
        $partita_iva = $anagrafica->partita_iva;
        $partita_iva = is_numeric($partita_iva) ? $anagrafica->nazione->iso2.$partita_iva : $partita_iva;

        $check_vat_number = Validate::isValidVatNumber($partita_iva);
        if (empty($check_vat_number['valid-format'])) {
            flash()->warning(tr('Attenzione: la partita IVA _IVA_ potrebbe non essere valida.', [
                '_IVA_' => $partita_iva,
            ]));
        }

        // Validazione del Codice Fiscale, solo per anagrafiche Private e Aziende, ignoro controllo se codice fiscale e settato uguale alla p.iva
        if ($anagrafica->tipo != 'Ente pubblico' and $anagrafica->codice_fiscale != $anagrafica->partita_iva) {
            $check_codice_fiscale = Validate::isValidTaxCode($anagrafica->codice_fiscale);
            if (empty($check_codice_fiscale)) {
                flash()->warning(tr('Attenzione: il codice fiscale _COD_ potrebbe non essere valido.', [
                    '_COD_' => $anagrafica->codice_fiscale,
                ]));
            }
        }

        // Aggiorno il codice anagrafica se non è già presente, altrimenti lo ignoro
        if ($anagrafica->codice != post('codice')) {
            flash()->warning(tr('Attenzione: il codice anagrafica _COD_ esiste già', [
                '_COD_' => $anagrafica->codice,
            ]));
        }

        // Aggiorno gli agenti collegati
        $dbo->sync('an_anagrafiche_agenti', ['idanagrafica' => $id_record], ['idagente' => (array) post('idagenti')]);

        // Se l'agente di default è stato elencato anche tra gli agenti secondari lo rimuovo
        if (!empty(post('idagente'))) {
            $dbo->query('DELETE FROM an_anagrafiche_agenti WHERE idanagrafica='.prepare($id_record).' AND idagente='.prepare(post('idagente')));
        }

        break;

    case 'add':
        $idtipoanagrafica = post('idtipoanagrafica');
        $ragione_sociale = post('ragione_sociale');

        $anagrafica = Anagrafica::build($ragione_sociale, post('nome'), post('cognome'), $idtipoanagrafica);
        $id_record = $anagrafica->id;

        // Blocco il salvataggio del codice fiscale se già presente
        if (!empty(post('codice_fiscale'))) {
            $idanagrafica = $dbo->fetchOne('SELECT GROUP_CONCAT(idanagrafica) AS idanagrafica FROM an_anagrafiche WHERE codice_fiscale = '.prepare(post('codice_fiscale')).' AND idanagrafica != '.prepare($id_record))['idanagrafica'];

            if (!empty($idanagrafica)) {
                $array = explode(',', $idanagrafica);
                foreach ($array as $value) {
                    flash()->warning(tr('Attenzione: il codice fiscale _COD_ è già stato censito. _LINK_', [
                        '_COD_' => '<b>'.post('codice_fiscale').'</b>',
                        '_LINK_' => Modules::link('Anagrafiche', $value, null, null, ''),
                    ]));
                }
            } else {
                $anagrafica->codice_fiscale = strtoupper(post('codice_fiscale'));
            }
        } else {
            $anagrafica->codice_fiscale = strtoupper(post('codice_fiscale'));
        }

        // Blocco il salvataggio della partita iva se già presente
        if (!empty(post('piva'))) {
            $idanagrafica = $dbo->fetchOne('SELECT GROUP_CONCAT(idanagrafica) AS idanagrafica FROM an_anagrafiche WHERE piva = '.prepare(post('piva')).' AND idanagrafica != '.prepare($id_record))['idanagrafica'];

            if (!empty($idanagrafica)) {
                $array = explode(',', $idanagrafica);
                foreach ($array as $value) {
                    flash()->warning(tr('Attenzione: la partita IVA _IVA_ è già stata censita. _LINK_', [
                    '_IVA_' => post('piva'),
                    '_LINK_' => Modules::link('Anagrafiche', $value, null, null, ''),
                    ]));
                }
            } else {
                $anagrafica->partita_iva = post('piva');
            }
        } else {
            $anagrafica->partita_iva = post('piva');
        }

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

        $anagrafica->indirizzo = post('indirizzo');
        $anagrafica->citta = post('citta');
        $anagrafica->cap = post('cap');
        $anagrafica->provincia = post('provincia');
        $anagrafica->telefono = post('telefono');
        $anagrafica->cellulare = post('cellulare');
        $anagrafica->email = post('email');
        $anagrafica->idagente = $idagente;
        $anagrafica->pec = post('pec');
        $anagrafica->tipo = post('tipo');
        $anagrafica->id_nazione = post('id_nazione') ?: null;
        $anagrafica->codice_destinatario = strtoupper(post('codice_destinatario'));

        $anagrafica->save();

        if ($anagrafica->isAzienda()) {
            flash()->info(tr('Anagrafica Azienda impostata come predefinita').'. '.tr('Per ulteriori informazionioni, visitare "Strumenti -> Impostazioni -> Generali"'));
        }

        // Lettura tipologia della nuova anagrafica
        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => $anagrafica->ragione_sociale]);
        }

        $descrizioni_tipi = $anagrafica->tipi()->get()->pluck('descrizione')->toArray();

        flash()->info(tr('Aggiunta nuova anagrafica di tipo _TYPE_', [
            '_TYPE_' => '"'.implode(', ', $descrizioni_tipi).'"',
        ]));

        break;

    case 'delete':
        // Se l'anagrafica non è l'azienda principale, la disattivo
        if (!$anagrafica->isAzienda()) {
            //$anagrafica->delete();
            $dbo->query('UPDATE an_anagrafiche SET deleted_at = NOW() WHERE idanagrafica = '.prepare($id_record));

            // Se l'anagrafica è collegata ad un utente lo disabilito
            $dbo->query('UPDATE zz_users SET enabled = 0 WHERE idanagrafica = '.prepare($id_record));

            flash()->info(tr('Anagrafica eliminata!'));
        }

        break;
}

// Operazioni aggiuntive per il logo
if (filter('op') == 'link_file') {
    $nome = filter('nome_allegato');

    if ($nome == 'Logo stampe' or $nome = 'Filigrana stampe') {
        if (setting('Azienda predefinita') == $id_record && filter('nome_allegato') == $nome) {
            Settings::setValue($nome, $upload);
        }
    }
}

// Operazioni aggiuntive per il logo
elseif (filter('op') == 'unlink_file') {
    $filename = filter('filename');

    if (strpos($filename, setting('Logo stampe')) !== false) {
        $nome = 'Logo stampe';
    }
    if (strpos($filename, setting('Filigrana stampe')) !== false) {
        $nome = 'Filigrana stampe';
    }

    if (setting('Azienda predefinita') == $id_record && $filename == setting($nome)) {
        Settings::setValue($nome, '');
    }
}
