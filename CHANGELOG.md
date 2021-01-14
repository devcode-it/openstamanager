# Changelog

Tutti i maggiori cambiamenti di questo progetto saranno documentati in questo file. Per informazioni più dettagliate, consultare il log GIT della repository su GitHub.

Il formato utilizzato è basato sulle linee guida di [Keep a Changelog](http://keepachangelog.com/), e il progetto segue il [Semantic Versioning](http://semver.org/) per definire le versioni delle release.

- [2.4.21 (2021-01-)](#2421-2021-01-14)
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
