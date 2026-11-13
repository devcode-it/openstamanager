# Changelog

Tutti i maggiori cambiamenti di questo progetto saranno documentati in questo file. Per informazioni più dettagliate, consultare il log GIT della repository su GitHub.

Il formato utilizzato è basato sulle linee guida di [Keep a Changelog](http://keepachangelog.com/), e il progetto segue il [Semantic Versioning](http://semver.org/) per definire le versioni delle release.

- [2.11.2 (2026-08-04)](#2112-2026-08-04)
- [2.11.1 (2026-06-30)](#2111-2026-06-30)
- [2.11.0 (2026-06-09)](#2110-2026-06-09)
- [2.10.4 (2026-04-28)](#2104-2026-04-28)
- [2.10.3 (2026-04-07)](#2103-2026-04-07)
- [2.10.2 (2026-03-17)](#2102-2026-03-17)
- [2.10.1 (2026-02-26)](#2101-2026-02-26)
- [2.10 (2026-02-05)](#210-2026-02-05)
- [2.9.8 (2025-12-23)](#298-2025-12-23)
- [2.9.7 (2025-12-09)](#297-2025-12-09)
- [2.9.6 (2025-11-26)](#296-2025-11-26)
- [2.9.5 (2025-11-12)](#295-2025-11-12)
- [2.9.4 (2025-10-28)](#294-2025-10-28)
- [2.9.3 (2025-10-14)](#293-2025-10-14)
- [2.9.2 (2025-09-25)](#292-2025-09-25)
- [2.9.1 (2025-09-02)](#291-2025-09-02)
- [2.9 (2025-08-08)](#29-2025-08-08)
- [2.8.3 (2025-07-30)](#283-2025-07-30)
- [2.8.2 (2025-07-09)](#282-2025-07-09)
- [2.8.1 (2025-06-10)](#281-2025-06-10)
- [2.8 (2025-05-20)](#28-2025-05-20)
- [2.7.3 (2025-04-15)](#273-2025-04-15)
- [2.7.2 (2025-03-26)](#272-2025-03-26)
- [2.7.1 (2025-03-05)](#271-2025-03-05)
- [2.7 (2025-02-12)](#27-2025-02-12)

## 2.11.2 (2026-08-04)

### Aggiunto (Added)
- Aggiunti doppi widget (numero e totale) per Fatture, Ordini e DDT
- Aggiunto widget con il totale impianti nelle Statistiche di Anagrafiche (#1420)

### Modificato (Changed)
- Modificate le opzioni del selettore per il backup
- Modificato l'helper per il ricalcolo delle scadenze
- Modificati i colori dei pulsanti dei messaggi di avviso
- Migliorata la visualizzazione dei testi lunghi nelle colonne delle viste
- Aggiornate le librerie esterne (Composer e npm)
- Aggiornate le istruzioni di installazione

### Fixed
- Corretta trigger evasione nel caso di note di credito a storno di fatture collegate a ddt
- Corretto rilevamento relazione bloccata
- Corretto percorso ricevute in importazione
- Corretta inizializzazione imponibile a 0 per evitare eccezioni con NULL
- Corretta notifica eliminazione sessione tecnico
- Corretta lettura fatture per compatibilità
- Corretta lettura stato fattura
- Corretta rimozione link cliente duplicato in impianti
- Corretto link allo stato impianto
- Corretta gestione seriali evasi in attività
- Corretto tasto eliminazione promemoria
- Corretta SQL injection modulo segmenti
- Corretti caratteri accentati su traduzioni
- Corretto stile campi input di colorpicker
- Corretto footer template di stampa fatture
- Corretta inizializzazione variabile template
- Corretti movimenti contabili per registrazione pagamento automatico
- Corretta apertura modal via ajax da sottocartelle
- Corretta #1852 Ricerca globale restituisce un solo risultato quando il modulo ha un segmento con filtro non vuoto
- Corretto colore select contratti
- Corretta verifica disponibilità quantità in evasione ordini in attività
- Aggiornamento openapi client
- Corretta estrazione valori iban
- Corretta la gestione dell'evasione delle quantità per le righe di tipo sconto e descrizione, anche nei DDT
- Ripristinata la visualizzazione delle strisce colorate di evasione nelle righe dei documenti
- Corretto il collegamento del preventivo alle righe dei documenti
- Corretto il troncamento delle descrizioni nelle righe delle fatture elettroniche
- Corretti i segni degli importi nelle note di credito in fatturazione elettronica
- Corretti i riferimenti ai documenti di vendita nelle fatture elettroniche
- Corretta una variabile nell'importazione delle fatture elettroniche
- Corretto l'invio delle fatture elettroniche da azioni di gruppo (codice ERR - Trasmissione non riuscita)
- Corretto l'attributo name nella generazione della fattura elettronica
- Corretta la gestione delle scadenze delle note di credito
- Corretto il conteggio dei tipi di scadenze (#1893)
- Corretta l'impostazione della data nelle presentazioni bancarie
- Corretto il calcolo dell'IVA per la riga marca da bollo
- Corretto il filtro del segmento Scadenzario completo
- Corretto l'ordinamento dei record nel modulo Fatture di vendita
- Corretto il cambio di stato automatico tra documenti collegati
- Corretto l'aggiornamento dello stato dei DDT da azioni di gruppo
- Corretto il controllo sulle categorie dei contratti
- Corretta la visualizzazione delle categorie
- Corretto il recupero della lista mail e la ricerca per destinatari secondari
- Corretto il controllo del token scaduto nell'invio delle mail
- Corretto il salvataggio dei template email
- Corretto l'inserimento delle liste nella newsletter
- Corretta la gestione degli indirizzi contenenti il carattere doppio apice
- Corretto il filtro per sede nel selettore delle anagrafiche
- Corretta la vista Anagrafiche nascondendo le sedi eliminate
- Nascoste le sedi eliminate dal selettore delle sedi
- Escluse dal selettore le tipologie di attività eliminate
- Corretta la preselezione del tipo di attività nell'importazione
- Corretta l'assegnazione del tecnico decisa dall'app
- Corretta la visualizzazione della card Campi personalizzati e la segnalazione di record non modificati
- Corretto il pulsante elimina: se si annulla la conferma il record non viene più eliminato
- Corretto il plugin Pianificazione interventi nella gestione e nel salvataggio dei promemoria
- Corretta la visualizzazione delle stampe inviate
- Corretto il selettore File da accodare nelle Stampe
- Ripristinato l'ordinamento delle colonne nelle viste dei moduli
- Rimossa la condizione ridondante deleted_at dalle query delle viste
- Corretta la creazione al volo degli articoli
- Corretta la quantità disponibile nel plugin Giacenze degli articoli
- Corrette le voci di riferimento dei documenti collegati ai seriali (#1922)
- Corretta la gestione del backup escludendo gli allegati
- Corretto il redirect dopo l'aggiunta di un record al volo
- Corretto il controllo sulla tipologia di anagrafica
- Corretta la query di verifica degli aggiornamenti di versione
- Corrette le query di aggiornamento del database
- Corretto il filtro delle date di sincronizzazione e la logica di updated_at negli endpoint API
- Aggiunto fallback sull'ultima ricevuta per l'avviso delle fatture scartate
- Corretta l'importazione delle ricevute via cron


## 2.11.1 (2026-06-30)

### Modificato (Changed)
- Aggiornamento dipendenza protobufjs

### Fixed
- Corretta visualizzazione limitata alle sedi abilitate nel plugin movimenti
- Corretta gestione giacenze sedi in base ai permessi
- Corretta query vista articoli per righe duplicate
- Corretta impostazione foto articolo da import CSV
- Corretto avviso uscita documento nei contratti
- Corretta impostazione cc e bcc da template in invio mail
- Corretta eliminazione anagrafiche da azioni di gruppo
- Corretta gestione indirizzo di risposta alle mail inviate
- Corretta creazione contratto con tipologie di attività disabilitati
- Corretto completamento automatico dati bancari
- Corretta inclusione file per modifica iva righe da bulk
- Corretto grafico ore interventi per tipologia (conteggia correttamente tutte le sessioni)
- Corretta stampa riepilogo interventi
- Corretto ordine colonne con query order by che vanno a capo
- Corretta query liste newsletter
- Corretta compatibilità PHP <= 8.5 (fputcsv legacy escape)
- Corretto problema evasione quantità ddt durante import FE
- Corretto tasto invia in modale di invio mail
- Corretto selezione allegati in invio mail azienda
- Corretto visualizzazione impianti in stampa contratti
- Corretti valori della ricerca con tema chiaro
- Corretto errore in firma GDPR e Interventi
- Corretto fullcalendar in selezione data e ora in pausa sessione
- Corretto refuso che non permette la disabilitazione degli hooks in Stato dei servizi
- Rimosso pulsante crea per anagrafiche con relazione bloccata
- Corretta selezione seriali in fatture di vendita
- Corretta descrizione riga in importazione fattura elettronica
- Corretta query vista Articoli per compatibilità con MariaDB
- Corretta select agenti
- Corretto avviso in fase di salvataggio fattura in stato pagato
- Corretta creazione immagine docker
- Corretto aggiornamento del tooltip con le informazioni dell'attività dopo il trascinamento
- Corretto Unauthenticated RCE via PHP Code Injection in Configuration Wizard
- Corretto IDOR in OAuth2 Callback Allows Unauthenticated Token Destruction for Any Account
- Corretto SQL Injection via Unsanitized json_decode in download-zip-allegati Action
- Corretto IDOR in view.php Allows Any Authenticated User to Download Arbitrary Uploaded Files
- Corretto Stored XSS via CSV Import Preview — Unescaped Cell Values Rendered in HTML
- Corretto Reflected XSS via unsanitized period_start/period_end GET parameters embedded in JavaScript
- Corretto Stored XSS via Content-Type Mismatch in Image File Preview
- Corretto Token Record-Level Access Control Bypass via editor.php IDOR
- Corretto Stored SSRF via HTML Injection in PDF Generation (mPDF img tag fetching)
- Corretto Unauthenticated OAuth2 Token Reset and Injection via IDOR in oauth2.php
- Corretto IDOR in API AppResource Allows Any Authenticated User to Read/Modify Any Record
- Corretto SQL Injection in ajax_select.php via unsanitized options[idtipiintervento] parameter in tipiintervento endpoint
- Corretto SQL Injection in ajax_select.php via unsanitized options[dir] parameter in tipi_documento endpoint
- Corretto Missing Authorization on Settings API Allows Any Authenticated User to Read and Modify Sensitive API Keys and Secrets
- Corretta potenziale esecuzione comandi shell da valori ambiente (code scanning alert)


## 2.11.0 (2026-06-09)
### Aggiunto (Added)
- Aggiunte azioni di gruppo per duplicazione massiva documenti
- Aggiunta colonna Data insoluto in Fatture di vendita
- Aggiunta colonna Data pagamento rate in Fatture di vendita
- Aggiunta colonna Validità in Contratti
- Aggiunta descrizione aliquota iva in stampa preventivo
- Aggiunta gestione campo nome in Ordini
- Aggiunta gestione pausa sessioni
- Aggiunta gestione per conto di in scheda anagrafica
- Aggiunta gestione di automezzi e giacenza automezzi nella sincronizzazione da app
- Aggiunta impostazione per disabilitare controllo sessione
- Aggiunta impostazione per limitare conteggio ore ad oggi nell'intestazione
- Aggiunta impostazione ritardo apertura tooltip dashboard
- Aggiunta informazione Token di sessione e Ultimo login riuscito nella scheda info dell'utente
- Aggiunta la possibilità di selezionare la sede di destinazione per lo scarico degli articoli in fase di import fattura elettronica
- Aggiunta possibilità prevedere collegamenti sulla barra di navigazione superiore definendoli da DB
- Aggiunte colonne Agente e Categoria in widget scadenza contratti in dashboard
- Aggiunto modulo Link navbar sotto Strumenti per gestire link sulla navbar
- Aggiunto campo stato su impianti e relativo modulo di gestione
- Aggiunte commissioni per riba insolute in banche e in registrazione insoluto fattura
- Aggiunta delle varianti da articolo
- Adeguamento tracciato SDI 1.9.1
- Aggiunta azioni di gruppo per aggiornamento massivo agenti principali nelle anagrafiche
- Ricerca globale cerca ora tra le note interne
- Aggiunta colonna Conducente al modulo Automezzi
- Gestione installazione database tramite file install.sql per prima installazione
- Aggiunta gestione timezone in API esportazione calendario
- Aggiunta gestione login OAuth2 con Keycloak
- Aggiunta gestione rinnovo contratto con ore residue
- Aggiunta gestione tipi di intervento abilitati nei contratti e aggiunta righe per tipi di intervento abilitati
- Aggiunta gestione firma GDPR con selezione condizioni
- Aggiunta gestione flag Attivo in aliquote iva
- Aggiunta gestione flag abilitato stati impianti
- Aggiunta stampa GDPR in anagrafica
- Aggiunta stampa liquidazione suddivisa per segmenti
- Aggiunta gestione ajax documenti collegati anagrafiche e articoli
- Aggiunta gestione caricamento documenti collegati via ajax
- Aggiunta creazione contratto da ordine
- Aggiunta importazione ordine in attività

### Modificato (Changed)
- Refactoring naming tabelle con snake_case (or_tipiordine → or_tipi_ordine, my_statiimpianti → my_stati_impianti, mg_unitamisura → mg_unita_misura, in_tipiintervento → in_tipi_intervento, in_statiintervento → in_stati_intervento, dt_tipiddt → dt_tipi_ddt, dt_statiddt → dt_stati_ddt, etc.)
- Refactoring naming colonne con snake_case (idmodule → id_module, idgruppo → id_gruppo, piva → p_iva, idanagrafica → id_, id_conto_fornitore, id_banca_vendite, id_sede_fatturazione, etc.)
- Allineamento query per compatibilità MariaDB
- Aggiornamento dipendenze per compatibilità PHP 8.5
- Aggiornamento dipendenze sweetalert, hotkeys-js,intervention/image, smartwizar, hotkeys-js
- Ottimizzazione controlli su integrità
- Ottimizzazione formato ICS
- Ottimizzazione importazione anagrafiche a blocchi per evitare duplicati
- Ottimizzazione importazione ricevute per evitare interruzioni in caso di ricevuta di scarto
- Ottimizzazione invio fatture elettroniche da coda di invio con campo fe_failed_at
- Ottimizzazione lettura file json moduli premium
- Ottimizzazione classi documenti e generazione numero progressivo
- Pulizia file da controlli integrità per evitare timeout
- Refactoring Dockerfile.dev per installare i pacchetti di sistema richiesti e le estensioni PHP
- Raggruppamento movimenti per idconto in emissione fatture
- Rimozione colonna indirizzo2 in an_anagrafiche e an_sedi
- Rinominato plugin Registrazioni in Contabilizzazione e modificato ordinamento
- Unificazione funzione di ordinamento righe
- Se non ci sono prezzi orari, al km o diritto chiamata, non inserire la riga in fattura
- Aumentata sanitizzazione POST e GET ad eccezione del corpo email
- Avviso in cambio stato fattura
- Correzione etichetta per esportazione iCal
- Modificata etichetta "Data di registrazione" con "Data di ricezione"
- Modificata impostazione Data inizio controlli su stati FE in Data inizio controlli Fatture di vendita
- Associazione articolo in fase di importazione fattura di acquisto
- Copia righe documenti disponibile anche per documenti bloccati
- Migliorata query colonna Validità in Contratti
- Miglioria grafica header interventi
- Miglioria grafica plugin consuntivo
- Miglioria grafica stampa riepilogo intervento con tabelle di riepilogo sessioni e materiale utilizzato
- Miglioria grafica tasti azione su righe
- Miglioria modale di aggiunta articolo
- Migliorie API calendar
- Migliorie grafiche plugin Componenti in Impianti
- Miglioria card header per documenti collegati
- Miglioria gestione documenti collegati per flusso di evasione
- Migliorata la gestione di modifica iva massiva righe documenti
- Introdotto il tour guidato per modulo Anagrafiche, articoli, contratti, ddt, fatture, impianti, interventi, ordini, preventivi e guida ai filtri in scadenzario
- Assegnazione numero progressivo ai ddt in uscita al momento della loro evasione
- Allineamento stampa consuntivo diviso per tipo di attività
- Avviso modifiche fattura di vendita per rigenerazione prima dell'invio

### Fixed
- Corretto Path Traversal via Unsanitized `directory` Field in ZIP Component Upload (RCE)
- Corretto Missing Authorization on Settings API (accesso non autorizzato a API keys)
- Corretto Read-Only Users Can Perform Write Operations via Missing Permission Check in actions.php
- Corretta inizializzazione dei select
- Corretto trigger evasione stato in base a quantità evasa
- Corretto invio email a cc in template email
- Corretto arrotondamento iva in vista fatture
- Corretti totali nella stampa fatture rispecchiano totali delle righe
- Corretta stampa fatturato per data fattura
- Corretta visualizzazione validità preventivo con valore corretto tradotto
- Corretto calcolo data validità preventivo
- Corretto flag completamento versione aggiornamento
- Corretta visualizzazione stampa mastrino in ordine per conto
- Corretta importazione fattura
- Corretta importazione fattura di acquisto con ddt o ordine collegato
- Corretto controllo fatture da inviare
- Aggiunte al controllo fatture da inviare anche le fatture non generate
- Corretta visualizzazione ritenuta in fattura
- Corretto addebito spese di incasso in fattura
- Corretto errore copia e incolla righe documenti
- Corretto HTML Injection in modules/utenti/edit.php
- Corretto ordinamento plugin in ordine crescente
- Corretta ricerca riferimenti in importazione fattura per riga con articolo non esistente
- Corretto suggerimento codice sdi in anagrafiche per compatibiltà MariaDB
- Rimosso blocco per inclusione modal modifica iva delle righe
- Corretta dimensione campo indirizzo in anagrafiche
- Corretta modifica script di aggiornamento per evitare errori causati da classi modificate

## 2.10.4 (2026-04-28)

### Modificato (Changed)
- Ottimizzazione importazione anagrafiche a blocchi per evitare duplicati

### Fixed
- Corretto completamento suggerimento indirizzi email referenti in invio mail
- Corretto invio mail con allegato a utenti senza i permessi per il modulo Coda di invio
- Corretto template stampa ddt
- Corretto riferimento documenti collegati nelle righe
- Corretto addebito spese di incasso
- Corretto avviso fatturazione attività futura per date corrispondenti
- Corretto plugin movimenti e giacenze per sede
- Corretto upload allegati in ckeditor per moduli senza permessi
- Corretta funzione selezione segmento predefinito
- Corretto salvataggio allegati duplicati
- Corretto #1787 avviso nessuna banca di addebito impostata in fatture di acquisto
- Corretta importazione fatture di acquisto e vendita
- Corretto ordinamento movimenti articolo
- Corretta visualizzazione banca per fatture con metodo di pagamento sepa
- Corretta conversione valori checklist da ckeditor
- Corretta autenticazione con OAuth2
- Corretta traduzione mancante su tasto di creazione fattura da ddt


## 2.10.3 (2026-04-07)

### Modificato (Changed)
- Ottimizzazione formato ICS per esportazione calendario
- Ottimizzazione controlli su integrità
- Ottimizzazione lettura file JSON moduli premium
- Miglioria grafica stampa riepilogo intervento con tabelle di riepilogo sessioni e materiale utilizzato
- Miglioria tooltip sessione in dashboard
- Miglioria avviso in cambio stato fattura
- Correzione etichetta per esportazione iCal

### Fixed
- Corretto grafico vendite e acquisti in Statistiche
- Corretto template di stampa fatturato
- Corretto registrazione insoluto da tasto Registra insoluto
- Corretto avviso banca di addebito non selezionata per fatture con metodo di pagamento riba
- Corretto nome colonna tradotto da Modifica colonne
- Corretto scritture contabili da tasto Riapri documento
- Corretto colonna Conto in vista Fatture di acquisto è ora raggruppata per conto
- Corretto allegato pdf alle email in invio mail fatture da bulk
- Corretto emissione fatture con impostazione Data emissione fattura automatica abilitata
- Corretto verifica P.IVA destinatario prima dell'importazione FE
- Corretto caricamento tabelle senza ordinamento
- Corretto aggiunta conti in piano dei conti
- Corretto template stampa ddt
- Corretto generazione movimento autofattura in fase di import FE
- Corretto tooltip delle sessioni in dashboard viene ora visualizzata solo la sessione selezionata
- Corretto controlli integrità su widgets premium
- Corretto contatore differenze in campi personalizzati
- Corretto gestione caratteri per codice SIA
- Corretto spostamento log Laravel in logs/app.log e abilitazione rotazione giornaliera a 30 giorni
- Corretto addebito di spese di incasso automatiche per fatture non in bozza

## 2.10.2 (2026-03-17)
### Modificato (Changed)
- Ottimizzata la lettura dei file JSON dei moduli premium
- Ottimizzata l'importazione delle ricevute FE per evitare interruzioni in caso di ricevuta di scarto
- Modificata l'etichetta "Data di registrazione" con "Data di ricezione"
- Migliorata la gestione dei log Laravel spostandoli in logs/app.log con rotazione giornaliera a 30 giorni
- Miglioria grafica stampa riepilogo intervento con tabelle di riepilogo sessioni e materiale utilizzato

### Fixed
- Corretto link ai moduli per migliore accessibilità, il link è ora stato ridotto all'icona spostata prima della descrizione
- Corretta conversione valore negativi in esportazione fatture in XLS
- Corretta generazione fatture con righe descrittive da bulk
- Corretta creazione ordine fornitore da preventivo per righe descrittive
- Corretta generazione progressivo per maschere senza riferimento all'anno
- Corretto evasione seriali nel caso di seriali senza documento di acquisto associato
- Corretto movimento automatico rilevazione IVA
- Corretto filtro per anagrafica in stampa scadenzario
- Corrette vulnerabilità minori
- Corretta vulnerabilità Time-Based Blind SQL Injection via `options[stato]` Parameter
- Corretta vulnerabilità Remote Code Execution via Insecure Deserialization in OAuth2
- Corretta vulnerabilità SQL Injection via righe Parameter in confronta_righe Modals
- Corretta vulnerabilità SQL Injection via Aggiornamenti Module
- Corretto blocco campi numero e data per fatture elettroniche importate
- Corretta generazione movimenti applicando data registrazione fattura
- Corretta funzione ricalcolo scadenze per movimenti di apertura e chiusura
- Corretta eliminazione fatture collegate a interventi
- Corretta eliminazione fatture da bulk
- Corretto errore duplica ordine cliente/fornitore (#1773)
- Corrette vulnerabilità SQL injection attraverso l'utilizzo della funzione prepare() per l'escaping delle variabili nelle query SQL
- Corretta impostazione di max_rows a 0 per prevenzione divisione per 0
- Corrette le etichette delle date di registrazione/ricezione
- Corretto applicazione tema in tempo reale al cambio della select, senza attendere refresh della pagina
- Corretto redirect in aggiornamento
- Corretto aggiornamento campo uid in interventi tecnici
- Corretto raggruppamento per fattura in mastrini piano dei conti
- Corretta query vista modulo Stati dei preventivi
- Corretto conteggio documenti collegati ad aliquota IVA
- Corretta query eliminazione categorie
- Corretta eliminazione IVA da bulk
- Corretto raggruppamento movimenti piano dei conti
- Corretti raggruppamenti movimenti contabili
- Aggiunto warning nel log per tag con JSON malformato

## 2.10.1 (2026-02-26)
### Modificato (Changed)
- Ottimizzazione invio fatture elettroniche da coda di invio con campo fe_failed_at
- Invio fatture elettroniche da bulk ora prevede tre tentativi di invio prima di rimuovere le fatture dalla coda
- Ottimizzazione query con Laravel
- Ottimizzazione plugin consuntivo
- Ottimizzazione classi documenti e generazione numero progressivo
- Miglioria modale di aggiunta articolo
- Miglioria grafica header interventi
- Miglioria grafica tasti azione su righe
- Migliorata la gestione di modifica iva massiva righe documenti
- Copia righe documenti disponibile anche per documenti bloccati

### Fixed
- Corretta visualizzazione documenti collegati a contratti per attività collegate al contratto
- Corretto conteggio ore in header
- Corretta distinzione somma ore sessioni da conteggiare per consuntivo
- Corretto logging invio fatture da coda di invio
- Se l'articolo è un servizio, disabilito i plugin Movimenti e Giacenze
- Corretta impostazione OAuth2
- Corretta vulnerabilità Unauthenticated privilege escalation
- Corretto calcolo ore totali contratto che non venivano sommate
- Corretto reset cache hooks per evitare avviso cron non impostato correttamente
- Corretti riferimenti plugin per importazione XML acquisto con modulo Fatture di vendita disabilitato
- Corretta modifica nome categorie e sottocategorie per sottocategorie duplicate
- Corretto conteggio righe per CIG e CUP in calcolo righe per generazione fattura
- Corretta selezione stato ordine in creazione documento
- Corretto errore 500 se display non definito (#1765)
- Corretta visualizzazione costi sessione
- Corretto caricamento plugin consuntivo nel caso di documento eliminato
- Corrette stampa liquidazione IVA e registro IVA
- Corretta funzione in update.php per prevenire attacchi XSS
- Corretta visualizzazione quantità disponibile per sede in select articoli nei documenti
- Corretta sanitizzazione per prevenire SQL injection
- Corretta generazione fattura elettronica per sedi committente paesi esteri
- Corretti calcoli automatici orari in modifica sessione
- Corretta selezione tecnico in automezzi limitata ad anagrafiche di tipo tecnico
- Corretta selezione data in aggiunta danno plugin Danni in Automezzi (#1758)
- Corretto errore visualizzazione modello in vista Articoli (#1756)
- Corretto l'accesso alla visualizzazione della pagina info dell'utente non loggato
- Corrette vulnerabilità minori
- Corretta corrispondenza anagrafica in controllo di integrità XML e documenti di vendita
- Corretto riferimento fattura di acquisto nel caso di autofatture
- Corretta gestione errori in fase di invio email
- Corretto percorso suggerito per la configurazione del cron, non veniva riportata la root dir
- Corretta esclusione file .env dalla release
- Corretta visualizzazione costi sessione
- Corretta larghezza campo codice in stampa DDT
- Corretto dettaglio prezzi sessioni e calcolo sconto
- Corretta creazione immagine docker

## 2.10 (2026-02-05)
### Aggiunto (Added)
- Aggiunto controllo sui widget personalizzati in aggiornamenti
- Aggiunta variabile interventi {email_user} per gestione reply_to in email
- Aggiunta gestione registrazione danni automezzi
- Aggiunta esportazione xml per la liquidazione IVA
- Aggiunta gestione tariffe tipi interventi in base alla sede cliente
- Aggiunta registrazione contabile automatica in base al pagamento
- Aggiunta bulk esportazione stampe viaggi automezzi
- Aggiunta azione di gruppo per l'aggiornamento massivo degli agenti principali nelle anagrafiche
- Aggiunta costi, margine e rincaro anche su contratti
- Aggiunta impostazione unità di misura predefinita per aggiunta articoli
- Aggiunta gestione controlli file e database per moduli aggiuntivi
- Aggiunta gestione attivazione/disattivazione correttore ortografico editor
- Aggiunta impostazione per nascondere il numero totale dei record delle tabelle
- Aggiunta gestione importazione delle fatture elettroniche di acquisto in automatico e in modalità semplificata
- Aggiunta duplicazione listino selezione anagrafiche collegate e azione di gruppo per aggiornare lo sconto
- Aggiunta gestione sconto combinato nello sconto delle righe dei documenti e in stampa
- Aggiunta selezione su campi da aggiornare da scheda articolo nelle righe dei documenti
- Aggiunta scelta del tipo garanzia articolo con opzione giorni, mesi, anni
- Aggiunta campo input per data inventario
- Aggiunta gestione soft delete delle sedi
- Aggiunta prevista opzione per nascondere i codici articolo in stampa contratti
- Aggiunta gestione viaggi
- Aggiunta gestione deleted_at sugli utenti per nascondere utenti eliminati dalla lista
- Aggiunta pre-selezione barcode automatico alla creazione di un nuovo articolo
- Aggiunta lingue vista sede destinazione in preventivi
- Aggiunta colonna sede destinazione in vista preventivi
- Aggiunto in controlli gestionale il controllo per i file .htaccess di sistema
- Aggiunta la gestione del tecnico in modifica sessione
- Aggiunta la gestione dell'inventario
- Aggiunti e migliorati testi in inglese
- Aggiunta api per la sincronizzazione dei movimenti fatti dal tecnico
- Aggiunta aggiornamento api app per introduzione filtri gruppi e tipi anagrafiche nei tipi attività
- Aggiunte API per Laravel
- Aggiunto token di sessione per prevenire accessi multipli da parte dello stesso utente
- Aggiunta sessioni tecnici multiple
- Aggiunti header per moduli con accesso tramite token (anagrafiche e impianti)
- Aggiunta gestione key in filelist upload
- Aggiunta descrizione aggiuntiva personalizzata in fatturazione
- Aggiunta btn-default su pulsanti copia-incolla
- Aggiunto docker a dependabot
- Aggiunta verifica automatica aggiornamenti npm e composer
- Aggiunta attivazione del tasto copia su documenti bloccati
- Aggiunta funzione copia/incolla delle righe tra i documenti
- Aggiunta gestione stampa definitiva libro giornale e liquidazione IVA
- Aggiunta calcolo provvigioni agenti su utile o ricavo
- Aggiunta salvataggio sessioni via ajax
- Aggiunta abilitazione dinamica pulsante "Azioni di gruppo"
- Aggiunta conversione importazione dati aggiuntivi nelle fatture elettroniche
- Aggiunto riferimento al numero secondario nei documenti
- Aggiunta gestione data competenza nei documenti
- Aggiunta introdotti limiti API distinti per autenticati e non autenticati con chiavi dedicate
- Aggiunta valorizzazione dati_aggiuntivi_fe in importazione fatture acquisto
- Aggiunta ottimizzazione indici articoli
- Aggiunta API per la gestione delle notifiche
- Aggiunta logging in zz_operations per cambio stato intervento e aggiunta sessione
- Aggiunta gestione utenti in tipi attività
- Aggiunta aggiunto rate limiting API con Illuminate RateLimiter e store su file
- Aggiunta risorsa api per la gestione dei token fcm per l'invio di notifiche all'app
- Aggiunta aggiunta protezione contro directory listing nella cartella files
- Aggiunta api per la verifica di modifiche sui record per app
- Aggiunta impostazione per consentire l'inserimento di allegati in attività completate
- Aggiunta pulsante generazione etichetta impianto con creazione automatica di un accesso tramite token diretto o otp
- Aggiunta azione di gruppo crea lista in anagrafiche
- Aggiunta aggiunta tipo attività nelle variabili per attività
- Aggiunta pulsante eliminazione per conti di livello 2
- Aggiunta sistema di redirect post-login
- Aggiunta tipo anagrafica in tipologie interventi
- Aggiunta registrazione richieste API nei log delle operazioni
- Aggiunta ripristino checklist impianti nelle attività
- Aggiunta gestione ddt di rientro
- Aggiunta inversione flag opt-out e azione di gruppo export
- Aggiunta stampa barcode da righe ddt
- Aggiunta lettura cron spazio sul disco in aggiunta ed eliminazione allegati
- Aggiunta aggiunta controllo su cron
- Aggiunta aggiunta iban e totale nei template email di fatture

### Modificato (Changed)
- Migliorata l'azione di gruppo di aggiornamento prezzi listini articoli
- Migliorata la gestione dei cespiti
- Migliorati i permessi sul modulo Token OTP
- Spostata la verifica dei permessi di upload/eliminazione allegati su classe
- Spostati i flag dei moduli in zz_modules_flags
- Allineamento requisiti workflow GitHub Actions
- Miglioria grafica logs
- Aggiornamento libreria ua-parser-js per gestione icone
- Aggiornamento librerie (chart.js, hotkeys-js, jquery, protobufjs, smartwizard)

### Fixed
- Corretta l'impostazione del log di invio per singola fattura in fase di invio email da azioni di gruppo
- Corretta iva calcolata su ritenuta in tabella riepilogo iva della stampa fattura
- Corretta stampa fattura con dicitura iva che veniva impostata a centro pagina
- Corretta navigabilità nella configurazione del database
- Corretti gli avvisi in fase di login utente
- Corretta visualizzazione cancel button default per swal
- Corretta stampa sede in riepilogo interventi
- Corretta data per calcolo progressivo contratto in duplicazione contratto anno precedente
- Corretto utilizzo di insert ignore per evitare errore js in fase di modifica permessi utenti
- Corretta correzione salvataggio firma
- Corretta impostazione banca di accredito predefinita in fattura in base a impostazione specificata in anagrafica cliente
- Corretto link alla documentazione
- Corretta stampa registro iva acquisti per fatture a cavallo dell'anno
- Corretto aggiornamento quantità da importazione articoli dove non è specificata la data
- Corretta l'impostazione del giorno scadenza per regole pagamenti
- Corretto percorso base_path
- Corretta query installazione plugin
- Corretta miglioria controllo su quantità in creazione documento, che viene ora abilitata in base all'impostazione Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita
- Corretta esclusione controllo sulle quantità per i documenti con direzione uscita
- Corretto raggruppamento righe per ordine in fase di selezione ordine da importare nei documenti
- Corretta selezione banca addebito nel caso di pagamento tramite Ri.Ba.
- Corretto calcolo quantità disponibile articoli in fase di generazione ddt da ordine
- Corretto avviso per uscita da fattura di acquisto
- Corretto calcolo arrotondamento in importazione fattura di acquisto se indicato in maniera errata nell'xml
- Corretta importazione fatture con tipo documento e data registrazione mancanti in xml
- Corretto refuso nome documento in creazione ddt da preventivo
- Corretto script creazione immagine docker
- Corretta prevenzione sql injection

## 2.9.8 (2025-12-23)
### Modificato (Changed)
- Migliorata la gestione delle aliquote IVA con aggiunta di un controllo sulla presenza della natura IVA per aliquote esenti
- Aggiornamento foglio di stile asso software per generazione XML
- Miglioria controllo su importazione fatture

### Fixed
- Corretta la gestione dei barcode collegati ad articoli eliminati
- Corretto l'avviso sulle risorse in scadenza
- Corretta la generazione di scadenze relative a fatture con marca da bollo
- Corretta la visualizzazione della ragione sociale del fornitore nella sezione Ultimi 20 prezzi di acquisto in Articoli
- Corretta la stampa inventario
- Corretta la stampa dei contratti
- Corretta l'impostazione del pagamento predefinito dell'anagrafica in aggiunta di una nuova attività
- Corretta l'aggiunta di marca e modello in fase di creazione di un impianto
- Corretta l'aggiunta di una prima nota dall'edit prima nota
- Corretta la stampa della provvigione agente
- Corretto il salvataggio della configurazione Oauth2
- Corretto l'avviso
- Corretta l'associazione di permessi ai gruppi di utenti
- Corretto l'invio mail da terminale durante l'esecuzione del cron
- Corretta la vista di Accesso con OAuth
- Corretta l'aggiunta di una categoria
- Corretta la visualizzazione di articoli collegati a una marca
- Corretta l'aggiunta di una categoria file
- Corretto il calcolo della quantità evasa delle righe preventivo per problema in fase di eliminazione fattura creata da preventivo con quantità eccedenti la disponibilità a magazzino
- Corretto l'ordinamento dei movimenti degli articoli da plugin per data movimento
- Corretta la gestione dei seriali in note di credito collegate a fatture
- Corretto il redirect al modulo fatture da plugin serial, nel caso di note di credito
- Corretta la gestione dei seriali rientrati a magazzino tramite nota di credito
- Corretta la selezione del tipo documento in fase di generazione nota di credito

## 2.9.7 (2025-12-09)
### Modificato (Changed)
- Miglioria avviso allegati anagrafiche

### Fixed
- Corretti riferimenti in registrazione contabile da bulk
- Corretta visualizzazione riferimenti import FE
- Corretta aggiunta ordini in attività
- Corretto cambio stato contratti
- Corretto controllo su iban
- Corretta migrazione immagini marche
- Corretto caricamento allegati
- Corretta sottocategoria in vista contratti
- Corretto salvataggio allegati
- Corretta rivalsa in FE
- Corretta associazione sottocategoria a impianto
- Corretta Importazione fatture da zip
- Corretto avviso tasto importa in sequenza
- Corretto salvataggio firma da app
- Corretti allegati fatture elettroniche

## 2.9.6 (2025-11-26)
### Modificato (Changed)
- Miglioria grafica controlli gestionale in Aggiornamenti
- Miglioria log caricamento file aggiornamento 
- Miglioria gestione immagini impianto, articolo e firme allegati
- Gestione indirizzo apilayer con solo indirizzo valorizzato
- Rimozione blocco generazione autofatture a 0

### Fixed
- Corretto log invio fatture da bulk
- Corretto calcolo spazio occupato
- Corretta lettura informazioni su services
- Corretta stampa inventario con filtri
- Corretta visualizzazione highlight righe documenti alla modifica
- Corretta selezione seriali
- Corretta gestione assenza file modules.json e views.json
- Corretta aggiunta record multilingua mancanti a database
- Corretta lettura cache Informazioni su services
- Corretta visualizzazione servizi
- Corretta segnalazione compatibilità
- Corretto background red widget contratti in scadenza
- Corretta aggiunta stato ddt
- Corretta salvataggio categorie contratti
- Corretto title stampa bilancio

## 2.9.5 (2025-11-12)
### Modificato (Changed)
- Miglioria widget rate contrattuali con visualizzazione ottimizzata
- Controllo integrità files allegati per garantire la consistenza dei dati
- Verifica title assenti database per completezza delle informazioni

### Fixed
- Corretta visualizzazione importo stampa preventivo
- Corretta duplicazione fattura con marca da bollo
- Corretto export FE dati aggiuntivi
- Corretta visualizzazione pagamento in stampa preventivo
- Corretta query vista modulo Log eventi
- Corretta sincronizzazione su app degli articoli eliminati
- Corretta importazione barcode articoli
- Corretta generazione autofattura per reverse charge
- Corretta maggiorazione nei template
- Corrette API allegati
- Corretto elenco tipi di spedizione in ordini
- Corrette anomalie ammortamenti/cespiti
- Corretto carattere non supportato XML
- Corretto avviso per valore richiesto al login
- Corretta impostazione idconto righe

## 2.9.4 (2025-10-28)
### Modificato (Changed)
- Migliorata la ricerca articoli per codice descrizione
- Migliorato il modulo Aggiornamenti con ulteriori controlli
- Ottimizzazione import listini cliente, preventivi, impianti, articoli
- Miglioria gestione banche nei preventivi per una gestione più accurata
- Rimozione console.log per pulizia del codice
- Su marche, parent = 0 diventa NULL per coerenza database
- Rimozione warning da riga di comando per output più pulito

### Fixed
- Corretto salvataggio inline nuova scadenza non funzionante
- Corretto allineamento larghezza input field e icon button nella pagina reset password e login
- Corretta generazione stampe contabili definitive
- Corretto ordinamento viste non rispettato
- Corretta gestione valori a null database per evitare errori
- Rimosso BountySource dal README (servizio non più esistente)
- Corretta visualizzazione barra dei plugin non corretta
- Corretto capitale sociale opzionale permettendo salvataggio a null
- Corretta formattazione colonne formato data
- Corretto svuota cache hooks non funzionante
- Corretta generazione query risoluzione problemi database
- Corretto inserimento ordine in attività con errori
- Corretta applicazione filtri segmenti non funzionante

## 2.9.3 (2025-10-14)
### Modificato (Changed)
- Ottimizzate le query dei moduli **Ordini cliente**, **Ordini fornitore**, **DDT in uscita**, **Utenti e permessi**, **Preventivi**, **Fatture di vendita**, **Anagrafiche**, **Zone**, **Marche**.
- Migliorata la visualizzazione dello stato giacenze negli **Articoli** con colori più chiari
- Semplificazione query modulo Articoli per lista barcode per migliorare le prestazioni
- Ottimizzazione caricamento moduli per migliorare i tempi di risposta
- Miglioramento grafico hooks per una visualizzazione più chiara
- Ottimizzazione sincronizzazione barcode su app mobile

### Fixed
- Corretta la gestione dell'invio via mail per i token OTP
- Corrette le dimensioni del QR Code nelle stampe
- Corretti i riferimenti alle email inviate nei vari moduli per una visualizzazione accurata
- Corretta verifica esigibilità IVA non funzionante correttamente
- Corretta gestione barcode non corretta in alcuni moduli
- Corretti filtri fantasma che apparivano nelle tabelle senza essere configurati
- Corretto avviso fatture con ricevuta di scarto non visualizzato correttamente
- Corretta ricerca articoli nei documenti che non restituiva risultati corretti
- Corrette query installazione che causavano errori durante l'aggiornamento
- Corretto pannello servizi che non caricava correttamente le informazioni
- Corretto bug che impediva il cambio password tramite l'interfaccia di modifica utente
- Corretto ridimensionamento viste non corretto su schermi diversi
- Corretta ricerca piano dei conti non funzionante
- Corretta notifica aggiornamento non visualizzata correttamente
- Corretta impostazione tipo anagrafica in import FE non corretta
- Corretta selezione modulo iniziale che non rispettava le impostazioni utente
- Corretta ricerca articoli che restituiva risultati non pertinenti
- Corretto invio sollecito scadenze selezionate non funzionante
- Corretto ordinamento tabelle che non rispettava i criteri impostati
- Corretta ricerca e visualizzazione fatture da importare non funzionante
- Corretta esportazione RIBA con errori nei dati esportati
- Corretto calcolo arrotondamento per fatture elettroniche non corretto
- Corretto link stampa su menu a tendina in invio email non funzionante
- Corretta esecuzione cron da riga di comando con redirectHTTPS attivo che causava errori
- Corretto import righe fatture elettroniche con quantità non definita ma prezzo unitario definito
- Corretto salvataggio seriali vendita al banco non funzionante
- Corretti permessi per modulo Accesso con Token/OTP mancanti
- Corretta gestione XML righe senza quantità che causava errori di importazione
- Corretta aggiunta pagamenti che non funzionava correttamente

## 2.9.2 (2025-09-25)
### Modificato (Changed)
- Ottimizzate le chiavi esterne per migliorare le prestazioni del database
- Ottimizzato il modulo Statistiche vendita per prestazioni migliorate
- Ottimizzate le tabelle per caricamento più veloce
- Ottimizzato il caricamento moduli per migliorare i tempi di risposta
- Aumento minuti di attesa per ricaricamento hooks per maggiore stabilità
- Rimossa esecuzione hooks se finestra senza focus per ottimizzare le prestazioni
- Ottimizzata la lettura delle impostazioni
- Rimozione warning da riga di comando per output più pulito
- Rimozione impostazione non più utilizzata per pulizia configurazione
- Rimozione record orfani tabelle per pulizia database
- Ottimizzata la ricerca di articoli nei documenti

### Fixed
- Corretta la query del modulo Articoli per lista barcode
- Corretta l'importazione delle fatture con split payment
- Corretta l'impostazioen di filtri fantasma nelle tabelle
- Corretto l'avviso di fatture con ricevuta di scarto
- Corretta la visualizzazione del pannello dei servizi
- Corretto il cambio password dall'interfaccia di modifica utente
- Corretta la sincronizzazione barcode su app
- Corretta la ricera sul piano dei conti
- Corretta la notifica di aggiornamenti disponibili
- Corretta l'impostazione del tipo anagrafica in importazione delle fatture
- Corretto l'invio del sollecito delle scadenze selezionate
- Corretta l'espansione del menu
- Corretta la ricerca e visualizzazione fatture da importare
- Corretta l'esportazione delle Ri.Ba.
- Corretta l'esecuzione cron da riga di comando con redirectHTTPS attivo
- Corretta l'importazione di fatture di acquisto con righe con qta non definita ma prezzo unitario definito
- Corretta la gestione dei permessi per modulo Accesso con Token/OTP


## 2.9.1 (2025-09-02)
### Modificato (Changed)
- Migliorata la gestione di hook e tasks
- Migliorate le prestazioni
- Migliorato l'avviso per cron non configurato correttamente
- Migliorata la barra di ricerca globale
- Migliorata la gestione del select articoli nei documenti
- Migliorata la visualizzazione delle notifiche

### Fixed
- Corretto testo caricamento ricerca
- Corretta ricerca impianti in plugin da attività
- Corretta la visualizzazione della barra dei plugin
- Corretta impostazione sede per movimenti da app
- Corretta query vista Articoli
- Corretto ordinamento tabelle
- Corretta impostazione ricorrenze in creazione nuova attività
- Corretta impostazione tecnici assegnati in add intervento
- Corretta la ricerca di aggiornamenti
- Corretto caricamento mappa nell'header degli interventi
- Corrette le note interne
- Corretta la selezione anagrafiche, ricerca raggio e filtri nel modulo mappa
- Corretta l'espansione dei sottomenu
- Corretto caricamento di altre operazioni in stato dei servizi
- Corretta gestione stati dei ddt
- Corrette le icone dei moduli in Tabelle
- Corretto spostamento modulo Categorie contratti
- Corretta visualizzazione categorie allegati
- Corretta ricerca cache hooks
- Corretto modulo Marche
- Corretto aggiornamento sottocategoria
- Corretta impostazione prezzo di acquisto fatture
- Corretta impostazione causale movimento
- Corretto select in aggiunta adattatore
- Corretta navigazione pagine datatables
- Corretti tasti in modal footer
- Corretta aggiunta segmenti
- Corretta impostazione gruppo utenti

## 2.9 (2025-08-08)
### Aggiunto (Added)
- Aggiunto footer predefinito ai template email
- Aggiunta gestione log richieste API
- Aggiunta gestione attivazione task
- Aggiunto salvataggio allegati originali in fase d'invio mail
- Aggiunto log gestione SMS
- Aggiunta gestione cespiti e ammortamenti
- Aggiunto miglioramento gestione log task con modulo di consultazione eventi
- Aggiunta possibilità di allegare immagini alle checklist
- Aggiunta scelta quantità di etichette da stampare
- Aggiunta gestione login tramite OTP/Token
- Aggiunta gestione risconti
- Aggiunta gestione link nei menù a tendina
- Aggiunta impostazione per raggruppare le righe con stesso articolo e barcode nei DDT
- Aggiunta gestione barcode multipli articolo sui documenti
- Aggiunta implementazione plugin Barcode per articoli e miglioramento gestione codici a barre
- Aggiunto publiccode.yml per software PA
- Aggiunta sezione ultime operazioni in stato dei servizi
- Aggiunta colonna Rif. fattura in Contratti
- Aggiunta visualizzazione anagrafica ultime vendite/acquisti
- Aggiunto riferimento prima nota nelle scadenze

### Modificato (Changed)
- Miglioria grafica widget Rate contrattuali
- Caricamento documenti collegati a tipo di attività via ajax

### Fixed
- Corretto ordinamento tabelle
- Corretta eliminazione periodo temporale in statistiche
- Corretto calcolo bollo da widget rate contrattuali
- Corretto invio sollecito scadenza
- Corretto invio mail con allegati
- Corretto invio pec
- Corretta aggiunta prima nota da edit prima nota
- Corretta stampa ddt con sede di partenza e destinazione diverse
- Corretta inizializzazione barcode in template di stampa
- Corretta gestione note di credito nell'esportazione bonifici XML, venivano sommate
- Corretta anteprima importi su evasione documenti

## 2.8.3 (2025-07-30)
### Modificato (Changed)
- Migliorie grafiche minori
- Miglioria form selezione riferimenti

### Fixed
- Corretta importazione fatture da zip
- Corretti riferimenti per colonna email inviata nei moduli
- Corretta gestione sede per distinta base
- Corretto inserimento ordini
- Corretta ricerca datatables
- Corretta visualizzazione mappa
- Corretta aggiunta sottocategoria
- Corretta visualizzazione errata valori tabelle
- Corretta creazione intervento
- Corretta registrazione contabile da bulk
- Corretto problema abilitazione permessi utenti
- Corretta migrazione categorie impianto
- Corretta query migrazione categorie impianto
- Corretta aggiunta categoria e marca da modale su edit
- Corretto modulo backup
- Corretta importazione FE da zip
- Corretta aggiunta righe descrittive in creazione ordine fornitore da ordine cliente
- Corretti allegati senza categoria
- Corretta esclusione agenti da plugin Provvigioni

## 2.8.2 (2025-07-09)
### Modificato (Changed)
- Ottimizzata l'importazione delle fatture di acquisto per migliorare le performance
- Ottimizzato il caricamento delle fatture da importare in sequenza

### Fixed
- Corretta stampa fattura di vendita
- Corretta configurazione PHP ini per Docker
- Corretta visualizzazione tabella movimenti
- Corretto calcolo data prossima esecuzione task
- Risolta inaggiornabilità/installabilità moduli con templates
- Corretta importazione articoli collegati a ordini in importazione fatture di acquisto
- Corretto numero di ore in stampa riepilogo senza prezzi
- Corretto riferimento tabella zz_categorie
- Corretta stampa inventario in base a fattore moltiplicativo
- Corretto link modulo Aggiornamenti
- Corretta impostazione prezzo in base a tipo di documento creato
- Rimossi required non necessari
- Corretta selezione aliquota in cambio iva da bulk righe preventivi
- Ripristinato tasto add articolo in ImportFE
- Corretta compilazione conto e iva in ImportFE
- Ripristinata compilazione automatica codice e descrizione articolo in ImportFE
- Corretta sede in creazione fattura
- Corretta selezione prezzo di acquisto in aggiunta articolo da ImportFE
- Corretto template DDT senza prezzi
- Corretta visualizzazione link in aggiunta attività
- Corretto ordinamento tabelle
- Corretta ricerca datatables campi vuoti tramite '='
- Corretto tooltip al mouseover su icona
- Corretto redirect plugin ricevute FE
- Corretta associazione categoria allegati
- Corretto errore calcola percorso da mobile
- Corretta importazione fatture di vendita zip
- Corretto problema di aggiornamento data scadenza cache
- Corretta stampa fattura con sconto in fattura
- Corretta impostazione multiple con query


## 2.8.1 (2025-06-10)
### Modificato (Changed)
- Miglioria grafica menu dropdown
- Miglioria generazione stampe

### Fixed
- Corretta sovrascrittura vendor in fase di installazione
- Corretti e ottimizzati i filtri delle tabelle
- Corretto salvataggio partita iva, codice fiscale e note da plugin sedi aggiuntive
- Corretti movimenti di giroconto per fatture
- Corretta la stampa dei contratti
- Corretta creazione ordine per moduli tradotti
- Corretti tasti in anagrafica
- Corretta l'importazione degli articoli
- Corretta stampa preventivi con descrizione
- Corretto allineamento query vista Giacenze sedi
- Corretto bug cambio di stato automatico documenti
- Corretto import anche senza primary key
- Corretta navigazione record per moduli custom
- Corretta stampa ddt con sede destinazione diversa
- Corretto l'avviso di ricevute scadute in base alla data da impostazioni
- Corretto blocco importazione ricevute infinita
- Corretta selezione sede partenza azienda


## 2.8 (2025-05-20)
### Aggiunto (Added)
- Aggiunta stato documento in riferimento documenti collegati
- Aggiunta variabile header-font-size e font-size per le stampe
- Aggiunta tabella in consuntivo con totale delle ore, km, costi e totale scontato suddivisi per i mesi in cui sono stati effettuati gli interventi
- Aggiunta la modifica del cliente da azioni di gruppo in Impianti
- Aggiunta la gestione delle immagini nelle viste
- Aggiunto l'aggiornamento automatico della versione di OSM su docker
- Aggiunti il nome e indirizzo sede in stampa riepilogo interventi
- Aggiunta la colonna Residuo in Contratti
- Aggiunta la stampa liquidazione IVA definitiva
- Aggiunti nuovi filtri data
- Aggiunto pulsante ckeditor per generare testo tramite IA
- Aggiunta gestione IA con OpenRouter e Prompt di sistema per modello AI
- Aggiunto servizio di verifica iban tramite ibanapi
- Aggiunta la gestione delle descrizioni predefinite delle righe
- Aggiunte le colonne Note e bg in Categorie impianti
- Aggiunte le colonne Totale, Residuo e bg in Assicurazione crediti
- Aggiunto il campo Note in Impianti
- Aggiunta la modifica dell'aliquota IVA da azioni di gruppo sulle righe
- Aggiunto il colore per i tecnici in selezione tecnico
- Aggiunta l'importazione del marchio da CSV articoli
- Aggiunta la cifratura del backup tramite password
- Aggiunta tasto Seleziona tutti sui gruppi in Viste
- Aggiunta la correzione massiva dei problemi database
- Aggiunto l'invio dei PDF delle fatture da azioni di gruppo
- Aggiunto controllo di Importazione fatture elettroniche attiva solo se ZIP
- Aggiunta gestione campi obbligatori in fase di importazione di CSV
- Aggiunta l'associazione di categorie file a template per invio email con allegati da includere
- Aggiunta la possibilità di modificare i conti da plugin Movimenti contabili
- Aggiunta controllo disponibilità spazio per backup
- Aggiunta gestione dell'eliminazione righe prima nota
- Aggiunta la visualizzazione degli allegati categoria
- Aggiunta l'integrazione automatica IVA per reverse charge in fattura

### Modificato (Changed)
- Migliorata la visualizzazione grafica dei modal
- Migliorato il select impianti in Attività in base al contratto e sede
- Uniformate le operazioni da azioni di gruppo
- Rinominato il flag Completato in Bloccato
- Rimosse le dipendenze JS simili
- Migliorata la gestione e visualizzazione dei moduli Viste, Backup, Aggiornamenti, Segmenti, Utenti e permessi, Statistiche, Mappa, Stato dei servizi, Impostaizoni, Gestione task e Stampe contabili
- Migliorata la visualizzazione dei tooltips
- Riordinati i tasti nei plugin
- Bloccata la modifica di alcuni conti predefiniti
- Uniformate categorie e sottocategorie in Articoli e Impianti
- Uniformati marca e modello in Articoli e Impianti
- Aggiornate le dipendenze minime
- Migliorati gli header di Articoli e Attività
- Migliorati graficamente il form di login, configurazione, aggiornamento e la barra dei plugins
- Spostati i rinnovi dal plugin Rinnovi alla schermata di modifica Contratto
- Migliorata la gestione degli allegati
- Migliorati graficamente i temi

### Fixed
- Corretta l'associazione dell'articolo in importazione di una fattura elettronica
- Corretta la visualizzazione della tabella del plugin Serial in Articoli
- Corretta la funzione di cambio stato della Newsletter
- Corretto il salvataggio del pagamento
- Corretto lo spostamento degli allegati di fatture di acquisto e vendita
- Corretta la duplicazione del preventivo
- Corretta la registrazione delle fatture di acquisto con split payment
- Corretto l'avviso per fatture doppie per anno in fatture di acquisto
- Corretta la visualizzazione del nome articolo in Automezzi
- Corretta la ricerca delle impostazioni
- Corretta la generazione di presentazioni bancarie raggruppate per scadenza
- Corretta la generazione di autofatture in caso di IVA indetraibile
- Corretto il blocco dei fornitori in base alla relazione
- Corretta l'inizializzazione di reverse charge e autofatture per le fatture

## 2.7.3 (2025-04-15)
### Modificato (Changed)
- Rimossi file e cartelle deprecati e dipendenze inutilizzate
- Spostati gli allegati PDF delle fatture di vendita

### Fixed
- Corretto il campo **Residuo contratto** in **Contratti**
- Corretta la stampa degli ordini
- Corretta la visualizzazione dei mesi in statistiche
- Corretto il cambio stato automatico in fatturazione intervento
- Corretta l'esportazione XML dei bonifici bancari
- Corretto il controllo sulla tipologia di anagrafica
- Corretta l'esportazione degli XML da azioni di gruppo
- Corretto il doppio caricamento delle tabelle con filtri
- Corretta la visualizzazione delle tabelle responsive
- Corretto l'avviso per ricevute di scarto
- Corretta la selezione della sottocategoria in impianti
- Corretta la visualizzazione degli importi in fase di fatturazione documenti
- Corretta la selezione delle zona in base all'anagrfica selezionata
- Corretta l'esportazione SEPA
- Corrette le API relative alle checklist
- Corretta l'aggiunta delle tariffe dei tecnici
- Corretto l'aggiornamento delle scadenze
- Corretta la selezione del conto in fase di importazione delle fatture di vendita
- Corretto il caricamento degli allegati in fase di importazione delle fatture di vendita
- Corretta l'aggiunta dei tipi di intervento
- Corretto il calcolo liquidazione IVA di fine anno

## 2.7.2 (2025-03-26)
### Modificato (Changed)
- Migliorata la classe no-wrap per desktop
- Spostati i file relativi alle fatture di vendita in files\vendite
- Rimossa l'animazione in chiusura pagina
- Aggiornamento assets\src\js\wacom\modules\protobufjs alla versione 7.2.4 per vulnerabilità CVE-2023-36665
- Rimosse classi css non valide
- Modificata la vista Contratti con le colonne Residuo contratto e Note interne
- Ripristinato il numero di revisione in stampa Preventivo
- Migliorata la visualizzazione di impostazioni personalizzabili per utente
- Migliorata la gestione del campo Capitale sociale in Anagrafiche

### Fixed
- Corretta la ricerca di articoli per seriale
- Corretto il riferimento all'ordine cliente in header
- Corrette le API delle tariffe dei contratti
- Corretta la visualizzazione del plugin selezionato
- Corretta l'aggiunta di un range dal plugin Listino fornitori
- Corretto il selettore del tipo di attività in Statistiche
- Corretta la definizione della cartella per lettura degli XML in invio fattura elettronica
- Corretta la visualizzazione degli impianti
- Corretta la descrizione in esportazione degli articoli da azioni di gruppo
- Corretta la visualizzazione dei button dropdown
- Corretta la dimensione del campo descrizione in piano dei conti
- Corretto l'avviso per utenti associati alle sedi
- Corretta la modifica di un contratto associato all'anagrafica
- Corretta l'impostazione delle tariffe attività in contratti
- Corretta l'associazione del conto anagrafica fornitore da import FE
- Corrette le registrazioni associate a conti rimossi
- Corretta la visualizzazione del background Articoli in base alla disponibilità
- Corretta la stampa inventario in base ai filtri selezionati
- Corretta l'impostazione automatica del sezionale in base al tipo di documento in aggiunta di una fattura di acquisto
- Corretta l'icona delle notifiche degli hooks
- Corretto il background del menu laterale in caso di temi chiari
- Corretta la gestione degli hooks da Stato dei servizi
- Corretta la modifica dei documenti collegati ad Anagrafiche bloccate
- Corretta la visualizzazione del prezzo di acquisto su ordine fornitore da import preventivo
- Corretta la chiusura della sidebar dei plugin
- Corretta la visualizzazione dei contratti
- Corretto l'avviso di conflitti delle sessioni di lavoro

## 2.7.1 (2025-03-05)
### Modificato (Changed)
- Ripristinato il conteggio ore nel plugin consuntivo
- Modificata la colonna Fornitore in Fornitore predefinito in Articoli
- Uniformati i colori sulla pagina informazioni utente
- Aggiornamento delle traduzioni

### Fixed
- Corretta la stampa del footer del preventivo con solo totale
- Corretto lo spostamento dei widget da stato dei servizi
- Corretta la visualizzazione delle tabelle da mobile
- Corretta la stampa dei documenti con rimandi a capo nelle righe
- Corretta la stampa liquidazione IVA
- Corretto il colore degli stati attività e tecnici
- Corretta la ricerca di articoli
- Corretto il caricamento degli elementi negli impianti
- Corretta la visualizzazione delle checklist
- Corretta la visualizzazione di metodo di pagamento e banche in rate scadenze fatture
- Corretto il caricamento dei contratti collegati ad Attività
- Corretto l'orientamento dell'immagine ridimensionata e aggiornamento dimensioni
- Corretta l'esclusione da ore rimanenti in contratto delle attività con tipo da non conteggiare
- Corretta la visualizzazione del numero ordine in attività
- Corretta la logica di creazione della revisione del preventivo
- Corrette le api dell'applicazione
- Corretta la disposizione dei campi in dati appalto
- Corretta la selezione del tipo di attività in statistiche e filtro per data
- Corretta la generazione di una rata scadenza in giorni
- Corretta la visualizzazione di guadagno e margine per i tecnici
- Corretta la vista della relazione in Anagrafiche
- Corrette le aliquote iva con codice assente

## 2.7 (2025-02-12)
### Aggiunto (Added)
- Aggiunto tasto di creazione fattura in **Attività**
- Aggiunte le opzioni Non conteggiare e Note in tipologie **Attività** e tipo di attività predefinita in **Contratti**
- Aggiunta la possibilità di creare documenti collegati senza evadere necessariamente delle righe
- Aggiunte impostazioni per gestire la fatturazione massiva da azioni di gruppo
- Aggiunta la gestione delle spese di incasso in fattura
- Aggiunta del progetto in Docker
- Aggiunte sottocartelle temporanee in files
- Aggiunta la lista di aggiornamenti da applicare
- Aggiunti i riferimenti alle attività nei documenti
- Aggiunta la generazione automatica del barcode in articoli
- Aggiunta la visualizzazione nelle righe dei documenti in cui sono state evase le righe
- Aggiunta la gestione dei meta title nei moduli
- Aggiunta la gestione della cartella allegati nei moduli e plugin e impostazione per generare nomi casuali
- Aggiunta riferimento riga da ordine cliente a ordine fornitore
- Aggiunto file FUNDING.yml
- Aggiunta la gestione della sede partenza e destinazione per tutti i documenti
- Aggiunta la generazione automatica della query dinamica liste per tipologia
- Aggiunta colonna zone in **Anagrafiche** e **Attività**
- Aggiunta colonna Da rinnovare in vista **Contratti**
- Aggiunta la colonna Note interne in **Fatture**, **DDT** e **Ordini**

### Modificato (Changed)
- Inibita la possibità di creare documenti da anagrafiche eliminate
- Ripristinati i test base integrati
- Aggiornamento logs di accesso con icone
- Ripristinata la colonna Codice nella stampa DDT

### Fixed
- Corretta la verifica di plugin duplicati
- Corretto il ridimensionamento delle immagini png
- Corretti i caratteri speciali in invio mail
- Corretta la configurazione.htaccess per Apache 2.4+
- Corretto il calcolo delle rate in pianificazione fatturazione
- Corretto il calcolo del codice attività
- Corretta la classe di validazione
- Corretti i filtri sul selettore del tecnico
- Corretta la mappatura del campo qtà in importazione articoli
- Corretta la perdita di permessi in importazione checklist da documento precedente
- Corretti i riferimenti ai permessi delle check
- Corretta la visualizzazione della mappa in fase di creazione attività
- Corretta la stampa di preventivo con banca
- Corretta l'associazione dell'anagrafica in importazione automatica FE
- Corretta l'aggiunta di campi dalle **Viste**
- Corrette le logiche di validità del preventivo
- Corretta vulnerabilità legata a protobufjs
- Corretto il salvataggio dei dati oauth2
- Corretta l'importazione delle fatture elettroniche da zip