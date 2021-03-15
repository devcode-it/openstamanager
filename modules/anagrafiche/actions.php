<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
        $anagrafica->riferimento_amministrazione = post('riferimento_amministrazione');
        $anagrafica->colore = post('colore');
        $anagrafica->idtipointervento_default = post('idtipointervento_default') ?: null;
        $anagrafica->id_ritenuta_acconto_acquisti = post('id_ritenuta_acconto_acquisti');
        $anagrafica->id_ritenuta_acconto_vendite = post('id_ritenuta_acconto_vendite');
        $anagrafica->split_payment = post('split_payment');
        $anagrafica->tipologie = (array) post('idtipoanagrafica');

        $anagrafica->codice_fiscale = strtoupper(post('codice_fiscale'));
        $anagrafica->partita_iva = strtoupper(post('piva'));

        $anagrafica->save();

        // Aggiorno gli agenti collegati
        $dbo->sync('an_anagrafiche_agenti', ['idanagrafica' => $id_record], ['idagente' => (array) post('idagenti')]);

        // Se l'agente di default è stato elencato anche tra gli agenti secondari lo rimuovo
        if (!empty(post('idagente'))) {
            $dbo->query('DELETE FROM an_anagrafiche_agenti WHERE idanagrafica='.prepare($id_record).' AND idagente='.prepare(post('idagente')));
        }

        flash()->info(tr("Informazioni per l'anagrafica _NAME_ salvate correttamente!", [
            '_NAME_' => $anagrafica->ragione_sociale,
        ]));

        // Aggiorno il codice anagrafica se non è già presente, altrimenti lo ignoro
        if ($anagrafica->codice != post('codice')) {
            flash()->warning(tr('Attenzione: il codice anagrafica _COD_ esiste già', [
                '_COD_' => $anagrafica->codice,
            ]));
        }

        // Controllo che il Codice Fiscale non sia già presente
        $codice_fiscale = $anagrafica->codice_fiscale;
        if (!empty($codice_fiscale)) {
            $anagrafiche_codice_fiscale = Anagrafica::where('codice_fiscale', $codice_fiscale)
                ->where('idanagrafica', '!=', $anagrafica->id)
                ->get();
            if (!$anagrafiche_codice_fiscale->isEmpty()) {
                $message = tr('Attenzione: il codice fiscale _COD_ è già stato censito', [
                    '_COD_' => $codice_fiscale,
                ]);

                $links = [];
                foreach ($anagrafiche_codice_fiscale as $anagrafica_singola) {
                    $links[] = '<li>'.Modules::link('Anagrafiche', $anagrafica_singola->id, $anagrafica_singola->ragione_sociale).'</li>';
                }

                flash()->warning($message.'<ul>'.implode('', $links).'</ul>');
            }
        }

        // Controllo che la Partita IVA non sia già presente
        $partita_iva = $anagrafica->partita_iva;
        if (!empty($partita_iva)) {
            $anagrafiche_partita_iva = Anagrafica::where('piva', $partita_iva)
                ->where('idanagrafica', '!=', $anagrafica->id)
                ->get();
            if (!$anagrafiche_partita_iva->isEmpty()) {
                $message = tr('Attenzione: la partita IVA _IVA_ è già stata censita', [
                    '_IVA_' => $partita_iva,
                ]);

                $links = [];
                foreach ($anagrafiche_partita_iva as $anagrafica_singola) {
                    $links[] = '<li>'.Modules::link('Anagrafiche', $anagrafica_singola->id, $anagrafica_singola->ragione_sociale).'</li>';
                }

                flash()->warning($message.'<ul>'.implode('', $links).'</ul>');
            }

            $vat_number = is_numeric($partita_iva) ? $anagrafica->nazione->iso2.$partita_iva : $partita_iva;
            $check_vat_number = Validate::isValidVatNumber($vat_number);
            if (empty($check_vat_number['valid-format'])) {
                flash()->warning(tr('Attenzione: la partita IVA _IVA_ potrebbe non essere valida', [
                    '_IVA_' => $partita_iva,
                ]));
            }
        }

        // Validazione del Codice Fiscale, solo per anagrafiche Private e Aziende, ignoro controllo se codice fiscale e settato uguale alla p.iva
        if ($anagrafica->tipo != 'Ente pubblico' && !empty($anagrafica->codice_fiscale) && $anagrafica->codice_fiscale != $anagrafica->partita_iva) {
            $check_codice_fiscale = Validate::isValidTaxCode($codice_fiscale);
            if (empty($check_codice_fiscale)) {
                flash()->warning(tr('Attenzione: il codice fiscale _COD_ potrebbe non essere valido.', [
                    '_COD_' => $codice_fiscale,
                ]));
            }
        }

        break;

    case 'add':
        $idtipoanagrafica = post('idtipoanagrafica');
        $ragione_sociale = post('ragione_sociale');

        $anagrafica = Anagrafica::build($ragione_sociale, post('nome'), post('cognome'), $idtipoanagrafica);
        $id_record = $anagrafica->id;

        // Se ad aggiungere un cliente è un agente, lo imposto come agente di quel cliente
        // Lettura tipologia dell'utente loggato
        $agente_is_logged = false;
        if (!empty($user['idanagrafica'])) {
            $rs = $dbo->fetchArray('SELECT descrizione FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica = an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica = '.prepare($user['idanagrafica']));

            for ($i = 0; $i < count($rs); ++$i) {
                if ($rs[$i]['descrizione'] == 'Agente') {
                    $agente_is_logged = true;
                    $i = count($rs);
                }
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

        $anagrafica->codice_fiscale = strtoupper(post('codice_fiscale'));
        $anagrafica->partita_iva = strtoupper(post('piva'));

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

        // Controllo che il Codice Fiscale non sia già presente
        $codice_fiscale = $anagrafica->codice_fiscale;
        if (!empty($codice_fiscale)) {
            $anagrafiche_codice_fiscale = Anagrafica::where('codice_fiscale', $codice_fiscale)
                ->where('idanagrafica', '!=', $anagrafica->id)
                ->get();
            if (!$anagrafiche_codice_fiscale->isEmpty()) {
                $message = tr('Attenzione: il codice fiscale _COD_ è già stato censito', [
                    '_COD_' => $codice_fiscale,
                ]);

                $links = [];
                foreach ($anagrafiche_codice_fiscale as $anagrafica_singola) {
                    $links[] = '<li>'.Modules::link('Anagrafiche', $anagrafica_singola->id, $anagrafica_singola->ragione_sociale).'</li>';
                }

                flash()->warning($message.'<ul>'.implode('', $links).'</ul>');
            }
        }

        // Controllo che la Partita IVA non sia già presente
        $partita_iva = $anagrafica->partita_iva;
        if (!empty($partita_iva)) {
            $anagrafiche_partita_iva = Anagrafica::where('piva', $partita_iva)
                ->where('idanagrafica', '!=', $anagrafica->id)
                ->get();
            if (!$anagrafiche_partita_iva->isEmpty()) {
                $message = tr('Attenzione: la partita IVA _IVA_ è già stata censita', [
                    '_IVA_' => $partita_iva,
                ]);

                $links = [];
                foreach ($anagrafiche_partita_iva as $anagrafica_singola) {
                    $links[] = '<li>'.Modules::link('Anagrafiche', $anagrafica_singola->id, $anagrafica_singola->ragione_sociale).'</li>';
                }

                flash()->warning($message.'<ul>'.implode('', $links).'</ul>');
            }

            $vat_number = is_numeric($partita_iva) ? $anagrafica->nazione->iso2.$partita_iva : $partita_iva;
            $check_vat_number = Validate::isValidVatNumber($vat_number);
            if (empty($check_vat_number['valid-format'])) {
                flash()->warning(tr('Attenzione: la partita IVA _IVA_ potrebbe non essere valida', [
                    '_IVA_' => $partita_iva,
                ]));
            }
        }

        // Validazione del Codice Fiscale, solo per anagrafiche Private e Aziende, ignoro controllo se codice fiscale e settato uguale alla p.iva
        if ($anagrafica->tipo != 'Ente pubblico' && !empty($anagrafica->codice_fiscale) && !empty($anagrafica->partita_iva) && $anagrafica->codice_fiscale != $anagrafica->partita_iva) {
            $check_codice_fiscale = Validate::isValidTaxCode($codice_fiscale);
            if (empty($check_codice_fiscale)) {
                flash()->warning(tr('Attenzione: il codice fiscale _COD_ potrebbe non essere valido.', [
                    '_COD_' => $codice_fiscale,
                ]));
            }
        }

        break;

    // Informazioni sulla posizione della sede
    case 'posizione':
        $sede = $anagrafica->sedeLegale;
        $sede->gaddress = post('gaddress');
        $sede->lat = post('lat');
        $sede->lng = post('lng');

        $sede->save();
        break;

    case 'delete':
        // Se l'anagrafica non è l'azienda principale, la disattivo
        if (!$anagrafica->isAzienda()) {
            //$anagrafica->delete();
            $dbo->query('UPDATE an_anagrafiche SET deleted_at = NOW() WHERE idanagrafica = '.prepare($id_record));

            // Se l'anagrafica è collegata ad un utente lo disabilito
            $dbo->query('UPDATE zz_users SET enabled = 0 WHERE idanagrafica = '.prepare($id_record));
            // Disabilito anche il token
            $dbo->query('UPDATE zz_tokens SET enabled = 0 WHERE id_utente = '.prepare($id_utente));

            flash()->info(tr('Anagrafica eliminata!'));
        }

        break;
}

// Operazioni aggiuntive per il logo e filigrana stampe
if (filter('op') == 'aggiungi-allegato') {
    $nome = $upload->name;

    $logo_stampe = ['logo stampe', 'logo_stampe', 'logo stampe.jpg', 'logo stampe.png'];
    if (in_array(strtolower($nome), $logo_stampe)) {
        $nome = 'Logo stampe';
        $uploads = $structure->uploads($id_record)->where('filename', $upload->filename);
        foreach ($uploads as $logo) {
            $logo->name = $nome;
            $logo->save();
        }
    }

    $filigrana_stampe = ['filigrana stampe', 'filigrana_stampe', 'filigrana stampe.jpg', 'filigrana stampe.png'];
    if (in_array(strtolower($nome), $filigrana_stampe)) {
        $nome = 'Filigrana stampe';
        $uploads = $structure->uploads($id_record)->where('filename', $upload->filename);
        foreach ($uploads as $filigrana) {
            $filigrana->name = $nome;
            $filigrana->save();
        }
    }

    if (($nome == 'Logo stampe' || $nome = 'Filigrana stampe') && (setting('Azienda predefinita') == $id_record)) {
        Settings::setValue($nome, $upload->filename);
    }
}

// Operazioni aggiuntive per il logo
elseif (filter('op') == 'rimuovi-allegato') {
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
