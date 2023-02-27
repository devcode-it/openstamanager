# Changelog

Tutti i maggiori cambiamenti di questo progetto saranno documentati in questo file. Per informazioni più dettagliate, consultare il log GIT della repository su GitHub.

Il formato utilizzato è basato sulle linee guida di [Keep a Changelog](http://keepachangelog.com/), e il progetto segue il [Semantic Versioning](http://semver.org/) per definire le versioni delle release.

- [2.4.41 (2023-02-27)](#2441-2023-02-27)
- [2.4.40 (2023-02-17)](#2440-2023-02-17)
- [2.4.39 (2023-01-13)](#2439-2023-01-13)
- [2.4.38 (2022-12-07)](#2438-2022-12-07)
- [2.4.37 (2022-11-02)](#2437-2022-11-04)
- [2.4.36 (2022-09-16)](#2436-2022-09-16)
- [2.4.35 (2022-08-12)](#2435-2022-08-12)
- [2.4.34 (2022-07-15)](#2434-2022-07-15)
- [2.4.33 (2022-05-17)](#2433-2022-05-17)
- [2.4.32 (2022-03-24)](#2432-2022-03-24)
- [2.4.31 (2022-03-18)](#2431-2022-03-18)
- [2.4.30 (2022-02-04)](#2430-2022-02-05)
- [2.4.29 (2022-01-28)](#2429-2022-01-28)
- [2.4.28 (2021-12-13)](#2428-2021-12-13)
- [2.4.27 (2021-10-25)](#2427-2021-10-26)
- [2.4.26 (2021-09-24)](#2426-2021-09-24)
- [2.4.25 (2021-08-25)](#2425-2021-08-25)
- [2.4.24 (2021-07-28)](#2424-2021-07-28)
- [2.4.23 (2021-05-18)](#2423-2021-05-18)
- [2.4.22 (2021-03-01)](#2422-2021-03-01)
- [2.4.21 (2021-01-14)](#2421-2021-01-14)
- [2.4.20 (2020-12-31)](#2420-2020-12-31)
- [2.4.19 (2020-11-10)](#2419-2020-11-10)
- [2.4.18 (2020-10-30)](#2418-2020-10-30)
- [2.4.17.1 (2020-09-18)](#24171-2020-09-18)
- [2.4.17 (2020-08-24)](#2417-2020-08-24)
- [2.4.16 (2020-07-28)](#2416-2020-07-28)
- [2.4.15 (2020-05-01)](#2415-2020-05-01)
- [2.4.14 (2020-04-23)](#2414-2020-04-23)
- [2.4.13 (2020-02-05)](#2413-2020-02-05)
- [2.4.12 (2019-12-30)](#2412-2019-12-30)
- [2.4.11 (2019-11-29)](#2411-2019-11-29)
- [2.4.10 (2019-07-23)](#2410-2019-07-23)
- [2.4.9 (2019-05-17)](#249-2019-05-17)
- [2.4.8 (2019-03-01)](#248-2019-03-01)
- [2.4.7 (2019-02-21)](#247-2019-02-21)
- [2.4.6 (2019-02-12)](#246-2019-02-12)
- [2.4.5 (2019-01-10)](#245-2019-01-10)
- [2.4.4 (2018-12-12)](#244-2018-12-12)
- [2.4.3 (2018-12-07)](#243-2018-12-07)
- [2.4.2 (2018-11-14)](#242-2018-11-14)
- [2.4.1 (2018-08-01)](#241-2018-08-01)
- [2.4 (2018-03-30)](#24-2018-03-30)
- [2.3.1 (2018-02-19)](#231-2018-02-19)
- [2.3 (2018-02-16)](#23-2018-02-16)
- [2.2 (2016-11-10)](#22-2016-11-10)
- [2.1 (2015-04-02)](#21-2015-04-02)

## 2.4.41 (2023-02-27)
### Aggiunto (Added)
- Aggiunta la modifica automatica del piano dei conti per clienti e fornitori
- Aggiunto l'inserimento di email multiple per notifica di interventi
- Aggiunta la possibilità di aggiornare l'ordine dei sottolivelli e di modificare la descrizione delle righe nelle checklist
### Modificato (Changed)
- Aggiornata la versione di fullcalendar
- Rimozione dei clienti eliminati dalla sincronizzazione dell'app
### Fixed
- Corretta impostazione stato predefinito attività da impostazioni
- Corretto l'inserimento di checklist non associate ad utenti o gruppi
- Corretto avviso utenti abilitati per la check
- Corretta la query vista fatture di vendita
- Corretta la creazione di eventi su calendario con vista del Giorno
- Corretta la vista giornaliera in dashboard da impostazioni
- Correzione query vista scadenzario
- Corretto l'editor nei moduli
- Corretta l'eliminazione di prima nota in riapertura di documenti
- Corretto l'inserimento di articoli in DDT e fatture di acquisto
- Corretta la visualizzazione del widget in Stato dei servizi
- Corretta la creazione evento con impostazione AllDay attiva
## 2.4.40 (2023-02-17)
### Aggiunto (Added)
- Aggiunto box dettagli cliente in fase di creazione fattura
- Aggiunta colonna modalità di pagamento in anagrafiche
- Aggiunti riferimenti a natura IVA N7
- Aggiunta colonna **Agente** in preventivi
- Aggiunta colonna **Inviato** in DDT in uscita
- Aggiunta scelta relazione anche su Fornitori
- Aggiunta visualizzazione sconto in fase di importazione di una fattura elettronica
- Aggiunta la possibilità di far scadere la cache degli hooks
- Aggiunta impostazione per quantità di widget per riga
- Aggiunta colonna **Banca** in scadenzario
- Aggiunto filtro per codice nei menu a tendina delle anagrafiche
- Aggiunta impostazione predefinita per movimentare o meno il magazzino in importazione fatture elettroniche di acquisto
- Aggiunta gestione dichiarazione d'intento predefinita
- Aggiunta impostazione posizione simbolo valuta
- Aggiunta scadenzario autofatture
- Aggiunto inserimento massivo sessioni di lavoro nelle attività
- Aggiunta impostazione per fatturare attività anche se collegate a documenti (ordini, preventivi, contratti)
- Aggiunto **Totale imponibile** in riepilogo fattura con cassa previdenziale
- Aggiunte informazioni in stampa scadenzario
- Aggiunte impostazioni per preimpostare lo stato attività in fase di creazione attività (da modulo **Attività** o da **Dashboard**)
- Aggiunta colonna **KM** in **Attività** e nelle stampe riepilogo
- Aggiunta impostazione per aggiornamento data emissione fattura di vendita in base alla prima data disponibile
- Aggiunta la possibilità di importare articoli da CSV specificando la sede su cui caricare le quantità
### Modificato (Changed)
- Miglioria sulle date dei movimenti di magazzino
- Miglioria su gestione campi personalizzati per pieno supporto html e js
- Miglioria provvigioni in Attività
- Aggiornamento a Fullcalendar 6
- Disattivata la modalità debug in fase di compilazione assets
- Ottimizzazioni grafiche Fullcalendar 6
- Rimosso controllo su tasto submit
- Ottimizzata velocità di caricamento widget rate contrattuali
- Disabilitato il tasto aggiunti in rate modalità di pagamento già utilizzate
- Ottimizzata la gestione del cambio di stato Attività
- Ottimizzata la gestione di aggiunta righe nei documenti
### Fixed
- Corretta query vista Utenti e permessi
- Corretta dicitura dichiarazione d'intento
- Corretta query riferimento ordini fornitore da ordini cliente
- Corretta selezione causali movimenti con hotkeys
- Corretta sede fatturazione
- Corretto invio rapportini da azioni di gruppo
- Corretti problemi di integrità database all'aggiornamento da una versione precedente alla 2.4.28
- Corretta query vista Pagamenti
- Corretto orario di inizio e di fine calendario
- Corretta selezione tecnici assegnati in fase di pianificazione promemoria
- Corretto sconto in pianificazione fatturazione
- Corretta duplicazione fatture attraverso sezionali diversi
- Corretto salvataggio conto Ordini da azioni di gruppo
- Corretta impostazione di filtri da statistiche in anagrafica
- Corretta visualizzazione margine in aggiunta riga
- Corretta selezione del modulo iniziale in Utenti e permessi
- Corretta stampa etichette con prezzo ivato
- Corretta impostazione ragione sociale in creazione documento
- Corretta visualizzazione sconto in fase di importazione fattura elettronica
- Corretta movimentazione magazzino in fase di importazione fattura elettronica
- Corretta selezione del sezionale e del tipo documento in fatturazione interventi da azioni di gruppo
- Corretta selezione sezionale in stampa registri IVA
- Corretto nome file Fatture elettroniche se si aggiorna da una versione precedente alla 2.4.4
- Corretta la generazione del numero progressivo ordini fornitore
## 2.4.39 (2023-01-13)
### Aggiunto (Added)
- Aggiunto controllo eliminazione metodo di pagamento
- Aggiunte nazioni
- Aggiunta visualizzazione mappa all'aggiunta attività
- Aggiunta possibilità di creare campi aggiuntivi con testo libero
- Aggiunta impostazione per nascondere i promemoria su app
- Aggiunta ID utente nei movimenti articolo
- Aggiunta valori buffer Datatables
- Aggiunto filtro referenti per sede in interventi
- Aggiunta azione di gruppo per cambio relazione anagrafiche
### Modificato (Changed)
- Miglioria gestione codice REA e provincia
- Miglioria stampa mastrino saldo iniziale
- Miglioria visualizzazione tabella listini clienti
- Migliorata l'impostazione del prezzo di vendita in fase di aggiunta articolo
### Fixed
- Corretta query vista contratti
- Corretta stampa inventario in Articoli
- Corretta query vista fatture di vendita
- Corretta query vista utenti e permessi
- Corretta lunghezza campo codice REA
- Corretto il calcolo del guadagno in aggiunta riga
- Corretto ordinamento riga bollo in fattura
- Corretta vulnerabilità XSS
- Corretto set pattern segmento attività
- Corretta query vista giacenze sedi
- Corretta visualizzazione rate contrattuali da plugin per anno precedente
- Corretta selezione conti e IVA durante importazione Fattura elettronica di acquisto
## 2.4.38 (2022-12-07)
### Aggiunto (Added)
- Aggiunto tempDir per mpdf
- Aggiunto controllo cartella files/temp
- Aggiunto invio delle mail con procedura in cron
- Aggiunta psalm per migliorare lo sviluppo
- Aggiunta strumenti di debug
- Aggiunto modulo Listini clienti
- Aggiunta gestione prezzo minimo e visulizzazione listini in fase di aggiunta riga
- Aggiunti modelli di prima nota per pagamento di salari e stipendi
- Aggiunta eliminazione massiva destinatari lista newsletter
- Aggiunta campi provvigione su righe promemoria
- Aggiunta fatturazione rate contratto con azione di gruppo da widget in dashboard
- Aggiunto avviso gruppo o username già in uso in fase di creazione/modifica
- Aggiunto colore per livello di permesso impostato
- Aggiunta gestione permessi segmenti e numerazione documenti
- Aggiunto controllo file di servizio
- Aggiunta email tecnici assegnati nel template Notifica intervento
- Aggiunta azione di gruppo invio mail da Attività
- Aggiunte opzioni per connessione al Database
- Aggiunto controllo plugin duplicati per i moduli
- Aggiunto controllo per Anagrafiche con codici REA non validi
### Modificato(Changed)
- Miglioria gestione prezzi
- Ottimizzate query viste per aumentare la velocità di caricamento dei moduli principali
- Miglioria ricerca di corrispondenza tra anagrafiche in fase di impostazione dei permessi
- Sostituita funzione deprecata formatLocalized con isoFormat
- Rimozione codice non raggiungibile
- Ridotte restrizioni cookie per problemi con app esterne
### Fixed
- Corretta la selezione dei colori
- Corretta la visualizzazione delle colonne datatables
- Corretta variabile referenti in template DDT
- Corretta creazione del file manifest.json
- Corretta l'importazione delle fatture con importi negativi
- Corretta eliminazione di articoli da azioni di gruppo
- Corretta visualizzazione utente collegato ad anagrafica
- Corretto login tramite API
- Corretta query vista Piani di scondo/maggiorazione
- Corretta validazione username
- Corretta importazione csv Anagrafiche con PHP8.0
- Corretto messaggio di contenuto modificato all'uscita dalla pagina di impostazione dei permessi
- Corretta descrizione periodi in pianificazione fatturazione contratti
- Corretti avvisi settore merceologico e provenienza già presenti
- Corretti id pulsanti in rowlist
- Corretta icona bandiera select2
- Corretta verifica prezzo minimo di vendita
- Corretta notifica inserimento fattura
- Corretto redirect da fatturazione rate contratto a Fatture di vendita
- Corretta visualizzazione rate da fatturare 
- Corretta visualizzazione query viste
- Corretto calcolo prezzo di vendita da cambio coefficiente da azione di gruppo
- Corretta query elenco di scadenze scadute per cliente in nuova fattura di vendita
- Corretta logica riapertura fattura pagata
- Corretta valorizzazione codice REA in fase di importazione fattura elettronica
- Corretta creazione fattura da azioni di gruppo in Attività se valorizzato 'Per conto di'
- Corrette note interne riga documento
- Corretto invio notifica chiusura intervento
- Corretta emissione fatture da azioni di gruppo
- Corretta generazione fatture con totale negativo
- Corretto controllo sort-buffer-size
## 2.4.37 (2022-11-04)
### Aggiunto (Added)
- Aggiunto modulo Mappa per geolocalizzare le attività
- Aggiunta tipologia documento TD28
- Aggiunta tipologia TD21 nei controlli per autofattura
- Aggiunte cartelle e file da escludere dal backup
- Aggiunto confronto con secondo checksum del database per la versione 5.7.x di MySQL
- Aggiunto flag impianti in fase di duplicazione attività
- Aggiunta opzione per includere allegati nella duplicazione delle attività
- Aggiunto elenco Hooks disponibili in stato dei servizi
- Aggiunta colonna Pagamento in Fatture
- Aggiunte colonne Cellulare e Indirizzo in Anagrafiche
- Aggiunta creazione movimenti dalla scheda articolo
- Aggiunta selezione colore in Stati dei preventivi
- Aggiunto il supporto ai valori multipli nelle impostazioni
- Aggiunta colonna Anagrafica in Movimenti
- Aggiunto codice fornitore in ordini cliente
- Aggiunta selezione periodo nelle stampe contabili
- Aggiunta condizione di fornitura in ordini
- Aggiunto avviso per fatture scartate
### Modificato(Changed)
- Miglioria per velocizzazione apertura DDT 
- Migliorie modulo Causali
- Miglioria cartelle escluse in fase di verifica numero file e spazio
- Miglioria statistiche interventi in base alla data di inizio sessione
- Miglioria plugin giacenze
- Miglioria database con allineamento decimali
- Miglioria visualizzazione colonne datatables
### Fixed
- Corretto controllo Attiva aggiornamenti
- Corretta visualizzazione delle immagini in stampa Preventivo
- Corretta ricerca di riferimenti automatici durante l'importazione di fatture elettroniche di acquisto
- Corretta selezione della data del nodo DatiOrdine in fase di esportazione di fatture elettroniche
- Corretto il salvataggio del corpo email delle newsletter
- Corretto autocompletamento di indirizzi email
- Corretto tooltip calendario
- Corretto widget top 10 allegati
- Corretto sconto su importi negativi
- Corretto calcolo arrotondamento automatico in fattura elettronica
- Corretta statistica Ore di lavoro per tecnico in caso di nessuna sessione inserita
- Corretto link note di credito da fatture di acquisto
- Corretto collegamento con anagrafiche in fase di aggiunta di una scadenza
- Corretto popup data in fase di duplicazione attività dalle azioni di gruppo
- Corretta visualizzazione sconti in fattura
- Corretta stampa ore in sessantesimi
- Corretto indirizzo google in modifica sede
- Corretto calcolo numero maschera
- Rimozione visualizzazione delle azioni di gruppo nei plugin
- Corretta notifica di numerazione errata nei DDT in entrata
- Corretto caricamento di immagini su ckeditor
- Corretto calcolo dell'IVA in stampa liquidazione
- Corretta impostazione di calcolo totali ristretti a selezione

## 2.4.36 (2022-09-16)
### Aggiunto (Added)
- Aggiunta selezione modulo iniziale per gruppo utenti
- Aggiunta validazione matricola in impianti
- Aggiunta anagrafica nelle scadenze
- Aggiunta prezzi ivati nella stampa documenti
### Modificato (Changed)
- Miglioria barra dei menu in modalità compatta
- Rimozione file deprecato controller_after.php
- Miglioria creazione conto anagrafica in piano dei conti
- Miglioria importazione articoli in CSV
- Rimozione delle aliquote IVA non utilizzate
- Miglioria riepilogo totali in stampa intervento
- Miglioria gestione ritenuta previdenziale
### Fixed
- Correzione controllo dei totali in fattura elettronica
- Correzione eliminazione attività collegate ai documenti
- Correzione numerazione progressiva
- Correzione creazione autofattura da nota di credito
- Correzione creazione intervento da ordine fornitore
- Correzione calcolo cassa previdenziale in importazione di fatture elettroniche
- Correzione stato di default in creazione attività
- Correzione selezione sottocategorie in Combinazioni
- Correzione registrazione autofattura
- Correzione aggiunta modelli prima nota
- Correzione movimentazione articoli tra DDT
- Correzione emissione fatture non fiscali da azioni di gruppo
- Correzione regime fiscale per fatture conto terzi
- Correzione date di inizio e fine attività
## 2.4.35 (2022-08-12)

### Aggiunto (Added)
- Aggiunta la possibilità di scrivere **note interne** per ogni riga dei documenti (fatture, preventivi, ecc)
- Aggiunta unità di misura nella finestra di inserimento nuovo articolo 
- Aggiunta distinzione fra margine e ricarico nelle finestre di inserimento righe dei documenti
- Aggiunto in visualizzazione il codice fornitore nelle righe da importare fra documenti di acquisto
- Nuovo pulsante **Copia riferimento vendita** in importazione fatture elettroniche passive
- Aggiunta filtro per gli utenti del gruppo **Agenti** per visualizzare solo i propri preventivi
- Ampliamento filtro di ricerca articoli anche per codice fornitore
### Modificato (Changed)
- Spostata stampa scadenzario nel modulo **Contabilità** -> **Stampe contabili**, con aggiunta di filtri
- Rimozione limiti tag HTML nell'editor dei template email e aggiunta emoji e immagini
### Fixed
- Correzione caratteri speciali nelle fatture elettroniche
- Correzione modulo **Statistiche** per escludere le autofatture
- Correzione colonne **Costi e Ricavi** nelle attività
- Correzione statistiche articolo per **PHP 8.0**
- Correzione ordinamento fatture di acquisto per num. protocollo in stampa registri iva
- Correzione ricerca iscritto newsletter per **PHP 8.0**
- Correzione della selezione banca nelle fatture
- Correzione cambio stato alla firma dell'attività 
- Correzione calcolo sconto combinato in importazione fattura elettronica
- Correzione sconti multipli in fattura
- Correzioni minori in fattura elettronica
- Correzione calcolo totali nelle tabelle principali
## 2.4.34 (2022-07-15)
### Aggiunto (Added)
- Aggiunte 3 nuove colonne nella vista attività, di default disattivate: ore, ricavi, costi
- Gestione **provvigioni**
- Gestione nodo CodiceArticolo da attributi avanzati
- Aggiunta creazione al volo del preventivo dall'attività 
- Gestione articolo confermato in fase di aggiunta articolo nei documenti tramite barcode 
- Aggiunta impostazione per controlli su stati FE
- Aggiunta visualizzazione **eventi** in dashboard
- Aggiunta azione di gruppo per eliminazione **Impianti** 
- Aggiunta stampa **barcode** come azione di gruppo
- Aggiunti controlli su eliminazione **Impianti**
- Aggiunta scelta minuti di snap in **Dashboard**
- Aggiunta stampa **scadenza** 
- Aggiunto filtro per mostrare **Preventivi** ai clienti
- Aggiunto pulsante allega fattura in **Scadenzario** 
- Aggiunto nuovo modulo **Provenienze**
- Aggiunto nuovo modulo per gestire i **Settori merceologici**
- Gestione chiusura scadenze in fase di registrazione contabile
- Aggiunto controllo per seriali duplicati
- Aggiunto link alla guida su pagina info
- Aggiunto nuovo plugin **Statistiche vendita** in Articoli
- Aggiunto grafico **Nuovi clienti** per mese in Statistiche 
- Aggiunto avviso per **sessioni di lavoro** a zero
- Aggiunto riferimento fattura di acquisto su **autofattura**
- Aggiunto flag autofatture in **Segmenti** per calcolo statistiche

### Modificato (Changed)
 - Controllo sfondo lista fatture in base al segmento
 - Rimozione filtro obsoleto su ricerca tipo anagrafica
 - Miglioramenti campo Agente nei documenti di vendita
 - Visualizzazione avviso fatture solo se invio FE è attivato
 - Miglioramenti modulo Fasce orarie
 - Rimossa visualizzazione pulsanti nel top per utenti diversi da amministratori
 - Modifica controlli fatture di vendita duplicate
 - Rimozione replace caratteri in fase di export FE
 
### Fixed
 - Fix css personalizzato
 - Fix controllo invio fatture in ritardo
 - Fix colorazione agente di default
 - Fix visualizzazione totale righe intervento
 - Fix export righe con ritenuta 
 - Fix set sezionale in fase di creazione fattura da bulk 
 - Fix aggiunta intervento al preventivo 
 - Fix ajax select fasce orarie 
 - Fix invio allegati stampe mail
 - Fix esportazioni bancarie
 - Fix visualizzazione dati sede in attività
 - Fix set stato in fase di creazione nota di credito
 - Fix eliminazione fattura collegata a Nota di credito
 - Fix visualizzazione fatture scadute in fase di aggiunta fattura 
 - Fix modifica orario intervento in Dashboard
 - Fix calcolo colonna Netto a pagare in Fatture
 - Fix visualizzazione scadenze in Fatture
 - Fix codice destinatario autofatture
 - Fix logica Condizioni di pagamento
 - Fix aggiunta Listini per range

## 2.4.33 (2022-05-17)

### Aggiunto (Added)
 - Introduzione modulo **fasce orarie** e modulo **eventi** 
 - Aggiunto pulsante per visualizzare movimenti prima nota
 - Cambio prezzo unitario listino da azioni di gruppo
 - Introduzione fasce orarie per il tipo di attività
 - Aggiunta selezione righe nei documenti per eliminazione e duplicazione
 - Aggiunte colonne Contratto, Preventivo, Ordine in Attività
 - Aggiunto controllo totale tra documento e FE per fatture di vendita
 - Calcolo iva intervento in base all'iva predefinita dell'anagrafica 
 - Aggiunti alert in fase di aggiunta riga per importi negativi e sconti
 - Aggiunta visualizzazione campi collegati alla sede
 - Aggiunto help su conto predefinito nelle banche
 - Aggiunta tabella **regioni**
 - Aggiunto sezionale fatture di acquisto non elettroniche 
 - Aggiunta azione di gruppo per l'importazione delle **ricevute** 
 - Aggiunta al volo Sede dal plugin Referenti
 - Gestione installazione da zip per i templates di stampa
 - Aggiunta **ricerca** datatables per i valori !=
 - Gestione esclusione allegati import FE
 - Aggiunto controllo su fattura già importata 
 - Aggiunta gestione **Autofattura**
 - Aggiunti controlli sulla stato documento delle fatture di vendita 
 - Gestione conti predefiniti crediti e debiti tramite impostazione 
 - Aggiunta possibilità di selezionare la creazione degli articoli in fase di importazione FE

### Modificato (Changed)
 - Spostamento pulsante seleziona/deseleziona in fase di import tra documenti
 - Ordinamento vista N. Prot.
 - Rimozione visualizzazione azioni di gruppo per utenti con permessi in sola lettura
 
### Fixed
 - Fix calcolo sconto finale
 - Fix visualizzazione colonne Totali in fatture
 - Fix controllo totale parziale in stampa fattura
 - Fix plugin registrazioni per le righe di sconto
 - Fix documenti collegati ai Preventivi
 - Fix liste newsletter
 - Fix set riferimento riga importFE
 - Fix set banca predefinita in fattura
 - Fix ricerca riferimenti FE
 - Fix selezione spunte su datatables dopo azione di gruppo
 - Fix visualizzazione totali Piano dei conti
 - Fix gestione causali movimenti 
 - Fix bulk cambio stato intervento
 - Fix set prezzo listino fornitore
 - Fix riapertura fatture pagate
 - Fix aggiunta articolo in Attività da Barcode
 - Fix esportazione csv fatture
 - Fix visualizzazione immagine in stampa preventivi 
 - Fix avviso aggiunta scadenza generica 
 - Fix ricalcola totale in fase di importazione tra documenti
 - Fix widget attività da programmare
 - Fix visualizzazione ultimi prezzi
 - Fix ordinamento righe in fattura
 - Fix pianificazione fatturazione contratto
 - Fix visualizzazione residuo dichiarazione d'intento 
 - Fix widget Scadenze

## 2.4.32 (2022-03-24)

### Aggiunto (Added)
 - Aggiunto try / catch per funzione fetchArray
 - Aggiunta visualizzazione Agenda in Dashboard
 - Aggiunta variabile impianti nei template Interventi
 - Aggiunta colonna Impianti in Attività
 
### Modificato (Changed)
 - Gestione contratto predefinito e miglioramenti plugin Contratti del cliente
 
### Fixed
 - Fix aggiunta articolo importFE
 - Fix stampa DDT
 - Fix calcolo margine in base ai prezzi ivati
 - Fix caricamento record via ajax
 - Fix visualizzazione contatori badge plugin
 - Fix stampa etichette
 - Fix classe Database

## 2.4.31 (2022-03-18)

### Aggiunto (Added)
 - Aggiunta possibilità di aggiungere **Impianti** dalla scheda attività
 - Aggiunto controllo **MySQL** per i requisiti
 - Aggiunta ricerca mail referente per sede in invio mail
 - Aggiunta numerazione per **mese** per Contratti, Preventivi, Ordini
 - Aggiunta **dicitura** fissa nei segmenti fiscali
 - Aggiunta azione di gruppo unisci **rdo**
 - Aggiunta colonna **Inviato** in Scadenzario
 - Imposta data fine sessione in base al **tempo standard**
 - Aggiunta azione di gruppo **Emetti fatture**
 - Aggiunto campo **barcode** nel listino fornitori 
 - Aggiunta importazione dettagli fornitore per gli articoli
 - Aggiunta **ricerca** articolo per barcode fornitore
 - Aggiunto creazione al volo referente e contratto in creazione attività 
 - Aggiunta impostazione scelta colore sessioni dashboard
 - Aggiunto **link** listino articolo in fase di aggiunta riga 
 - Aggiunta impostazione per mantenere tutti i riferimenti nelle righe
 - Aggiunta colonna **Codice** in Anagrafiche 
 - Aggiunta informazione Esigibilità IVA in fase di creazione aliquota
 - Aggiunte configurazioni per gestione **immagini** in fase di import articoli
 - Aggiunta gestione righe dinamiche per i modelli prima nota 
 - Seleziona tutte le righe in fase di importazione tra documenti
 - Aggiunta opzione formattazione HTML in viste per campi ckeditor
 - Aggiunta colonna **Servizio** per vista Articoli
 - Aggiunti pagamenti **predefiniti** per importazione FE
 - Gestione stampa definitiva registri iva
 - Aggiunto **coefficiente** di vendita in articoli e azione di gruppo collegata
 - Aggiunte colonne codice e barcode fornitore in listini
 - Aggiunta campi in import anagrafiche 
 - Creazione **attività** da Preventivo
 - Aggiunta azione di gruppo per esportazione PDF prima nota
 - Badge contatore numero record all'interno del plugin 
 - Aggiunta possibilità di tornare all'elenco delle fatture da registrazione di prima nota
 - Aggiunto plugin **Regole pagamenti** in Anagrafiche 
 - Aggiunta impostazione per personalizzare **dicitura** riferimento attività
 - Aggiunto abilita serial in fase di creazione articolo
 - Aggiunto plugin **Registrazioni** in Fatture
 - Aggiunta azione massiva export **ricevute** FE
 
### Modificato (Changed)
 - Aggiornato **README**
 - Migliorie per dichiarazione d'intento
 - Spostamento generazione xml nella classe Fattura 
 - Verifica numerazione DDT 
 - Ampliamento visualizzazione movimenti contabili
 - Miglioramenti dati **trasporto** fattura accompagnatoria
 - Miglioramento importazione FE sui riferimenti dei documenti
 - Spostamento stampa riepilogo nelle azioni di gruppo
 - Modifiche modulo **Piano dei conti**
 - Modifiche stampe **registri iva**
 - Predisposizione conti registrazione contabile ritenuta
 
### Fixed
 - Fix stampa consuntivo contratto/preventivo
 - Fix messaggio firma mancante
 - Fix creazione seriali
 - Fix aggiunta referente
 - Fix codice aliquota iva e validazione
 - Fix nascondi prezzi al tecnico in fase di modifica sessione
 - Fix prezzi al cambio della tipologia in modifica sessione tecnico
 - Fix ricerca globale dal menù
 - Fix prima nota scadenze generiche
 - Fix generazione causale in fase di registrazione scadenze
 - Fix calcolo e visualizzazione sconto in fattura
 - Fix creazione nota di credito da ddt
 - Fix chiusura bilancio
 - Fix visualizzazione tipo documento in stampa Fattura di vendita
 - Fix visualizzazione campi personalizzati
 - Fix stampa fattura elettronica di acquisto
 - Fix conteggio righe collegate ad unità di misura
 - Fix per tipologia ritenuta acconto in FE
 - Fix widget valore magazzino
 - Fix destinatari in fase di invio mail
 - Fix importazione FE con codice articolo vuoto
 - Fix selezione seriali in fase di creazione documento 
 - Fix PEC per variabili email

## 2.4.30 (2022-02-05)

### Aggiunto (Added)
 - Aggiunta azione di gruppo **Copia listini**
 - Aggiunta stampa riepilogo interventi **interno**
 
### Modificato (Changed)
 - Spostamento movimento cassa previdenziale a costo per acquisti
 
### Fixed
 - Fix partita iva FE per privati esteri
 - Fix calcolo **scadenze**
 - Fix grafica barra plugin
 - Fix selezione interventi fatturabili
 - Fix minore data **competenza** fattura
 - Fix calcolo **costi** intervento
 - Fix query nel file sql **update**

## 2.4.29 (2022-01-28)

### Aggiunto (Added)
 - Aggiunto campo **sito web** per l'importazione Anagrafiche
 - Aggiunta data esportazione Ri.Ba. in scadenza
 - Aggiunto salvataggio impianto nelle righe intervento
 - Aggiunta stampa ordine cliente (senza codici)
 - Aggiunta tabella nel modulo Articoli per visualizzare **ultimi prezzi**
 - Integrazione nuove tabelle nel plugin Consuntivo
 - Aggiunto condizioni generali di fornitura nel modulo Contratti
 - Aggiunta azione di gruppo **Rinnova contratti** nel modulo Contratti
 - Aggiunta modifica sottocategoria da azione di gruppo nel modulo **Articoli**
 - Aggiunta tabella per i documenti collegati articoli
 - Aggiunto avviso con informazioni fattura creata per import in sequenza
 - Aggiunta colonna **protocollo** in stampa registro iva
 - Aggiunti referenti collegati in Mansioni
 - Aggiunta link **scadenza** ai movimenti di primanota
 - Aggiunta vista **Email Inviata** in Ordini

### Modificato (Changed)
 - Gestione rimozione collegamento variante-attributo di una Combinazione
 - Miglioramenti stampa riepilogo interventi
 - Calcolo automatico **codice articolo** in fase di duplicazione se non specificato
 - Modifica stampa preventivi senza totale per visualizzazione prezzo ivato
 - Modifica stampa scadenzario con **intestazione ridotta**
 - Bloccata rimozione allegati FE se fattura non è generabile
 - Miglioramenti compilazione automatica import FE
 - Aggiornamento terminologie fiscali
 - Modificato controllo **stato** per rinnovo contratti
 
### Fixed
 - Fix calcolo margine percentuale
 - Fix per import articoli senza quantità
 - Fix calcolo prezzo vendita articolo
 - Fix calcolo totale tra fattura e xml
 - Fix articoli in esaurimento
 - Fix problema iva di vendita preselezionata
 - Fix calcolo numero protocollo in base a data registrazione
 - Fix controllo numero intervento
 - Fix widget preventivi in lavorazione
 - Fix avviso modulo Iva 
 - Fix numero lettera intento
 - Fix numerazione rata
 - Fix validità contratto
 - Fix set sede in pianificazione interventi
 - Fix vista tecnici assegnati
 - Fix calcolo numero protocollo Parcella
 - Fix cambio stato al rinnovo contratto
 - Fix importazione documento in ddt
 - Fix apertura bilancio
 - Fix aggiunta destinatari referenti
 - Fix aggiunta articoli Barcode
 - Fix massimale dichiarazione d'intento
 - Fix eliminazione pianificazione fatturazione nel modulo Contratti
 - Fix data conclusione in fase di duplicazione preventivo
 - Fix import CSV articolo

## 2.4.28 (2021-12-13)

### Aggiunto (Added)
 - Aggiunta colonna documento di acquisto e prezzo nel plugin **seriali**
 - Aggiunto flag Calcola km nel modulo **tipi di attività**
 - Aggiunta azione di gruppo per aggiornare unità di misura degli **articoli**
 - Aggiunta azione di gruppo per aggiornare liste **newsletter**
 - Aggiunta azione di gruppo in **articoli** per aggiornare conto acquisto/vendita
 - Aggiunta colonna mail Inviata in **attività**
 - Aggiunta gestione dell'arrotondamento nell'azione di gruppo per aggiornare il prezzo di vendita 
 - Aggiunta impostazione per riportare il riferimento del documento 
 - Aggiunto controllo widget Fatturato per escludere le fatture in stato Bozza 
 - Aggiunta stampa **libro giornale**
 - Aggiunta creazione sottocategoria al volo dal modulo **articoli**
 - Aggiunta creazione dinamica della sede in attività
 - Aggiunta tabella **mansioni** e gestione aggiunta destinatari mail
 - Aggiunte impostazioni per notificare intervento ai tecnici 
 - Aggiunte nuove colonne nel widget **interventi da programmare**
 - Aggiunto filtro di ricerca nel Piano dei conti
 - Aggiunto plugin **Movimenti contabili** in Fatture e Anagrafiche 
 - Aggiunta possibilità di associare varianti della combinazione collegandole ad articoli esistenti
 - Aggiunto plugin **Presentazioni bancarie** in Scadenzario
 - Aggiunta gestione Abi e Cab in fase di creazione banca
 - Aggiunte note interne in template mail
 - Aggiunta duplicazione **DDT**
 - Aggiunto codice distinta nello scadenzario
 - Aggiunta azione di gruppo Aggiorna banca da Scadenzario e Fatture
 - Aggiunto pulsante di modifica per le varianti di una combinazione
 - Aggiunte note interne in Preventivi e Contratti
 
### Modificato (Changed)
 - Compilazione automatica tipo documento in fase di import FE solo se il campo non è impostato 
 - Rimossa data conclusione preventivo automatica
 - Miglioramenti modal modifica sessione tecnico
 - Modifica apertura attività in dashboard con click e doppio click da smartphone
 - Migliorie modulo Coda di invio
 - Importazione righe della fattura elettronica con quantità e importo a 0 come riga di tipo descrizione
 - Rimosso modulo Gestione componenti

### Fixed
 - Fix controllo corrispondenza tra xml e documento
 - Fix stampa senza intestazione **fattura di vendita**
 - Fix eliminazione movimenti manuali
 - Fix stampa registro iva
 - Fix select articoli
 - Fix calcolo prezzi e iva tabella **seriali**
 - Fix importazione fatture con importi negativi
 - Fix destinatari modal invio mail
 - Fix calcolo guadagno riga
 - Fix pulsante disabilita widget
 - Fix calcolo sessioni interventi in fase di duplicazione
 - Fix stampa documento tramite comando da tastiera
 - Fix campi abi cab nel modulo **banche**
 - Fix stampa fatturato
 - Fix salvataggio categorie durante import articoli da CSV

## 2.4.27 (2021-10-26)

### Aggiunto (Added)
 - Aggiunta selezione automatica **banca** in fase di importazione fattura elettronica
 - Aggiunta selezione automatica del **conto di acquisto** articolo in fase di importazione fattura elettronica
 - Aggiunto select **conto acquisto/vendita** in fase di creazione articolo
 - Aggiunto select per aggiornare i prezzi di acquisto dell'articolo in fase di importazione fattura elettronica
 - Aggiunto filtro per mostrare gli impianti ai tecnici assegnati
 - Aggiunto ordinamento righe in interventi 
 - Aggiunta azione di gruppo per **rincaro prezzi di vendita** articoli con possibilità di scelta del prezzo di partenza 
 - Aggiunta azione di gruppo per cambiare la **categoria** degli articoli
 - Aggiunta azione di gruppo per aggiornare l'aliquota iva degli articoli
 - Aggiunto **Mese prossimo** nel calendario
 - Aggiunta variabile ragione sociale per l'invio mail da ddt
 - Aggiunta immagine in import CSV articoli
 - Aggiunta selezione prezzo di acquisto per stampa inventario
 - Aggiunto costo medio in fase di aggiunta riga articolo 
 - Aggiunta azione di gruppo per aggiornare il prezzo di acquisto per gli articoli a cui non è impostato, in base all'ultima fattura di acquisto

### Modificato (Changed)
 - Ampliata **ricerca articoli** in importazione fatturazione elettronica per collegamento automatico
 - Ridotto il valid time per la cache
 - Ordinamento **gestione documentale** per data decrescente 
 - Spostamento stampe situazione contabile e bilancio in **Stampe contabili**

### Fixed
 - Fix sconti in **fatturazione interventi**
 - Fix statistiche **fatture**
 - Fix aggiunta intervento da dashboard vista mese
 - Fix selezione iva in aggiunta riga articolo
 - Fix cambio stato intervento in fase di eliminazione riga da fattura
 - Fix selezione iva in **crea fattura** da contratto
 - Fix filigrana stampe
 - Fix arrotondamento automatico
 - Fix azzeramento revisione in duplicazione **preventivo**

## 2.4.26 (2021-09-24)

### Aggiunto (Added)
 - Aggiunto modal in fase di **Stampa Bilancio** per visualizzare o meno l'elenco analitico dei clienti e fornitori
 - Aggiunta scelta del tipo documento in fase di creazione fattura da un altro documento 
 - Aggiunta possibilità di creare delle ricorrenze per gli **Interventi** in fase di aggiunta
 - Aggiunta scelta del tipo documento in fase di creazione fattura da un azione di gruppo di un altro documento
 - Aggiunta sistema di gestione Combinazioni Articoli
### Modificato (Changed)
 - Modificata query per generare liste in **Newsletter**
### Fixed
 - Fix orario della modifica del listino di riferimento dell'articolo
 - Fix movimentazione articoli tra due sedi tramite **DDT**
 - Fix duplicazione **Pagamenti**
 
## 2.4.25 (2021-08-25)

### Aggiunto (Added)
 - Aggiunta percentuale di imponibilità nel modulo **Piano dei conti** sezione **Ricavi** per il calcolo del **Totale reddito**
 - Conversione del plugin Componenti modulo Impianti nel nuovo formato
 - Aggiunta colonna **Sede** nel modulo **Anagrafiche**
 - Aggiunta colonna **Referenti** nel modulo **Anagrafiche**
 - Aggiunta procedura per l'importazione del **Piano dei conti**

### Modificato (Changed)
 - In fase di importazione **Fattura di acquisto** la data competenza ora viene allineata alla data di emissione

### Fixed
- Fix calcolo totale della **Stampa inventario**
- Fix aggiunta **DDT** in una **Fattura di vendita**
- Fix calcolo arrotondamento automatico in fase di importazione delle Fatture di acquisto

## 2.4.24 (2021-07-28)

### Aggiunto (Added)
 - Aggiunta nel calendario della Dashboard visualizzazione dei preventivi pianificabili in corrispondenza alla data di accettazione e conclusione.
 - Aggiunta impostazione per la visualizzazione delle ore nella stampa intervento (Decimale, Sessantesimi).
 - Aggiunta possibilità di selezionare la sede di partenza della merce in fase di aggiunta articolo da un'attività
 - Aggiunta colonna *Scaduto* nel modulo **Scadenzario**
 - Aggiunto campi confermato, data e ora evasione nel modulo **Preventivi**
 - Aggiunta possibilità di creare un nuovi conti di secondo livello dal modulo **Piano dei conti**
 - Aggiunta impostazione per la rimozione del blocco sulle quantità massime importabili fra documenti
 - Aggiunta colonna **Rif. fattura** nei moduli Ordini cliente e fornitore
 - Aggiunta gestione come costo e ricavo per i conti di secondo livello
 - Aggiunta gestione di DDT di trasporto interno tra sedi dell'anagrafica Azienda, con creazione semplificata del DDT di direzione opposta
 - Aggiunto codice e prezzo nella stampa *Barcode*
 - Aggiunto riquadro destinazione diversa nella stampe documenti (se presente)
 - Aggiunta azione di gruppo in **Fatture di acquisto** per l'esportazione delle fatture FE in PDF
 - Separazione plugin per la visualizzazione di *Listini Clienti* e *Listini Fornitori*
 - Introduzione importazione Preventivi e Contratti in Attività
 - Aggiunta colonna reddito nella stampa del **Bilancio**
 - Aggiunta autenticazione OAuth2 per gli **Account email** (funzionante con Google, sperimentale con Microsoft)
 - Aggiunta importazione di **Preventivi** e **Contratti** in **Attività**, senza collegamento contabile per i *Consuntivi*
 - Aggiunta sezione di riepilogo dettagli Cliente in apertura di nuove **Attività**

### Modificato (Changed)
 - Rimossa dipendenza JQuery per la gestione dell'ordinamento (righe e widget) e per la ricerca generale
 - Modifica del sistema di selezione delle righe nelle tabelle principali, per aggiungere un contatore delle righe selezionate

### Fixed
 - Sostituito plugin **Componenti** nel modulo Impianti con la possibilità di inserire gli articoli di magazzino
 - Possibilità di ripristinare un conto cliente/fornitore dal modulo **Anagrafiche** se eliminato
 - Fix visualizzazione referenti nel plugin **Sedi**
 - Fix stampa *Registro IVA*
 - Fix inclusione CSS personalizzato da **Impostazioni**
 - Fix esportazione footer delle tabelle principali

## 2.4.23 (2021-05-18)

### Aggiunto (Added)
 - Nuovo *Sconto finale* per gli **Ordini**, **Preventivi**, **DDT** e **Contratti**, influenza il valore *Netto a pagare* del documento.
 - Nuovo filtro in attività per mostrare al tecnico solo le attività assegnate.
 - Nuovo filtro in contratti per mostrare al cliente solo i contratti collegati.
 - Nuovo pulsante **Duplica Template** per copiare un template già esistente.
 - Aggiunto controllo nelle fatture di vendita per segnalare l'eventuale fatturazione di un'attività con la data di una sessione futura rispetto alla data della fattura.
 - Aggiunta possibilità di creare un ordine fornitore da un preventivo.
 - Aggiunto eventuale numero di telefono e/o cellulare nella stampa dei ddt con sede di destinazione diversa.
 - Aggiunta azione di gruppo in articoli per modificare il prezzo di vendita applicando una percentuale di sconto/rincaro al prezzo già esistente.
 - Aggiunta colonna unità di misura **UM** in movimenti di magazzino.
 - Aggiunta colonna **Tecnici assegnati** nel widget promemoria attività.
 - Aggiunto flag nella tabella **Spedizioni** per rendere obbligatoria la selezione del vettore nei ddt.
 - Aggiunta importazione del campo referente tra i documenti.
 - Aggiunta stampa dettaglio anagrafica e dati aziendali nel modulo **Anagrafiche**.
 - Aggiunta ora evasione negli ordini e in stampa.
 - Aggiunta possibilità di duplicare una sessione di lavoro.
 - Aggiunta colonna Prev. evasione nel modulo **Ordini**.
 - Aggiunta descrizione modificabile al momento della creazione di una revisione in un **Preventivo**.

### Fixed
 - Aggiornamento prezzo di listino quando viene cambiato il prezzo di acquisto (se collegato ad un fornitore).
 - Fix plugin **Pianificazione fatturazione** in **Interventi**
 - Rimossa l'obbligatorietà di inserire la data del documento nel modulo **Gestione documentale**

## 2.4.22 (2021-03-01)

### Aggiunto (Added)
 - Introduzione di nuove **Aliquote IVA** con specifiche più dettagliate
 - Nuovo campo condizioni generali di fornitura in **Preventivi**
 - Introduzione stampe del *Bilancio* e della *Fattura elettronica* per **Fatture di vendita e di acquisto**
 - Nuove azioni di massa sui record per
    - Creare **Preventivi** da **Articoli**
    - Cambiare lo stato a più **Ordini** e **DDT**
    - Allineare la quantità degli **Articoli**
    - Esportare le stampe delle Fatture Elettroniche
 - Aggiunta possibilità di importare **DDT di acquisto** in **DDT di vendita**
 - Aggiunta la possibilità di creare una **Nota di credito** da un **DDT di acquisto**
 - Nuova funzionalità di notifica automatica al *Cliente/Tecnico* quando viene cambiato lo stato dell'**Attività**
 - Nuovo flag per escludere la generazione della **Scadenza** di una Ritenuta d'Acconto se viene versata dal *Fornitore*
 - Introduzione del sistema di controllo sull'integrità delle logiche interne del gestionale
 - Nuovo sistema di registrazione delle procedure di importazione
 - Nuovo hook *Notifiche su Ricevute FE* per indicare graficamente eventuali **Fatture di vendita** che necessitano controlli manuali sullo stato
 - Nuovo *Sconto finale* per le **Fatture di vendita**: influenza il valore *Netto a pagare* della fattura in relazione alle singole scadenze, senza modificare il comportamento per i movimenti contabili

### Modificato (Changed)
 - Modifica della gestione degli importi per le Note di credito e debito: i campi di riepilogo (*qta*, *qta_evasa*, *subtotale*, *iva*, *ritenutaacconto*, *rivalsainps*) sono ora positivi.
 - Impostazione CAP automatico a 99999 nella FE per clienti esteri
 - Aggiornamento di CKEditor al fine di permettere l'utilizzo dell'intero insieme di plugin per funzionalità di editing più avanzate
 - Correzione del tipo di Fattura predefinito in caso di importazione da DDT (*Fattura differita*)
 - Correzioni varie sul sistema di sincronizzazione via API per l'applicazione mobile

### Fixed
 - Correzione movimenti di magazzino con sedi diverse
 - Correzione JS su input di tipo select con stesso ID
 - Correzione dimensione del campo *Tempo standard* in **Tipi di attività**
 - Correzione dei redirect al modulo **Impostazioni**
 - Fix del calcolo sullo spazio disponibili in GB
 - Fix procedura di pagamento automatico delle **Scadenze** sulla base dei movimenti in **Prima Nota**

## 2.4.21 (2021-01-14)

### Aggiunto (Added)
 - Aggiunto fallback selezione IVA per natura mancante in fase di import fattura di acquisto
 - Aggiunto filtro periodo anche per stampe mastrini di livello 1 e 2
 - Aggiunta gestione peso e volume automatici per DDT e Fatture

### Fixed
 - Fix falsi positivi su warning verifica numero fatture di vendita (#919)
 - Fix fornitore predefinito articoli (#928)
 - Correzioni in importazione FE
 - Fix per invio email (#923)
 - Correzione sconto unitario (#925)
 - Fix validazione codice fiscale
 - Fix dichiarazione intento su data fattura
 - Fix inclusione nel filtro periodo degli estremi temporali
 - Fix sul calcolo in base alla validità della data conclusione del contratto
 - Correzzione aggiunta **Codice destinatario** in fase di creazione **Anagrafica**
 - Correzzione selezione aliq. IVA in fase di import **Fatture di acquisto**

## 2.4.20 (2020-12-31)

### Aggiunto (Added)
 - Aggiunta alert sullo stato di **disponibilità dei tecnici**
 - Aggiunta verifica massiva su correttezza fatture elettroniche
 - Introduzione **nuovo sistema di esportazione CSV massivo** per impianti, anagrafiche e fatture
 - Aggiunta alert per **spazio su disco in esaurimento**
 - Aggiunta gestione **nomi e cognomi** durante l'importazione anagrafiche da **CSV**
 - Aggiunta flag di **rinnovo automatico** per i contratti
 - Aggiunta del segmento predefinito **Attività non completate**
 - Aggiunta impostazione per definire se poter selezionare articoli con quantità minore o uguale a zero nelle vendite
 - Aggiunta **importazione CSV articoli con prezzi specifici** (prezzo di acquisto/vendita per range, scontistica e prezzo per cliente/fornitore)
 - Aggiunta modifica sconto massivo su listini articoli
 - Aggiunta **stampa multipla per etichette articoli**
 - Aggiunta grafico delle ore lavorate dai tecnici mensilmente
 - Aggiunta nuova **azione massiva per fatturazione ordini cliente**
 - Aggiunta nuovo modulo per la creazione dei tipi di documento

### Modificato (Changed)
 - Miglioramento messaggi di errore per servizi di **fatturazione elettronica**
 - Miglioramento funzionalità **listini**
 - Separazione scadenzario Ri.Ba. per clienti e fornitori
 - Miglioramento movimentazione articoli tramite lettore barcode

### Fixed
 - Correzione impostazione conto economico in fase di fatturazione contratto
 - Correzione dichiarazioni di intento
 - Varie correzioni durante la creazione fatture
 - Correzione calcolo bollo per nuove nature iva
 - Correzione widget dei contratti da fatturare
 - Correzione nazione mancante durante importazione CSV delle anagrafiche


## 2.4.19 (2020-11-10)

### Aggiunto (Added)
 - Aggiunta gestione conto anticipi cliente e fornitore tramite **Prima nota**
 - Aggiunta colonna della sede nel modulo **Movimenti**

### Modificato (Changed)
 - Spostati i conti transitori (iva, ecc) su stato patrimoniale

### Fixed
 - Selezione banca **Preventivi**
 - Riferimenti attività **Fatture di vendita**
 - Permesso cambio stato fatture in Bozza
 - Correzione aggiornamento 2.4.11 per MariaDB
 - Correzione calcolo giacenze su plugin **Giacenze**
 - Esclusione fatture pro-forma dal calcolo fatturato sul modulo **Statistiche**
 - Gestito il conto articolo in fase di fatturazione attività se specificato nella scheda articolo


## 2.4.18 (2020-10-30)

### Aggiunto (Added)
 - Sidebar per la gestione grafica dei Plugin all'interno dei record per i Moduli
 - Sistema di cron di base per la gestione di operazioni ricorrenti (`cron.php`)
 - Avviso su conflitti di occupazione per i Tecnici in **Attività**
 - Plugin *Dettagli* per il modulo **Articoli**, finalizzato alla gestione dei prezzi di acquisto e vendita per *Anagrafica* e *Quantità* del Documento
 - Modulo **Giacenze sedi** per visualizzare le giacenze in specifiche *Sedi* dell'*Anagrafica* Azienda
 - Sistema di controllo sull'integrità strutturale del database, per utilizzo da parte di tecnici dedicati
 - Numerazione righe nella stampa **Ordini**
 - Azione di gruppo sul modulo **Anagrafiche** per calcolare la posizione geografica sulla base della Sede legale (richiede Google Maps abilitato)
 - *Copyright notice* su tutti i file del progetto
 - Possibilità di indicare lo stato alla creazione dei Documenti nella procedura di importazione
 - Traduzione di base per il Tedesco (Germania)
 - Supporto interno all'importazione verso **Preventivi** e **Contratti**
 - Base per gestione listini METEL
 - Possibilità di definire uno stato per le righe ordini (confermato/non confermato) per la gestione dell'impegno quantità
 - Movimento di carico per gli **Articoli** in fase di creazione
 - Miglioramento del sistema di gestione delle **Banche**, che ora permette la registrazione di informazioni contabili per tutte le **Anagrafiche**

### Modificato (Changed)
 - Integrazione completa del nuovo sistema per la gestione delle impostazioni dei select (`select-options`) e del nuovo metodo di inizializzazione e utilizzo degli input
 - Miglioramento della grafica di integrazione con Google Maps per **Attività** e **Anagrafiche**
 - Miglioramento del sistema di importazione FE in relazione alla gestione dei riferimenti a **Ordini** e **DDT**, con introduzione di una ricerca di base per l'autocompletamento
 - Caricamento AJAX delle righe dei Documenti
 - Introduzione numero rata in Rate contrattuali
 - Visualizzazione completa delle date di lavorazione nella stampa delle **Attività** (#828)
 - Modifica sovrapposizione eventi in **Dashboard**
 - Modifica della gestione JS dei campi numerici, ora basata sulla libreria [AutoNumeric](http://autonumeric.org/)
 - Modifica del sistema di riferimenti tra Documenti per includere il codice relativo nella Descrizione delle righe relative (con deprecazione dell'Impostazione "Riferimento dei documenti nelle stampe") {
 - Modifica del sistema di riferimenti tra Documenti per includere il codice relativo nella *Descrizione* delle righe relative (con deprecazione dell'*Impostazione* "Riferimento dei documenti nelle stampe")
 - Miglioramento elenco *Promemoria* in **Dashboard**, per visualizzare il *Tecnico* relativo
 - Corretta l'impostazione degli Sconti generici nei Documenti per l'utilizzo dell'importo ivato nel caso dell'impostazione "Utilizza prezzi di vendita comprensivi di IVA"
 - Corretta la gestione dei *Modelli di Prima Nota* e aggiunta integrazione con il sistema aggiornato
 - Aggiornato il modulo **Impostazioni** per permettere una navigazione agevole e semplificata
 - Modifica del sistema di gestione delle Ricevute FE, che ora permette la visualizzazione delle ricevute remote (con alcuni limiti) e la definizione di una ricevuta quale principale per la fattura

### Fixed
 - Correzione del numero delle righe sui Documenti a seguito di un riordinamento
 - Fix segno marca da bollo su *Nota di credito*
 - Fix selezione data sbagliata per vista mensile in Dashboard
 - Fix per aggiornamento indicato negli Hook anche a seguito del completamento
 - Correzione per l'eliminazione delle *Sedi*
 - Fix problema delle statistiche in **Stato dei servizi**
 - Fix visualizzazione sconto ivato sulla base dell'utilizzo dei Prezzi ivati
 - Fix visibilità per i checkbox standard del browser
 - Fix creazione di articoli duplicati da importazione FE (#870)
 - Correzioni per l'impostazione di *Categoria* e *Sottocategoria* dalla procedura di importazione CSV **Articoli**
 - Fix link interno al plugin *Impianti del cliente*
 - Correzioni sulla procedura di duplicazione **Attività**
 - Correzione del modulo **Pagamenti**
 - Fix varie in vista di PHP 8
 - Gestione del formato data FE con timezone

## 2.4.17.1 (2020-09-18)

### Fixed
 - Fix query aggiornamento data movimento per registrazioni in **Prima Nota**
 - Fix apretura pop-up di inserimento / modifica righe nelle varie schermate
 - Fix per idconto righe fattura da fatturazione in bulk
 - Fix calcolo quantità impegnata
 - Fix nome funzione duplicata durante aggiornamento 2.4.17
 - Fix Api per recupero delle sessioni di lavoro delle Attività in funzione dei mesi i mesi definiti nello storico
 - Fix allegati **Impianti** non trovati
 - Fix creazione / modifica componenti e componenti **Impianti** non trovati
 - Fix su calcolo movimenti in caso di insoluto
 - Fix raggruppamento movimenti composti

## 2.4.17 (2020-08-24)

### Aggiunto (Added)
 - Versione API per l'interazione con l'applicazione ufficiale (v3)
 - Modal intermedio per la duplicazione **Articoli**
 - Aggiunto controllo aggiuntivo sui checksum dei file (#705)
 - Sistema per l'assegnazione di specifici Tecnici ad **Attività senza sessioni di lavoro** (**Promemoria di attività**), con nuove impostazioni per la gestione della **Dashboard**
 - Funzioni JavaScript di utility per la gestione degli input
 - Introduzione del *Totale reddito* per i *Movimenti* della **Prima Nota** e del **Piano dei conti**, con relativa revisione della generazione dei *Movimenti*
 - Introduzione della sostituzione automatica per i caratteri speciali in Fattura Elettronica
 - Aggiunta la *Data prevista evasione* sulle righe degli **Ordini**
 - Aggiunto nome del firmatario nella stampa del **Rapportino attività**
 - Aggiunta procedura per il salvataggio dinamico delle modifiche dei documenti alla creazione/modifica delle righe (#636)

### Modificato (Changed)
 - Miglioramento dello stile delle checkbox
 - Sistema di gestione dei parametri per la generazione AJAX delle opzioni select (*select-options*)
 - Tabelle *responsive* per le righe di tutti i documenti
 - Modifica del modulo **MyImpianti** in **Impianti**
 - Miglioramento della struttura JavaScript della **Dashboard**
 - Aggiornamento del modal di aggiunta **Attività**
 - Separazione della gestione del *Bollo* e delle *Scadenze* dal codice delle *Fatture*
 - Aggiornamento della struttura dedicata all'importazione dei file CSV
 - Rimozione dello stato intervento "Chiamata" se inutilizzato

### Fixed
 - Fix della duplicazione di Fattura, che in alcuni casi non rimuoveva lo stato FE originale
 - Fix della procedura di duplicazione di gruppo per le **Attività**
 - Risoluzione bug nella modifica manuale della **Prima Nota** risalente a versioni <= 2.4.11 (#864)
 - Fix dell'ordinamento per i conti primari del **Piano dei conti**, con correzione dei totali di riepilogo relativi
 - Correzione sui tooltip bloccati sui pulsanti disabilitati

## 2.4.16 (2020-07-28)

### Aggiunto (Added)
 - Aggiunta possibilità di creare un contratto dalla scheda del preventivo
 - Aggiunta in supersearch la ricerca articoli per barcode
 - Aggiunta rivalsa INPS e relativa IVA per il calcolo del totale ivato del documento
 - Aggiunta colonna immagine per stampa preventivi
 - Aggiunto pulsante visualizza la scheda del promemoria
 - Aggiunta gestione allegati nello scadenzario
 - Aggiunto ID per righe documenti
 - Aggiunto avviso se ci sono fatture in elaborazione da più di 7 giorni per le quali non ho ancora ricevuto in feedback
 - Aggiunta possibilità di duplicare l'attività (anche in bulk)
 - Aggiunte operazioni di verifica notifica FE
 - Aggiunta scelta del sezionale prima della stampa del registro IVA
 - Aggiunta visualizzazione quantità disponibile in ordine
 - Agginata possibilità di specificare riferimenti tra i documenti (#822)
 - Aggiunti dettagli Fornitori per gli Articoli (#810)
 - Aggiunto prezzo vendita ivato per gli Articoli
 - Aggiunti periodi temporali per campo “Validità” (#806)
 - Aggiunto supporto alle Causali DDT non fatturabili
 - Aggiunti totali delle tabelle ristretti alla selezione
 - Aggiunta articoli in sequenza tramite barcode

### Modificato (Changed)
 - Allineamento Fattura Elettronica a versione schema XML 1.2.1
 - Aggiornamento foglio di stile FE Asso Invoice
 - Migliorato caricamento files con Dropzone
 - Aggiornamento a Gulp4
 - Migliorata stampa registro IVA
 - Compattazione grafica righe documenti
 - Ottimizzazione caricamento lista fatture

### Fixed
 - Fix pulsante compilazione automatica campi in fase di import Fattura Elettronica passiva
 - Fix statistiche per anagrafiche eliminate
 - Fix creazione sottocategoria articoli
 - Fix riporto sconti da attività a fattura (#817)
 - Fix calcolo numero progressivo in fase di duplicazione dei preventivi (#825)
 - Fix stampa preventivo per descrizioni lunghe con testo troppo piccolo (#759)
 - Ripristino TD01 per fatture differite
 - Fix widget "Contratti in scadenza"
 - Fix IVA con prezzi fino a 6 decimali
 - Fix valorizzazione campi anagrafica fornitore creata in fase di import Fattura Elettronica passiva (#840)
 - Fix sconto attività (#841)
 - Fix filtro articoli con caratteri speciali (#838)
 - Fix lunghezza campo PrezzoUnitario per problemi di arrotondamento e calcolo FE
 - Fix calcolo sconto su riga per prezzi ivati
 - Fix calcolo ore Consuntivo Contratti
 - Fix movimentazioni per Note di credito/debito
 - Fix Validazione codice articolo (#854)
 - Fix dicitura footer stampa fattura
 - Fix calcolo quantità per inventario
 - Fix stato dei Preventivi selezionabili

## 2.4.15 (2020-05-01)

### Aggiunto (Added)
 - Aggiunta impostazione per abilitare la notifica di nuove pre-release oltre a release stabili

### Modificato (Changed)
 - Ordinamento righe documenti anche in funzione dell'ID
 - Ottimizzato oscuramento campi prezzi per i tecnici quando è attiva l'opzione di nascondere i prezzi al tecnico

### Fixed
 - Bugfix Dashboard su vista settimanale e giornaliera (causato dalla nuova versione di JQuery)
 - Fix importazione Fattura Elettronica
 - Fix eliminazione campi dal Modulo Viste (#794)
 - Fix permessi API sync calendario per aggiungere il filtro cliente
 - Fix esportazione dati in CSV per leggere correttamente importi con le migliaia

## 2.4.14 (2020-04-23)

### Aggiunto (Added)
 - Nuove funzionalità nell'importazione delle Fatture Elettroniche
    - Riferimenti manuali a DDT e Ordini di acquisto nell'importazione delle Fatture Elettroniche
    - Compilazione automatica dei campi principali sulla base delle Fatture precedentemente importate
 - Controlli aggiuntivi sulla numerazione di **DDT** e **Fatture**
 - Fatturazione massiva di Contratti e Preventivi
 - Nuovo modulo **Stampe** sotto **Strumenti** per permettere la modifica manuale delle opzioni delle stampe
 - Visualizzazione informazioni su CIG, CUP anche nella stampa delle **Fatture**
 - Aggiunta prezzo vendita e acquisto in inserimento **Articoli**
 - Aggiunto elenco di **Fatture di vendita** in stato *Bozza* alla creazione
 - Aggiunta nuova stampa per i barcode degli **Articoli** e nuova variabile *revisione* nella stampa **Preventivi**
 - Aggiunta azione di cambiamento massivo dello stato negli **Interventi**
 - Aggiunto controllo sulla numerazione di **Fatture di acquisto** e **DDT in entrata**, con miglioramento delle informazioni per la numerazione delle **Fatture di vendita**
 - Supporto alla data di fine nella selezione dashboard (#556)
 - Aggiunta nuove aliquote e Nature IVA, e nuovi tipi documenti di Fattura Elettronica come da provvedimento Agenzia delle Entrate del 28/02/2020

### Modificato (Changed)
 - Revisione e aggiornamento dei plugin *Pianificazione interventi* e *Pianficazione fatturazione*
 - Modifica della gestione degli importi per le righe dei documenti (#758)
 - Il plugin *Movimenti* degli **Articoli** presenta ora un raggruppamento per documento (#766)
 - Aggiornamento del sistema di cache per prevedere una maggiore varietà di casi di utilizzo
 - Estensione suggerimento prezzi di acquisto e vendita per gli **Articoli** nei documenti
 - Rimozione blocco del codice destinatario sulla base della Tipologia di **Anagrafica** e rimozione dell'unicità obbligatoria del codice fiscale (#768)
 - Ottimizzazione della procedura di caricamento delle righe per **Fatture**, **Ordini** e **DDT**
 - Controllo del totale delle Fatture Elettroniche sulla base dei *Riepiloghi IVA*

### Fixed
 - Blocco della duplicazione per Fatture per cui esiste una Nota di credito
 - Gestione delle quantità evase per la fatturazione massiva di DDT
 - Abilitazione e disabilitazione API per utenti senza token
 - Modifica del totale per scadenze generiche in **Scadenzario** (#764)
 - Fix totale volume e peso nelle stampe DDT e Fatture
 - Fix percentuale negativa per Maggiorazione in Fattura Elettronica (#780)

## 2.4.13 (2020-02-05)

### Aggiunto (Added)
 - Aggiunta di nuovi campi di default nel modulo **Articoli**, con gestione delle quantità impegnati tramite gli ordini cliente
 - Aggiunta funzionalità di copia fatture in bulk
 - Aggiunta filtro di ricerca nelle tabelle con il carattere speciale "=" per ricercare una stringa o numero esatti
 - Aggiunta data e ora trasporto nei ddt
 - Aggiunta gestione nodo **ScontoMaggiorazione** su **Fattura elettronica**
 - Inserimento campi aggiuntivi per le importazioni di **Anagrafiche** e **Articoli**
 - Aggiunta gestione allegati nel modulo di **Prima nota**
 - Aggiunta creazione accesso utente dall'anagrafica per i tecnici
 - Aggiunta gestione filigrana per le stampe
 - Aggiunto modulo per la gestione delle tipologie di relazioni clienti
 - Ripristinato temporaneamente il plugin **Pianificazione fatturazione** nei **Contratti**
 - Aggiunta campo "Note" nello **Scadenzario**
 - Aggiunta visualizzazione dettagli ritenute applicate/calcolate nelle righe delle fatture

### Modificato (Changed)
 - Aggiornamento colonne con totali mantenendo solo l'imponibile
 - Miglioramento caricamento dettagli interventi a calendario tramite tooltip
 - Migliorata visualizzazione scadenze nella scheda fattura
 - Modificato invio email ritornando all'invio istantaneo quando viene effettuato l'invio dai documenti
 - Migliorata graficamente la gestione del modulo di movimentazione articoli
 - Ottimizzazione query sul **Piano dei conti**
 - Modificato funzionamento del calcolo numero progressivo per **Attività** e **Preventivi*, considerando la presenza dell'anno nel formato

### Fixed
 - Correzione generatore di numeri documenti in base alla data del documento e non più in base alla data odierna
 - Correzione calcoli nel plugin **Statistiche**
 - Corretta stampa registri iva
 - Correzione selezione sedi azienda
 - Correzione calcolo stato intervento dopo l'aggiunta di preventivi o contratti in fattura
 - Correzione calcolo trasferta in fattura
 - Correzione scelta destinatari multipli nell'invio mail
 - Correzione calcolo totali interventi svolti nel plugin **MyImpianti**


## 2.4.12 (2019-12-30)

### Aggiunto (Added)
 - Nuova tipologia di fattura elettronica TD02 Acconto/anticipo su fattura
 - Nuova impostazione per non sovrapporre le attività in dashboard
 - Movimentazione articoli dal modulo dei movimenti generali, con supporto a lettori barcode
 - Possibilità di fatturare interventi con importo totale pari a zero
 - Nuovo campo "Ubicazione" per gli articoli
 - Nuova funzione di apertura bilancio con la ripresa saldi dal periodo precedente
 - Nuova funzione di chiusura bilancio con lo spostamento saldi nel conto di chiusura
 - Possibilità di modifica dei conti di livello 2 e di quelli standard di livello 3

### Modificato (Changed)
 - Rimozione funzione di autocompletamento campi di testo da browser

### Fixed
 - Fix caricamento assets dopo aggiornamenti, con aggiunta versionamento
 - Fix creazione utenti con stesso username
 - Fix movimenti contabili alla riapertura fattura
 - Fix creazione nota di credito
 - Fix salvataggio sessioni di lavoro da accesso tecnico
 - Fix gestione categorie documenti
 - Fix orario lavorativo in dashboard
 - Fix problema generazione stampe in invio mail durante accesso multi-utente
 - Fix calcolo margine nei preventivi
 - Fix importazione note di credito con importi negativi
 - Altri fix minori


## 2.4.11 (2019-11-29)

### Aggiunto (Added)

 - Nuova sezione *Note interne* nei moduli **Anagrafiche**, **Attività**, **Preventivi**, **Contratti**, **Fatture di vendita**, **Fatture di acquisto**, **Scadenzario**, **Ordini cliente**, **Ordini fornitore**, **Articoli**, **DDT di uscita**, **DDT di entrata** e **MyImpianti**
 - Nuova sezione *Checklist* nei moduli **Attività** e **MyImpianti**
 - Nuova procedura di ripristino password via email
 - Supporto a multiple versioni dell'API interna, per mantenere la compatibilità con servizi esterni collegati a seguito di aggiornamenti e nuove funzionalità
 - Possibilità di registrare contabilmente in modo massivo le fatture in **Fatture di vendita** e le scadenze in **Scadenzario**
 - Possibilità di importare in sequenza tutte le *Fatture Elettroniche* presenti, con supporto alle relazioni delle **Fatture di acquisto** con le *Note di credito/debito* e le *Parcelle*
 - Supporto al footer solo nell'ultima pagina per le stampe (**Fatture di vendita** e **DDT di uscita**)  tramite l'opzione *last-page-footer*
 - Informazioni più complete sulle *Fatture Elettroniche* da importare per gli utenti con servizio di importazione automatica
 - Possibilità di indicare una foto per l'utente, visualizzata nelle *Note interne* e nei futuri allegati che verranno caricati
 - Possibilità di modificare il nome delle categorie degli allegati
 - Stampe dei consuntivi interni (i prezzi sono sostituiti dai costi)
 - Supporto all'inserimento manuale di maggiori attributi per le *Fatture Elettroniche*, tramite gli appositi pulsanti "Attributi avanzati" all'interno delle **Fatture di vendita**
 - Aggiunto Identificativo documento, Num Item, codici CIG e CUP in **DDT di uscita**
 - Modulo **Newsletter** per la gestione delle campagne di newsletter sulla base delle informazioni delle **Anagrafiche**
 - Supporto alle *Dichiarazione d'Intento* per le *Fatture di vendita**
 - Calcolo del margine per i **Preventivi**
 - Supporto alla selezione della lingua durante la configurazione
 - Gestione dei permessi per gruppi all'interno del sistema di **Gestione documentale**
 - Supporto agli sconti combinati nel modulo **Listini**
 - Supporto al caricamento di archivi ZIP per le *Fatture Elettroniche* di acquisto da importare (solo estrazione)

### Modificato (Changed)

 - Aggiornamento delle stampe di *Riepilogo intervento*, *Consuntivo contratto* e *Consuntivo preventivo*
 - Correzione dell'importazione delle *Fatture Elettroniche* per supportare Ritenuta d'Acconto (dove indicata), Rivalsa INPS (su tutto il documento) e Ritenuta contributi (su tutto il documento)
 - Miglioramento del sistema di evasione delle quantità nel passaggio tra documenti, ora integrato nelle classi Eloquent e completamente automatico
 - Correzione delle diciure generali *Imponibile scontato* in *Totale imponibile* e *Sconto* in *Sconto/maggiorazione*
 - Aggiornamento degli hook per permettere l'aggiunta di task in background
    - Invio delle email
    - Backup automatico
 - **Articoli** ora eliminabili solo virtualmente attraverso il flag *deleted_at*
 - Miglioramento del plugin *Giacenze* nel modulo **Articoli** per interagire con gli **Ordini** registrati, e aggiunta della quantità progressiva per nel plugin *Movimenti*
 - Generazione del numero delle **Fatture di vendita** a seguito dell'emissione della stessa
 - Supporto alla precisione di importi e quantità fino a 5 decimali
 - Opzione per la creazione automatica degli articoli presenti in **Fattura Elettronica**
 - Revisione della visualizzazione grafica del modulo **Prima Nota**, per rendere più chiara la suddivisione logica delle righe in relazione all'evasione delle scadenze
 - Aggiornamento delle stampe *Inventario magazzino* e *Calendario*

### Rimosso (Removed)
 - Funzione *get_costi_intervento* del modulo **Attività**, a causa dell'aggiornamento della maggior parte del sistema di gestione degli **Attività** con le classi Eloquent
 - Funzione *aggiorna_scadenziario* del modulo **Prima Nota**
 - Classe *src/Mail.php*

### Fixed

 - Fix selezione di articoli senza movimenti
 - Fix per l'autocompletamento delle email nella procedura di invio

## 2.4.10 (2019-07-23)

### Aggiunto (Added)

 - Possibilità di gestire più magazzini attraverso la sezione delle sedi nelle **Anagrafiche** (gli **Automezzi** sono stati trasformati in **Sedi**, con possibilità di tracciamento di partenza e destinazione tra le sedi)
 - Modulo **Tipi scadenze** (in **Strumenti** -> **Tabelle**) per gestire i tipi di scadenze
 - Prima versione della traduzione parziale in inglese del gestionale
 - Validazione AJAX dei campi (*partita iva*, *codice fiscale* e *codice* in **Anagrafiche**, *codice* in **Articoli**)
 - Possibilità di ripristinare gli elementi eliminati dove l'eliminazione avviene a livello virtuale (**Anagrafiche**)
 - Plugin **Rinnovi** in **Contratti**
 - Caricamento del **Piano dei conti** attraverso AJAX
 - Plugin *Statistiche* in **Articoli**, con visualizzazione del *Prezzo medio acquisto* in periodi personalizzabili
 - Supporto ai select come **Campi personalizzati**
 - Possibilità di generazione massiva delle fatture elettroniche

### Modificato (Changed)

 - Miglioramento grafica degli hook, con gestione automatica degli aggiornamenti delle informazioni causati da altre componente del gestionale
 - Le tariffe dei tecnici sono state standardizzate nel seguente modo:
    - Il modulo **Tipi di attività** permette di definire le tariffe standard per i nuovi tecnici
    - Il modulo **Tecnici e tariffe** permette di definire le tariffe personalizzate per i diversi tecnici in relazione ai tipi di attività
    - Il modulo **Contratti** permette di definire le tariffe personalizzate per le *nuove sessioni* delle attività collegate
    - La sezione di modifica delle sessioni permette la modifica manuale delle tariffe interessate; il cambiamento del tipo di sessione provoca l'utilizzo delle tariffe definite da **Tecnici e tariffe**
 - Ottimizzazione delle stampe **Scadenzario** e **Registro IVA**, e della tabella principale del modulp **Fatture di vendita**
 - Miglioramento della plugin *Statistiche* in **Anagrafiche**,con visualizzazione dei dati in periodi personalizzabili
 - Miglioramento del sistema di importazione delle ricevute delle Fatture Elettroniche, per permetterne il caricamento manuale
 - Standardizzazione dei nomi predefiniti delle stampa e dei relativi file generati

### Rimosso (Removed)
 - Supporto ai raggruppamenti di **Contratti** e **Preventivi** nelle **Fatture**

### Fixed

 - Fix export delle tabelle principali in Excel
 - Fix bug della configurazione iniziale nella selezione della nazione
 - Fix delle somme filtrate sulle tabelle principali
 - Fix per includere le stampe previste nelle notifiche
 - Risolti alcuni bug generali

## 2.4.9 (2019-05-17)

### Aggiunto (Added)

 - Possibilità di ricalcolare le scadenze delle **Fatture di acquisto** importate da fatture elettroniche
 - Campo *Data registrazione* e *Data competenza*  per le **Fatture di acquisto**
 - Stampa **Preventivo** senza costi totali
 - Impostazione di esportazione massiva degli XML delle **Fatture di vendita**
 - Impostazioni "Riferimento dei documenti nelle stampe" e "Riferimento dei documenti in Fattura Elettronica" per permettere l'inclusione o meno delle relative diciture in stampe e Fattura Elettronica
 - Supporto all'importazione delle Fatture Elettroniche Semplificate e alle notifiche ZIP
 - Sistema di confronto dei totali delle Fatture Elettroniche importate (totale nel file XML) con il totale calcolato dal gestionale per la visualizzazione grafica di eventuali errori di arrotondamento
 - Pulsante per impostare la Fatture Elettroniche remota come processata **(integrazione con sistemi interni)**
 - Modulo **Stato dei servizi** per la gestione di widget e moduli, e la visualizzazione dello spazione occupato
  - Sistema di hook (e notifiche) per l'esecuzione automatica di alcune azioni periodiche
    - Controllo automatico della presenza di Fatture Elettroniche da importare **(integrazione con sistemi interni)**
    - Controllo automatico della presenza di ricevute di Fatture Elettroniche rilasciate **(integrazione con sistemi interni)**
 - Possibilità di duplicare gli **Impianti**

### Modificato (Changed)

 - La marca da bollo considera solo le righe con esenzione iva da natura N1 a N4, ed è modificabile manualmente a livello di fattura
 - Gli sconti incodizionati sono ora gestiti a tutti gli effetti come righe
 - Miglioramento della procedura di interpretazione degli XML codificati in P7M (basata sulla libreria OpenSSL)
 - Aggiornati gli stylesheet per le notifiche della Fattura elettronica
 - Possibilità di ricercare per valori maggiori/uguali o minori/uguali sui campi delle tabelle (importi)
 - Spostamento della gestione di widget e moduli da **Aggiornamenti** al modulo **Stato dei servizi**
 - I totali vengono visualizzati e arrotondati sempre a due cifre per legge (la modifica consiste **solo nella visualizzazione dei totali**, e non influenza i conteggi in alcun modo)
 - Modernizzazione del plugin *Statistiche* nel modulo **Anagrafiche**

### Fixed

 - Fix selezione righe multiple sulle tabelle
 - Fix dei conteggi dei widget *Acquisti* e *Fatturato* (esclusione dell'IVA)
 - Fix dei ripristini delle quantità evase nei **Preventivi** e nei **Contratti**
 - Fix API per APP OSM
 - Fix per compatibilità con MySQL 8
 - Risolti altri bug generali

## 2.4.8 (2019-03-01)

### Aggiunto (Added)

 - Possibilità di scorporare l'IVA dal prezzo di vendita nel modulo **Articoli**
 - Ritenuta contributi nella stampa di **Fatture di vendita**
 - Supporto al campo *NumItem* nella Fatture Elettronica
 - Aggiunta la data di scadenza per le **Attività**

### Modificato (Changed)

 - Miglioramento del caricamento delle opzioni Ajax per i select
 - Miglioramento della procedura di importazione: i contenuti vengono importati un po' alla volta, evitando così problemi di *timeout* del server

### Fixed

 - Fix della procedura di passaggio tra documenti (con supporto agli *sconti incondizionati*)
 - Fix di un bug nella movimentazione articoli nel passaggio tra documenti
 - Fix della creazione delle categorie articoli e impianti
 - Risolti altri bug generali

## 2.4.7 (2019-02-21)

### Aggiunto (Added)

 - Aggiunto possibilità per evitare i movimenti causati da Fatture Elettroniche importate
 - Supporto delle fatture alle ritenute contributi
 - Solleciti di pagamento nel modulo **Scadenzario**

### Modificato (Changed)

 - Miglioramento del sistema di importazione dei diversi documenti in fattura

### Fixed

 - Fix di diversi bug nella procedura di importazione XML
 - Fix degli sconti nelle note di credito
 - Risolti alcuni bug distribuiti

## 2.4.6 (2019-02-12)

### Aggiunto (Added)

 - Introduzione della seconda ritenuta (ad esempio, *Contributo Enasarco*)
 - Introduzione della fatturazione per conto terzi
 - Aggiunto stato elaborazione fattura elettronica per **Fatture di vendita**
 - Aggiunto codice cig, cup e identificativo documento per **Preventivi**

### Modificato (Changed)

 - Miglioramento della generazione xml per le Fatture Elettroniche
 - Miglioramento procedura importazione xml per le Fatture Elettroniche
 - Gestito split payment nella fattura elettronica

### Fixed

 - Fix del calcolo dei codice preventivo, ordine, ddt e fattura
 - Fix valori non riportati in fase di inserimento di una nuova attività
 - Fix aggiunta del contratto in fattura
 - Fix aggiunta articolo in attività
 - Fix calcolo sconto per nota di credito fa fattura di vendita
 - Risolti altri bug minori

## 2.4.5 (2019-01-10)

### Aggiunto (Added)

 - Introduzione dello split payment
 - Introduzione dei campi Nome e Cognome per le anagrafiche
 - Introduzione della possibilità di non verificare il certificato SSL per gli account email
 - Introduzione calcolo del guadagno in fase di aggiunta righe nei documenti

### Modificato (Changed)

 - Miglioramento della generazione xml per le Fatture Elettroniche
 - Miglioramento procedura importazione xml per le Fatture Elettroniche
 - Gestite righe di tipo descrizione nelle Fatture Elettroniche

### Fixed

 - Fix calcolo codice intervento
 - Fix dei filtri per la stampa del riepilogo interventi
 - Risolti altri bug minori

## 2.4.4 (2018-12-12)

### Aggiunto (Added)

 - Controllo sulla presenza di personalizzazioni nel modulo **Aggiornamenti**
 - Stati multipli per le Fatture Elettroniche (per ampliamenti futuri)

### Fixed

 - Risolti malfunzionamenti negli import degli allegati della Fattura Elettronica
 - Risolti diversi bug

## 2.4.3 (2018-12-07)

### Aggiunto (Added)

 - Nodi secondari per la Fatturazione Elettronica
 - Importazione di Fatture Elettroniche in formato P7M
 - Messaggi informativi in vari campi

### Fixed

 - Risolti alcuni problemi di compatibilità
 - Risolti malfunzionamenti delle righe dei documenti
 - Fix dei calcoli

## 2.4.2 (2018-11-14)

### Aggiunto (Added)

 - Plugin per generazione della Fatturazione Elettronica (modulo **Fatture di vendita**) e l'importazione relativa (modulo **Fatture di acquisto**)
 - Libreria autonoma per i messaggi da mostrare all'utente
 - Logging completo delle azioni degli utente (accessibile agli Amministratori)
 - Supporto a [Prepared Statements PDO](http://php.net/manual/it/pdo.prepared-statements.php)
 - Impostazioni da definire durante l'installazione e l'aggiornamento del software
 - Helper per semplificare lo sviluppo di codice indipendente (file `lib/helpers.php`)
 - Funzioni generiche per moduli e plugin (file `lib/common.php`)
 - API per la gestione dell'applicazione
 - Classe `Util\Zip` per la gestione dei file ZIP
 - Controllo automatico degli aggiornamenti da GitHub (modulo **Aggiornamenti**)
 - Ripristino semplificato dei backup (modulo **Backup**)
 - Impostazioni per impostare un orario lavorativo personalizzato nel modulo **Dashboard**
 - Possibilità di impostare un elemento predefinito per i moduli **Porti**, **Causali** e **Tipi di spedizioni**
 - Impostazione *Stampa per anteprima e firma* per selezionare la stampa da mostrare nella sezione **Anteprima e firma** di **Attività**
 - Ritenuta d'acconto predefinita per le **Anagrafiche**
 - Sistema automatizzato per l'importazione delle classi di moduli e plugin (file `config/namespaces.php`)
 - Sistema di notifiche predefinito
    - Notifica di chiusura delle **Attività** (impostabile dal modulo **Stati attività**)
    - Notifica di aggiunta e rimozione del tecnico dalle **Attività**
 - Gestione revisione preventivi
 - Categorizzazione impianti
 - Modulo per gestione documentale
 - Categorizzazione allegati

### Modificato (Changed)

 - Normalizzazione delle nazioni registrate dal gestionale (https://github.com/umpirsky/country-list)
 - Gestione delle strutture principali attraverso modelli (**Eloquent**)[https://laravel.com/docs/5.6/eloquent]
 - Miglioramenti nella gestione dei record (variabile `$record` al posto di `$records[0]`)
 - Ottimizzazione delle query di conteggio (metodo `fetchNum`)
 - Miglioramento del sistema di aggiornamento e installazione, con supporto completo ai plugin
 - Drag&drop nella **Dashboard** permette di impostare le attività senza sessioni di lavoro
 - Aggiungere un tecnico in una **Attività** salva le modifiche apportate in precedenza
 - Rinominat moduli ddt in "Ddt in uscita" e "Ddt in ingresso"
 - Miglioramenti grafici vari

### Deprecato (Deprecated)

 - Variabili globali $post e $get, da sostituire con le funzioni `post()` e `get()`
 - Funzione `get_var()`, da sostituire con la funzione `setting()`
 - Funzioni PHP inutilizzate: `datediff()`, `unique_filename()`, `create_thumbnails()`

### Rimosso (Removed)

 - Funzioni PHP deprecate nella versione 2.3.*

### Sicurezza (Security)

 - Abilitata protezione contro attacchi CSRF (opzione `$disableCSRF` nella configurazione per disattivarla in caso si verifichino problemi)

## 2.4.1 (2018-08-01)

### Aggiunto (Added)
 - Supporto alla generazione PDF/A
 - Gestione di Note di accredito e di addebito per le Fatture
 - Salvataggio AJAX delle righe in Fatture
 - Cambio automatico dello stato dei documenti
 - Nomi per i filtri di accesso ai moduli
 - Anteprime degli upload (per immagini e PDF)
 - Validazione di indirizzi email e codici fiscali
 - Test della connessione al server email
 - Widget *Attività da pianificare* per individuare le attività senza tecnici
 - Esportazione tabelle in PDF ed Excel (impostazione *Abilita esportazione Excel e PDF*)
 - Stampa dedicata al calendario attività in **Dashboard**
 - Operazioni rapide su **Anagrafiche** di tipo *Cliente*
 - Campi aggiuntivi nella creazione di nuove **Anagrafiche**
 - Possibilità di specificare tempi standard per *Tipologia di attività*
 - Seriali nella stampa delle **Attività**
 - Quantità calcolata tramite movimenti in data attuale per **Articoli**
 - Movimenti manuali con causale degli **Articoli**

### Modificato (Changed)
 - Miglioramento della gestione di installazione/aggiornamento
    - Migliorata la procedura per i moduli (esempi: https://github.com/devcode-it/example)
    - Aggiunto supporto all'installazione dei plugin (esempio: https://github.com/devcode-it/example/tree/master/sedi)
    - Aggiunto supporto a file ZIP con vari moduli/plugin (installazione in ordine alfabetico)
 - Miglioramento dei pre-requisiti di installazione
 - Gestione degli upload tramite AJAX
 - Gestione del logo per le stampe come un allegato
 - Gestione delle immagini di **Articoli** e **Impianti** come allegati
 - Miglioramento del plugin *Pianificazione attività* in **Contratti**
 - Miglioramento della ritenuta d'acconto (calcolo impostabile su Imponibile o Rivalsa INPS)
 - Ripristinati plugin *Pianificazione fatturazione* e widget *Rate contrattuali*
 - Miglioramento della tabella dei *Costi Totali* in **Attività**
 - Collegamento ad un'anagrafica obbligatorio per i nuovi utenti
 - Ridenominazione delle tabelle `co_righe_contratti` e `co_righe2_contratti` in `co_contratti_promemoria` e `co_righe_contratti`
 - I movimenti articoli utilizzano la data del documento relativo
 - I chilometri del cliente vengono riportati nell'attività
 - I tecnici possono aggiungere **Attività** solo a loro nome

### Fixed
 - Correzione dei link alle stampe sulle tabelle dei moduli
 - Correzione della scontistica per la stampa **Attività**
 - Correzione degli arrotondamenti su IVA e imponibili nei documenti
 - Correzione del budget dei **Contratti**
 - Correzione della scadenza "Data fattura fine mese"
 - Correzione del plugin *Statistiche* in **Anagrafiche**
 - Correzione del widget *Debiti verso fornitori*
 - Correzioni minori

## 2.4 (2018-03-30)

### Aggiunto (Added)

 - Modelli di stampa su database, con possibilità di creare più stampe per singolo modulo e raggrupparle in unica voce di menu
 - Possibilità di inviare le email dai vari moduli e gestione degli account SMTP
 - Introduzione dei segmenti: filtri aggiuntivi definibili per ogni modulo
 - Aggiunti sezionali per fatture acquisto/vendita
 - Nuovo modulo archivio banche per definire poi in ogni anagrafica (cliente o fornitore) la banca predefinita
 - Nuova pagina dedicata all'utente dove è possibile:
   - Cambiare la propria password
   - Visualizzare il proprio token di accesso all'API
   - Visualizzare il link e le informazioni per importare il calendario eventi all'esterno del gestionale
 - Introduzione della possibilità di poter impostare dei campi personalizzati per ogni modulo
 - Aggiunta possibilità di inserire un articolo in contratti e preventivi
 - Aggiunta di una variabile $baseurl globale
 - Aggiunta nei documenti la possibilità di inserire una riga descrittiva senza importi
 - Aggiunta creazione fattura da contratto
 - Aggiunta scelta iva su attività per spese aggiuntive e materiale
 - Aggiunta gestione allegati anche per contratti, anagrafiche, preventivi, articoli, impianti
 - Modulo per import CSV (anagrafiche)

### Modificato (Changed)

  - Modificati pulsanti principali dei moduli e fissati in alto durante lo scorrimento
  - Resi i pulsanti principali dei moduli dinamici e personalizzabili
  - Migliorati attività da pianificare
  - Migliorato il calcolo della numerazione per i documenti
  - Modificato il numero per le fatture di acquisto utilizzabile per numeri di protocollo
  - Migliorata gestione dei menu a tendina dinamici
  - Modificata aggiunta attività in fatturazione, con raggruppamento per costi orari e diritti di chiamata
  - Modificato calcolo ritenuta d'acconto, con scelta se calcolare su imponibile o imponibile + rivalsa inps

### Fixed

 - Corretto calcolo IVA con sconto globale unitario
 - Corretto calcolo numerazione dei ddt
 - Correzione visualizzazione di attività a calendario a cavallo di periodi diversi
 - Correzioni minori

## 2.3.1 (2018-02-19)

### Aggiunto (Added)

 - Aggiunti i seriali in stampa
 - Aggiunta la zona nelle attività (in sola lettura dall'anagrafica)
 - Aggiunta tramite flag la possibilità di inserire la descrizione dell'attività in fattura
 - Aggiunta esportazione bulk in zip dei pdf delle attività selezionate
 - Aggiunte informazioni del cliente e fornitore nelle relative stampe ordini

### Modificato (Changed)

 - Migliorati i widget di "Crediti da clienti" e "Debiti verso fornitori", con calcolo parziale del rimanente
 - Disabilitato di default il modulo "Viste"
 - Migliorata la gestione della pianificazione attività sui contratti, con la possibilità di eliminare tutte le pianificazioni o di creare direttamente una attività collegata
 - Modificato l'inserimento di attività in fattura raggruppando per costo orario nel caso ci siano più costi orari
 - Spostato il conto "Perdite e profitti" nello stato patrimoniale

### Fixed

 - Corretti diversi problemi in fase di installazione
 - Modifica e miglioramento dell'arrotondamento iva in fattura, sia a video che in stampa
 - Corretto il caricamento di menu a tendina per gli utenti con permessi limitati
 - Corretti i permessi per la stampa fattura per utenti con permessi limitati
 - Corretto e migliorato il funzionamento delle viste
 - Corretto il calcolo dello sconto incondizionato in percentuale nei principali moduli
 - Corretta la stampa consuntivo del preventivo
 - Corrette alcune funzioni dello scadenzario, in quanto sparivano delle scadenze in fase di modifica prima nota
 - Corretto il cambio di stato automatico di ddt dopo la fatturazione
 - Migliorato il caricamento dinamico del calendario via ajax in quanto a volte si bloccava
 - Correzioni varie sulla gestione viste
 - Corretto il piano dei conti per arrotondare gli importi come negli altri moduli
 - Corretto il calcolo iva nei contratti
 - Corretto il salvataggio delle sessioni tecnico nelle proprie attività
 - Corretto un problema nel salvataggio firma attività su alcuni tablet
 - Corretto ordinamento voci di menu laterale
 - Altre correzioni minori e strutturali


## 2.3 (2018-02-16)

### Aggiunto (Added)

- Creazione di sistemi centralizzati per la gestione della funzioni principali del progetto (secondo una logica ad oggetti)
  - Connessione al database (tramite PDO, con possibile ampliamento dei DMBS supportati)
  - Autenticazione degli utenti
  - Gestione e controllo dei permessi
  - Gestione degli input degli utenti
  - Personalizzazione delle impostazioni
  - Traduzione e conversione dei formati (date e numeri)
  - Gestione degli aggiornamenti
- Creazione della documentazione ufficiale per sviluppatori (disponibile nel Wiki e in `docs/`)
- Creazione di un sistema API ufficiale
- Creazione di cartelle di default per i backup (`backup/`) e i log (`logs/`)
- Completo supporto alla traduzione del progetto
- Possibilità di vedere se ci sono altri utenti che stanno visualizzando lo stesso record (opzione "Attiva notifica di presenza utenti sul record" nel modulo **Impostazioni**)
- Possibilità di creare nuovi elementi dei moduli all'interno del record (oltre che dalla visualizzazione generale del modulo)
- Nuova struttura per permettere il richiamo via AJAX delle procedure per la creazione di nuovi elementi all'esterno del modulo specifico (tramite il file `add.php`)
- Nuovo sistema di gestione delle operazioni di debugging e logging
- Nuovi plugins e widgets
- Introduzione di nuovi moduli primari e secondari
  - **Viste**
  - **Utenti e permessi** (convertito)
  - **Impostazioni** (convertito)
  - **IVA**
  - **Pagamenti**
  - **Porto**
  - **Unità di misura**
  - **Aspetto beni**
  - **Causali**
  - **Categorie**
  - **Ritenute acconto**
  - **Movimenti**
- Nuovo modulo per gestire i file `.ini` dei componenti degli impianti (**Gestione componenti**)
- Nuovo pulsante per resettare i filtri di ricerca (nella sezione generica dei moduli)
- Nuova gestione generalizzata degli upload
- Nuova sistema di gestione delle operazioni _bulk_ degli upload
- Nuova documentazione integrata delle funzioni PHP in `lib/functions.php`
- Nuovo file `lib/init.js` per permettere una rapida inizializzazione dei componenti JS
- Nuove funzioni relative ai diversi moduli
  - Introduzione della numerazione univoca per gli impianti (**MyImpianti**)
  - Possibilità di individuare i componenti dell'impianto su cui l'attività viene effettuato (**Attività**)
  - Possibilità di firmare le attività (**Attività**)
  - Possibilità di selezionare della tipologia di attività per ogni sessione di lavoro (**Attività**)
  - Introduzione di una tabella riepilogativa più completa dei costi (**Attività**)
  - Introduzione di sconti globali e specifici (unitari e percentuali) in **Contratti**, **DDT**, **Fatture**, **Attività**, **Preventivi**, **Ordini**

### Modificato (Changed)

- Gestione delle librerie e dipendenze PHP tramite _Composer_
- Gestione degli assets tramite _Yarn_ e _Gulp_
  - Miglioramenti grafici
  - Sostituzione di _Chosen_ con _Select2_
  - Gestione delle tabelle ora completamente basata su _Datatables_
- Miglioramento della procedura di installazione e aggiornamento del gestionale
  - Aggiunto sistema di ripresa dell'aggiornamento (se questi è stato bloccato in una fase intermedia tra i singoli aggiornamenti)
  - Aggiunto sistema di bloccaggio dell'aggiornamento, per evitare problemi nel caso molteplici richieste di update
  - Semplificazione della procedura manuale, che ora non richiede nessuna modifica dei file VERSION da parte dell'utente
  - Modificata la struttura della tabella `updates`
- Passaggio completo all'estensione `.php` per tutti i file dei moduli
- Miglioramento dell'interpretazione del template per la generazione degli input, ora disponibile ovunque all'interno del progetto
- Miglioramento della gestione dei permessi
- Miglioramento delle stampe principali
- Miglioramento delle informazioni disponibili sul progetto e della procedura di segnalazione dei bug
- Miglioramento generale sull'identificazione del modulo attualmente in uso e sull'inclusione dei file necessari per il funzionamento
- La prima anagrafica di tipo Azienda caricata viene impostata come "Azienda predefinita"
- Ottimizzazione della schermata per aggiunta dell'attività
- Miglioramento dei riquadri delle spese aggiuntive e degli articoli
- Miglioramento dei permessi di visione per il modulo **MyImpianti** (ogni cliente vede solo i propri impianti)

### Deprecato (Deprecated)

- Classe HTMLHelper, a favore della nuova classe Filter
- Funzioni PHP (`lib/deprecated.php`)

### Rimosso (Removed)

- Funzioni PHP non utilizzate (`lib/functions.php`)
  - coolDate
  - cut_text
  - data_italiana
  - dateadd
  - full_html_entity_decode
  - gestione_sessioni
  - get_module_name
  - get_module_name_by_id
  - get_text_around
  - get_user_browser
  - getAvailableModules
  - getLastPathSegment
  - getSistemaOperativo
  - getVersion
  - is_id_ok
  - mytruncate
  - read_file
  - RemoveNonASCIICharacters
  - show_error_messages
  - show_info_messages
  - write_error
  - write_ok
- Funzioni JS non utilizzate (`lib/functionsjs.php`)
- Cartelle e file non più utilizzati (`lib/jscripts`, `widgets`, `share`, `lib/dbo.class.php`, `lib/widgets.class.php`, ...)

### Fixed

- Risoluzione di numerosi bug e malfunzionamenti

### Sicurezza (Security)

- Aggiunta protezione contro attacchi di tipo XSS
- Aggiunta base per contrastare l'SQL Injection
- Aggiunta protezione (temporaneamente disabilitata) contro attacchi CSRF
- Aggiunto sistema basilare contro attacchi brute-force all'accesso
- Passaggio della codifica della password con algoritmo di hashing BCrypt

## 2.2 (2016-11-10)

### Aggiunto (Added)

- Aggiunto ordinamento righe in fattura e stampa con ordine impostato
- Creazione automatica del conto cliente e fornitore nel piano dei conti
- Aggiunte stampe dei mastrini nel piano dei conti
- Aumentata performance caricamento record sulle viste principali dei moduli
- Aggiunta funzionalità di rinnovo contratto con collegamento a contratti precedenti
- Migliorata gestione dei backup (1 backup al giorno)
- Aggiunta tipologia di attività di default nel cliente per pre-caricarla durante la creazione attività
- Aggiunta funzionalità di firma rapportino e stampa del rapportino con firma inserita
- Modifica raggruppamento voci di menu, principalmente "Vendite" e "Acquisti"
- Aggiunta funzionalità di duplicazione fattura
- Migliorata la procedura di installazione
- Aggiunta richiesta di salvataggio prima di uscire da una schermata
- Aggiunta possibilità di collegare più agenti ad un cliente, e specificarne uno principale
- Aggiunta schermata di visualizzazione accessi
- Aggiunte rivalsa inps e ritenuta d'acconto nelle singole righe in fattura
- Aggiunti widget "Valore magazzino" e "Articoli in magazzino"
- Aggiunta stampa viste principali da browser con buona grafica minimale
- Aggiunta gestione componenti
- Aggiunta possibilità di generare lotti e serial number dalla fattura e ddt di acquisto
- Aggiunta possibilità di impostare dei costi unitari per ogni tipo di attività collegata al contratto, per utilizzare prezzi concordati nel contratto durante le attività

### Fixed

- Bugfix vari sui permessi
- Bugfix minori

## 2.1 (2015-04-02)

### Aggiunto (Added)

- Aggiunto stato “Parzialmente pagato” sulle fatture
- Aggiunta stampa scadenzario
- Aggiunta possibilità di includere più ddt in fattura
- Aggiunto blocco sulla modifica campi di testo per gli utenti in sola lettura
- Aggiunta scelta rivalsa inps e ritenuta d’acconto per ogni riga della fattura

### Modificato (Changed)

- Allargate le cifre decimali a 4 sugli importi

### Fixed

- Alcune migliorie su vari moduli
- Aumentata performance schermate
