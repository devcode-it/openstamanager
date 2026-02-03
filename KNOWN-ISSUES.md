# Problematiche Note - OpenSTAManager

Questo documento raccoglie e documenta le problematiche del gestionale OpenSTAManager che sono già state identificate e risolte dalla community di sviluppo.

## Struttura del documento

Le problematiche sono organizzate per versione di release in ordine cronologico decrescente (dalla più recente alla più datata). Per ogni problema identificato viene fornito:

- **Descrizione**: Una breve spiegazione del problema riscontrato
- **Commit di risoluzione**: Link diretto al commit GitHub che contiene la correzione del bug

---

#### 2.10 - 03/02/2026

##### Problemi noti
- Corretta l'impostazione del log di invio per singola fattura in fase di invio email da azioni di gruppo
https://github.com/devcode-it/openstamanager/commit/eefbc6f7c

- Corretta iva calcolata su ritenuta in tabella riepilogo iva della stampa fattura
https://github.com/devcode-it/openstamanager/commit/d0559c2fd

- Corretta stampa fattura con dicitura iva che veniva impostata a centro pagina
https://github.com/devcode-it/openstamanager/commit/a7cce938d

- Corretta navigabilità nella configurazione del database
https://github.com/devcode-it/openstamanager/commit/b46847702

- Corretti gli avvisi in fase di login utente
https://github.com/devcode-it/openstamanager/commit/e36a1bc1a

- Corretta visualizzazione cancel button default per swal
https://github.com/devcode-it/openstamanager/commit/748ebdb26

- Corretta stampa sede in riepilogo interventi
https://github.com/devcode-it/openstamanager/commit/5937b77f3

- Corretta data per calcolo progressivo contratto in duplicazione contratto anno precedente
https://github.com/devcode-it/openstamanager/commit/11e3ff897

- Corretto utilizzo di insert ignore per evitare errore js in fase di modifica permessi utenti
https://github.com/devcode-it/openstamanager/commit/f575c0176

- Corretta correzione salvataggio firma
https://github.com/devcode-it/openstamanager/commit/13ee85506

- Corretta impostazione banca di accredito predefinita in fattura in base a impostazione specificata in anagrafica cliente
https://github.com/devcode-it/openstamanager/commit/5b8241eab

- Corretto link alla documentazione
https://github.com/devcode-it/openstamanager/commit/53745ba7c

- Corretta stampa registro iva acquisti per fatture a cavallo dell'anno
https://github.com/devcode-it/openstamanager/commit/b4eaab860

- Corretto aggiornamento quantità da importazione articoli dove non è specificata la data
https://github.com/devcode-it/openstamanager/commit/e683e563d

- Corretta l'impostazione del giorno scadenza per regole pagamenti
https://github.com/devcode-it/openstamanager/commit/29413472a

- Corretto percorso base_path
https://github.com/devcode-it/openstamanager/commit/b69e64cd3

- Corretta miglioria controllo su quantità in creazione documento, che viene ora abilitata in base all'impostazione Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita
https://github.com/devcode-it/openstamanager/commit/d32bd1ba7

- Corretta esclusione controllo sulle quantità per i documenti con direzione uscita
https://github.com/devcode-it/openstamanager/commit/b2f29ee34

- Corretto raggruppamento righe per ordine in fase di selezione ordine da importare nei documenti
https://github.com/devcode-it/openstamanager/commit/b46847702

- Corretta selezione banca addebito nel caso di pagamento tramite Ri.Ba.
https://github.com/devcode-it/openstamanager/commit/f04d59585

- Corretto calcolo quantità disponibile articoli in fase di generazione ddt da ordine
https://github.com/devcode-it/openstamanager/commit/86a92716f

- Corretto avviso per uscita da fattura di acquisto
https://github.com/devcode-it/openstamanager/commit/b2b39f85f

- Corretto calcolo arrotondamento in importazione fattura di acquisto se indicato in maniera errata nell'xml
https://github.com/devcode-it/openstamanager/commit/a4aa0a3d0

- Corretta importazione fatture con tipo documento e data registrazione mancanti in xml
https://github.com/devcode-it/openstamanager/commit/7402d4371

- Corretto refuso nome documento in creazione ddt da preventivo
https://github.com/devcode-it/openstamanager/commit/b6628b551

- Corretto script creazione immagine docker
https://github.com/devcode-it/openstamanager/commit/2642c186f

- Corretta prevenzione sql injection
https://github.com/devcode-it/openstamanager/commit/bae00c059

#### 2.9.8 - 23/12/2025

##### Problemi noti
- Corretta la gestione dei barcode collegati ad articoli eliminati
https://github.com/devcode-it/openstamanager/commit/555400afb

- Corretto l'avviso sulle risorse in scadenza
https://github.com/devcode-it/openstamanager/commit/837b2f4d1

- Corretta la generazione di scadenze relative a fatture con marca da bollo
https://github.com/devcode-it/openstamanager/commit/ee1471442

- Corretta la visualizzazione della ragione sociale del fornitore nella sezione Ultimi 20 prezzi di acquisto in Articoli
https://github.com/devcode-it/openstamanager/commit/5a460ee90

- Corretta la stampa inventario
https://github.com/devcode-it/openstamanager/commit/c228d5ea2

- Corretta la stampa dei contratti
https://github.com/devcode-it/openstamanager/commit/9cbd3040d

- Corretta l'impostazione del pagamento predefinito dell'anagrafica in aggiunta di una nuova attività
https://github.com/devcode-it/openstamanager/commit/79ae16856

- Corretta l'aggiunta di marca e modello in fase di creazione di un impianto
https://github.com/devcode-it/openstamanager/commit/5c9f67fed

- Corretta l'aggiunta di una prima nota dall'edit prima nota
https://github.com/devcode-it/openstamanager/commit/12a42dd99

- Corretta la stampa della provvigione agente
https://github.com/devcode-it/openstamanager/commit/d69585908

- Corretto il salvataggio della configurazione Oauth2
https://github.com/devcode-it/openstamanager/commit/ea1ee603e

- Corretto l'avviso
https://github.com/devcode-it/openstamanager/commit/2d09c62f3

- Corretta l'associazione di permessi ai gruppi di utenti
https://github.com/devcode-it/openstamanager/commit/d91b69aba

- Corretto l'invio mail da terminale durante l'esecuzione del cron
https://github.com/devcode-it/openstamanager/commit/3f71d36c9

- Corretta la vista di Accesso con OAuth
https://github.com/devcode-it/openstamanager/commit/dbc25baf6

- Corretta l'aggiunta di una categoria
https://github.com/devcode-it/openstamanager/commit/33650bafb

- Corretta la visualizzazione di articoli collegati a una marca
https://github.com/devcode-it/openstamanager/commit/073dbac9b

- Corretta l'aggiunta di una categoria file
https://github.com/devcode-it/openstamanager/commit/80358e70e

- Corretto il calcolo della quantità evasa delle righe preventivo per problema in fase di eliminazione fattura creata da preventivo con quantità eccedenti la disponibilità a magazzino
https://github.com/devcode-it/openstamanager/commit/3ae4ff34d

- Corretta l'ordinamento dei movimenti degli articoli da plugin per data movimento
https://github.com/devcode-it/openstamanager/commit/4bfb84f68

- Corretta la gestione dei seriali in note di credito collegate a fatture
https://github.com/devcode-it/openstamanager/commit/b1636ce0f

- Corretto il redirect al modulo fatture da plugin serial, nel caso di note di credito
https://github.com/devcode-it/openstamanager/commit/2aba9bfc0

- Corretta la gestione dei seriali rientrati a magazzino tramite nota di credito
https://github.com/devcode-it/openstamanager/commit/5deab378f

- Corretta la selezione del tipo documento in fase di generazione nota di credito
https://github.com/devcode-it/openstamanager/commit/0a32be473

#### 2.9.7 - 09/12/2025

##### Problemi noti
- Corretti riferimenti in registrazione contabile da bulk
https://github.com/devcode-it/openstamanager/commit/d0383136b

- Corretta visualizzazione riferimenti import FE
https://github.com/devcode-it/openstamanager/commit/af2b27187

- Corretta aggiunta ordini in attività
https://github.com/devcode-it/openstamanager/commit/5b851cc4a

- Corretto cambio stato contratti
https://github.com/devcode-it/openstamanager/commit/e4724ccc1

- Corretto controllo su iban
https://github.com/devcode-it/openstamanager/commit/b0da8adda

- Corretto caricamento allegati
https://github.com/devcode-it/openstamanager/commit/2b98d3e0c

- Corretta sottocategoria in vista contratti
https://github.com/devcode-it/openstamanager/commit/937ef080a

- Corretto salvataggio allegati
https://github.com/devcode-it/openstamanager/commit/76187dcd4

- Corretta rivalsa in FE
https://github.com/devcode-it/openstamanager/commit/589901c52

- Corretta associazione sottocategoria a impianto
https://github.com/devcode-it/openstamanager/commit/439332f93

- Corretto Importazione fatture da zip
https://github.com/devcode-it/openstamanager/commit/6d69f9fa8

- Corretto avviso tasto importa in sequenza
https://github.com/devcode-it/openstamanager/commit/c4bcaedd2

- Corretto salvataggio firma da app
https://github.com/devcode-it/openstamanager/commit/90cc7a145

- Corretti allegati fatture elettroniche
https://github.com/devcode-it/openstamanager/commit/0ef3a71f5

#### 2.9.6 - 26/11/2025

##### Problemi noti
- Corretto log invio fatture da bulk
https://github.com/devcode-it/openstamanager/commit/9f13a965c

- Corretto calcolo spazio occupato
https://github.com/devcode-it/openstamanager/commit/ca47fb7f1

- Corretta lettura informazioni su services
https://github.com/devcode-it/openstamanager/commit/59e1596b1

- Corretta stampa inventario con filtri
https://github.com/devcode-it/openstamanager/commit/9276bb607

- Corretta visualizzazione highlight righe documenti alla modifica
https://github.com/devcode-it/openstamanager/commit/197ce85b2

- Corretta selezione seriali
https://github.com/devcode-it/openstamanager/commit/02574b057

- Corretta gestione assenza file modules.json e views.json
https://github.com/devcode-it/openstamanager/commit/19483b4b5

- Corretta aggiunta record multilingua mancanti a database
https://github.com/devcode-it/openstamanager/commit/499e8e065

- Corretta lettura cache Informazioni su services
https://github.com/devcode-it/openstamanager/commit/ccf3c4d80

- Corretta visualizzazione servizi
https://github.com/devcode-it/openstamanager/commit/261cc2e5a

- Corretta segnalazione compatibilità
https://github.com/devcode-it/openstamanager/commit/a4adfcab6

- Corretto background red widget contratti in scadenza
https://github.com/devcode-it/openstamanager/commit/f6bfdcf14

- Corretta aggiunta stato ddt
https://github.com/devcode-it/openstamanager/commit/de575f976

- Corretto salvataggio categorie contratti
https://github.com/devcode-it/openstamanager/commit/2913246ed

- Corretto title stampa bilancio
https://github.com/devcode-it/openstamanager/commit/9730a0a38

#### 2.9.5 - 12/11/2025

##### Problemi noti
- Corretta visualizzazione importo stampa preventivo
https://github.com/devcode-it/openstamanager/commit/a038f2839

- Corretta duplicazione fattura con marca da bollo
https://github.com/devcode-it/openstamanager/commit/e5aa8f796

- Corretto export FE dati aggiuntivi
https://github.com/devcode-it/openstamanager/commit/61218005e

- Corretta visualizzazione pagamento in stampa preventivo
https://github.com/devcode-it/openstamanager/commit/c17ee6968

- Corretta query vista modulo Log eventi
https://github.com/devcode-it/openstamanager/commit/3d658d575

- Corretta sincronizzazione su app degli articoli eliminati
https://github.com/devcode-it/openstamanager/commit/834f9378f

- Corretta importazione barcode articoli
https://github.com/devcode-it/openstamanager/commit/352c9c7af

- Corretta generazione autofattura per reverse charge
https://github.com/devcode-it/openstamanager/commit/ab87cf4bc

- Corretta maggiorazione nei template
https://github.com/devcode-it/openstamanager/commit/c062b47f7

- Corrette API allegati
https://github.com/devcode-it/openstamanager/commit/d17a3951f

- Corretto elenco tipi di spedizione in ordini
https://github.com/devcode-it/openstamanager/commit/22fa8a8ed

- Corrette anomalie ammortamenti/cespiti
https://github.com/devcode-it/openstamanager/commit/d58b8cce2

- Corretto carattere non supportato XML
https://github.com/devcode-it/openstamanager/commit/51cfe9606

- Corretto avviso per valore richiesto al login
https://github.com/devcode-it/openstamanager/commit/a03625757

- Corretta impostazione idconto righe
https://github.com/devcode-it/openstamanager/commit/3080be327

#### 2.9.4 - 28/10/2025

##### Problemi noti
- Corretto salvataggio inline nuova scadenza non funzionante
https://github.com/devcode-it/openstamanager/commit/a038f2839

- Corretto allineamento larghezza input field e icon button nella pagina reset password e login
https://github.com/devcode-it/openstamanager/commit/9346cc4ee

- Corretta generazione stampe contabili definitive
https://github.com/devcode-it/openstamanager/commit/96b9eac0b

- Corretto ordinamento viste non rispettato
https://github.com/devcode-it/openstamanager/commit/7df806723

- Corretta gestione valori a null database per evitare errori
https://github.com/devcode-it/openstamanager/commit/accd2dfb4

- Rimosso BountySource dal README (servizio non più esistente)
https://github.com/devcode-it/openstamanager/commit/39d2fadc2

- Corretta visualizzazione barra dei plugin non corretta
https://github.com/devcode-it/openstamanager/commit/6b3584d3c

- Corretto capitale sociale opzionale permettendo salvataggio a null
https://github.com/devcode-it/openstamanager/commit/148aacd60

- Corretta formattazione colonne formato data
https://github.com/devcode-it/openstamanager/commit/c9ed47b79

- Corretto svuota cache hooks non funzionante
https://github.com/devcode-it/openstamanager/commit/5dab6c4d6

- Corretta generazione query risoluzione problemi database
https://github.com/devcode-it/openstamanager/commit/e98d4b398

- Corretto inserimento ordine in attività con errori
https://github.com/devcode-it/openstamanager/commit/3c0af7b27

- Corretta applicazione filtri segmenti non funzionante
https://github.com/devcode-it/openstamanager/commit/07ef6a766

#### 2.9.3 - 14/10/2025

##### Problemi noti
- Corretta la gestione dell'invio via mail per i token OTP
https://github.com/devcode-it/openstamanager/commit/6f59b7c42

- Corrette le dimensioni del QR Code nelle stampe
https://github.com/devcode-it/openstamanager/commit/4aa42299b

- Corretti i riferimenti alle email inviate nei vari moduli per una visualizzazione accurata
https://github.com/devcode-it/openstamanager/commit/49d68ea6b

- Corretta verifica esigibilità IVA non funzionante correttamente
https://github.com/devcode-it/openstamanager/commit/c72c4386d

- Corretta gestione barcode non corretta in alcuni moduli
https://github.com/devcode-it/openstamanager/commit/cb6269721

- Corretti filtri fantasma che apparivano nelle tabelle senza essere configurati
https://github.com/devcode-it/openstamanager/commit/8d30cec74

- Corretto avviso fatture con ricevuta di scarto non visualizzato correttamente
https://github.com/devcode-it/openstamanager/commit/09f261b6c

- Corretta ricerca articoli nei documenti che non restituiva risultati corretti
https://github.com/devcode-it/openstamanager/commit/fbeea9fd9

- Corrette query installazione che causavano errori durante l'aggiornamento
https://github.com/devcode-it/openstamanager/commit/230d95e30

- Corretto pannello servizi che non caricava correttamente le informazioni
https://github.com/devcode-it/openstamanager/commit/0dc84866c

- Corretto bug che impediva il cambio password tramite l'interfaccia di modifica utente
https://github.com/devcode-it/openstamanager/commit/aa05fe798

- Corretto ridimensionamento viste non corretto su schermi diversi
https://github.com/devcode-it/openstamanager/commit/2adb15e56

- Corretta ricerca piano dei conti non funzionante
https://github.com/devcode-it/openstamanager/commit/898cf38e1

- Corretta notifica aggiornamento non visualizzata correttamente
https://github.com/devcode-it/openstamanager/commit/1ab6734fc

- Corretta impostazione tipo anagrafica in import FE non corretta
https://github.com/devcode-it/openstamanager/commit/9add9c76e

- Corretta selezione modulo iniziale che non rispettava le impostazioni utente
https://github.com/devcode-it/openstamanager/commit/5ff39df9b

- Corretta ricerca articoli che restituiva risultati non pertinenti
https://github.com/devcode-it/openstamanager/commit/637b82345

- Corretto invio sollecito scadenze selezionate non funzionante
https://github.com/devcode-it/openstamanager/commit/5a77a513a

- Corretto ordinamento tabelle che non rispettava i criteri impostati
https://github.com/devcode-it/openstamanager/commit/06efa924a

- Corretta ricerca e visualizzazione fatture da importare non funzionante
https://github.com/devcode-it/openstamanager/commit/d37cb5ef9

- Corretta esportazione RIBA con errori nei dati esportati
https://github.com/devcode-it/openstamanager/commit/6d82c05ff

- Corretto calcolo arrotondamento per fatture elettroniche non corretto
https://github.com/devcode-it/openstamanager/commit/ecdd0fd9d

- Corretto link stampa su menu a tendina in invio email non funzionante
https://github.com/devcode-it/openstamanager/commit/8f9572565

- Corretta esecuzione cron da riga di comando con redirectHTTPS attivo che causava errori
https://github.com/devcode-it/openstamanager/commit/5ffe7fe68

- Corretto import righe fatture elettroniche con quantità non definita ma prezzo unitario definito
https://github.com/devcode-it/openstamanager/commit/4e003a850

- Corretto salvataggio seriali vendita al banco non funzionante
https://github.com/devcode-it/openstamanager/commit/1ff92853b

- Corretti permessi per modulo Accesso con Token/OTP mancanti
https://github.com/devcode-it/openstamanager/commit/f73b53a75

- Corretta gestione XML righe senza quantità che causava errori di importazione
https://github.com/devcode-it/openstamanager/commit/21f91c1c9

- Corretta aggiunta pagamenti che non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/02bd3db69

#### 2.9.2 - 25/09/2025

##### Problemi noti
- Rimozione record orfani dalle tabelle del database
https://github.com/devcode-it/openstamanager/commit/f115707f8

- Semplificazione query modulo Articoli per lista barcode per migliorare le prestazioni
https://github.com/devcode-it/openstamanager/commit/37637c994

- Verifica esigibilità IVA non funzionante correttamente
https://github.com/devcode-it/openstamanager/commit/28ccbad0f

- Gestione barcode non corretta in alcuni moduli
https://github.com/devcode-it/openstamanager/commit/2dd184f43

- Filtri fantasma che apparivano nelle tabelle senza essere configurati
https://github.com/devcode-it/openstamanager/commit/33e2325af

- Avviso fatture con ricevuta di scarto non visualizzato correttamente
https://github.com/devcode-it/openstamanager/commit/5ae1e370e

- Impostazione tecnico in aggiunta intervento non funzionante
https://github.com/devcode-it/openstamanager/commit/ffa59fba1

- Ricerca articoli nei documenti non restituiva risultati corretti
https://github.com/devcode-it/openstamanager/commit/a862e29c4

- Query installazione causavano errori durante l'aggiornamento
https://github.com/devcode-it/openstamanager/commit/21c331268

- Inizializzazione tabelle non corretta in fase di installazione
https://github.com/devcode-it/openstamanager/commit/5220c2693

- Pannello servizi non caricava correttamente le informazioni
https://github.com/devcode-it/openstamanager/commit/7a2fca08f

- Bug che impediva il cambio password tramite l'interfaccia di modifica utente
https://github.com/devcode-it/openstamanager/commit/b9135a567

- Caricamento moduli lento o non funzionante
https://github.com/devcode-it/openstamanager/commit/4649896d1

- Correzione sincronizzazione barcode su app mobile
https://github.com/devcode-it/openstamanager/commit/2fd1812bd

- Ridimensionamento viste non corretto su schermi diversi
https://github.com/devcode-it/openstamanager/commit/bff93f43b

- Ricerca piano dei conti non funzionante
https://github.com/devcode-it/openstamanager/commit/e34516254

- Notifica aggiornamento non visualizzata correttamente
https://github.com/devcode-it/openstamanager/commit/9f34e6629

- Ricerca articolo con risultati non corretti
https://github.com/devcode-it/openstamanager/commit/5d4abfa7d

- Selezione tipo anagrafica non funzionante in alcuni contesti
https://github.com/devcode-it/openstamanager/commit/10571e978

- Impostazione tipo anagrafica in import FE non corretta
https://github.com/devcode-it/openstamanager/commit/7e81628fa

- Selezione modulo iniziale non rispettava le impostazioni utente
https://github.com/devcode-it/openstamanager/commit/fcdd9adcb

- Ricerca articoli restituiva risultati non pertinenti
https://github.com/devcode-it/openstamanager/commit/c79c91d78

- Invio sollecito scadenze selezionate non funzionante
https://github.com/devcode-it/openstamanager/commit/dee33f9a7

- Espansione sottomenu non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/4221c3fa8

- Espansione sottomenu tabelle causava errori di visualizzazione
https://github.com/devcode-it/openstamanager/commit/31d69dd46

- Espansione sottomenu tabelle aggiuntiva con problemi
https://github.com/devcode-it/openstamanager/commit/3ef95571a

- Ordinamento tabelle non rispettava i criteri impostati
https://github.com/devcode-it/openstamanager/commit/6c5502b95

- Ricerca e visualizzazione fatture da importare non funzionante
https://github.com/devcode-it/openstamanager/commit/a9bb4d0d9

- Esportazione RIBA con errori nei dati esportati
https://github.com/devcode-it/openstamanager/commit/4ab5f97d4

- Calcolo arrotondamento per fatture elettroniche non corretto
https://github.com/devcode-it/openstamanager/commit/68430b638

- Link stampa su menu a tendina in invio email non funzionante
https://github.com/devcode-it/openstamanager/commit/ddce400a8

- Esecuzione cron da riga di comando con redirectHTTPS attivo causava errori
https://github.com/devcode-it/openstamanager/commit/113edf85f

- Import righe fatture elettroniche con quantità non definita ma prezzo unitario definito
https://github.com/devcode-it/openstamanager/commit/75d4bab68

- Salvataggio seriali vendita al banco non funzionante
https://github.com/devcode-it/openstamanager/commit/f024ba0e5

- Aggiunta permessi per modulo Accesso con Token/OTP mancanti
https://github.com/devcode-it/openstamanager/commit/3c0546828

- Gestione XML righe senza quantità causava errori di importazione
https://github.com/devcode-it/openstamanager/commit/dee094b56

- Aggiunta pagamenti non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/21aff8e54

#### 2.9.1 - 08/09/2025

##### Problemi noti
- Margine navbar non corretto su dispositivi mobili
https://github.com/devcode-it/openstamanager/commit/b29258295

- Sidebar plugin non visualizzata correttamente
https://github.com/devcode-it/openstamanager/commit/f6a24921e

- Espansione sottomenu non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/095cbad9a

- Tasti modal non funzionanti in alcune finestre di dialogo
https://github.com/devcode-it/openstamanager/commit/510a8b1f2

- Inizializzazione stampa barcode causava errori
https://github.com/devcode-it/openstamanager/commit/f13db1cf8

- Import righe con quantità a 0 non gestito correttamente
https://github.com/devcode-it/openstamanager/commit/96f98c5d4

- Impostazione stato bozza nei DDT non funzionante
https://github.com/devcode-it/openstamanager/commit/faa8d9155

- Avviso requisiti cron non visualizzato correttamente
https://github.com/devcode-it/openstamanager/commit/50ab490ab

- Avviso cron generico non mostrato quando necessario
https://github.com/devcode-it/openstamanager/commit/9007c903d

- Ordinamento promemoria da pianificare in dashboard non corretto
https://github.com/devcode-it/openstamanager/commit/a8b124ad1

- Widget Articoli in esaurimento mostrava dati non corretti
https://github.com/devcode-it/openstamanager/commit/8ab7ecbcd

- Avviso cron non configurato non appariva quando necessario
https://github.com/devcode-it/openstamanager/commit/d36e8b1c2

- Corretta evidenziazione risultati ricerca non funzionava
https://github.com/devcode-it/openstamanager/commit/192543a00

- Testo caricamento ricerca non visualizzato correttamente
https://github.com/devcode-it/openstamanager/commit/fe0bb726a

- Ricerca impianti in plugin da attività restituiva risultati errati
https://github.com/devcode-it/openstamanager/commit/f23cd3c02

- Correzione campo data fine in ricorrenza non salvava correttamente
https://github.com/devcode-it/openstamanager/commit/30b83bb41

- Rimozione link articolo in select causava errori
https://github.com/devcode-it/openstamanager/commit/6e9752e67

- Sidebar non si comportava correttamente su dispositivi mobili
https://github.com/devcode-it/openstamanager/commit/d6bad2c37

- Allineamento valori tabelle non corretto
https://github.com/devcode-it/openstamanager/commit/26a1aa769

- Esecuzione cron causava errori in alcune configurazioni
https://github.com/devcode-it/openstamanager/commit/0f81efc2f

- Formattazione vista gestione task non corretta
https://github.com/devcode-it/openstamanager/commit/b0368fe5d

- Correzione sede per movimenti da app mobile non funzionante
https://github.com/devcode-it/openstamanager/commit/927ae8db2

- Query vista Articoli causava lentezza nel caricamento
https://github.com/devcode-it/openstamanager/commit/cc620daf0

- Ordinamento tabelle non rispettava i criteri impostati
https://github.com/devcode-it/openstamanager/commit/df98d666e

- Impostazione ricorrenze in creazione nuova attività non funzionava
https://github.com/devcode-it/openstamanager/commit/c4b25f58e

- Impostazione tecnici assegnati in aggiunta intervento non corretta
https://github.com/devcode-it/openstamanager/commit/338fed477

- Allineamento grafico modulo interventi non corretto
https://github.com/devcode-it/openstamanager/commit/870078d97

- Ricerca aggiornamenti non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/ce6871e54

- Caricamento mappa header interventi causava errori
https://github.com/devcode-it/openstamanager/commit/8fa07c769

- Notifiche non venivano visualizzate correttamente
https://github.com/devcode-it/openstamanager/commit/f3455c24d

- Note interne non venivano salvate correttamente
https://github.com/devcode-it/openstamanager/commit/479b6d4b6

- Plugin Impianti in attività non caricava i dati
https://github.com/devcode-it/openstamanager/commit/1a406b43f

- Ricerca globale restituiva risultati non pertinenti
https://github.com/devcode-it/openstamanager/commit/74f4a0bcf

- Selezione anagrafiche modulo mappa non funzionava
https://github.com/devcode-it/openstamanager/commit/aca6cf0f7

- Ricerca raggio modulo Mappa non restituiva risultati corretti
https://github.com/devcode-it/openstamanager/commit/c619bbfc7

- Tasto filtri in mappa non funzionante
https://github.com/devcode-it/openstamanager/commit/b37021e8a

- Visualizzazione icona plugin solo se disponibili non corretta
https://github.com/devcode-it/openstamanager/commit/09e5a6701

- Espansione sottomenu aggiuntiva non funzionava
https://github.com/devcode-it/openstamanager/commit/70b6e74c1

- Indicazione ultima esecuzione task non aggiornata
https://github.com/devcode-it/openstamanager/commit/e65651954

- Query di installazione causavano errori
https://github.com/devcode-it/openstamanager/commit/3e1b54c4f

- Caricamento altre operazioni stato dei servizi causava errori
https://github.com/devcode-it/openstamanager/commit/47c9bb18f

- Visualizzazione stato servizi non corretta
https://github.com/devcode-it/openstamanager/commit/fa147fac4

- Hook servizi non venivano eseguiti correttamente
https://github.com/devcode-it/openstamanager/commit/fc880abd3

- Gestione stati dei DDT non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/8a1d208b0

- Modifica icone moduli in Tabelle non salvava le modifiche
https://github.com/devcode-it/openstamanager/commit/d19ef02d0

- Spostamento modulo Categorie contratti causava errori
https://github.com/devcode-it/openstamanager/commit/903dbd813

- Visualizzazione categorie allegati non corretta
https://github.com/devcode-it/openstamanager/commit/d5cfc7f10

- Ricerca cache hooks non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/92934aafb

- Modulo Marche non caricava correttamente i dati
https://github.com/devcode-it/openstamanager/commit/6bed1697c

- Aggiornamento sottocategoria non salvava le modifiche
https://github.com/devcode-it/openstamanager/commit/4241e778b

- Correzione grafica hooks per migliorare la visualizzazione
https://github.com/devcode-it/openstamanager/commit/3d3eda17f

- Impostazione prezzo di acquisto fatture non funzionava
https://github.com/devcode-it/openstamanager/commit/37bdef29d

- Impostazione causale movimento non corretta
https://github.com/devcode-it/openstamanager/commit/ce3ea86a8

- Elenco tabelle non mostrava tutte le tabelle disponibili
https://github.com/devcode-it/openstamanager/commit/9061678fb

#### 2.9-beta - 08/08/2025

##### Problemi noti
- Select in aggiunta adattatore non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/e129c5438

- Vista modulo Marche non caricava i dati correttamente
https://github.com/devcode-it/openstamanager/commit/633c01712

- Navigazione pagine datatables non funzionava
https://github.com/devcode-it/openstamanager/commit/0dda51a66

- Tasti in modal footer non erano cliccabili
https://github.com/devcode-it/openstamanager/commit/487658bcd

- Aggiunta segmenti causava errori di validazione
https://github.com/devcode-it/openstamanager/commit/d0f15adbb

- Impostazione gruppo utenti non veniva salvata
https://github.com/devcode-it/openstamanager/commit/59452a8b5

- Cambio banca da bulk scadenzario non funzionava
https://github.com/devcode-it/openstamanager/commit/074cd5a2f

- Fix minore per miglioramenti generali
https://github.com/devcode-it/openstamanager/commit/a4fb9215b

- Preselezione gruppo utenti in creazione utente non corretta
https://github.com/devcode-it/openstamanager/commit/ed4b5dd75

- Aggiunta campi personalizzati causava errori
https://github.com/devcode-it/openstamanager/commit/f77362ddd

- Fix minore aggiuntivo per stabilità
https://github.com/devcode-it/openstamanager/commit/07b73d0bd

- Aggiunta categorie non salvava correttamente
https://github.com/devcode-it/openstamanager/commit/860f0e76c

- Ordinamento tabelle non rispettava i criteri impostati
https://github.com/devcode-it/openstamanager/commit/f31b67285

- Eliminazione periodo temporale in statistiche (#1692)
https://github.com/devcode-it/openstamanager/commit/3172f2aca

- Miglioria DDT (#1031)
https://github.com/devcode-it/openstamanager/commit/d6d4dc808

- Aggiunta seriali non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/88b363723

- Avviso aggiunta seriali non veniva mostrato
https://github.com/devcode-it/openstamanager/commit/4afe82750

- Campi vista DDT non visualizzati correttamente
https://github.com/devcode-it/openstamanager/commit/d5449e7ee

- Calcolo bollo da widget rate contrattuali non corretto
https://github.com/devcode-it/openstamanager/commit/9aafb56e5

- Invio sollecito scadenza non funzionava
https://github.com/devcode-it/openstamanager/commit/c15523444

- Invio mail con allegati causava errori
https://github.com/devcode-it/openstamanager/commit/36c50d210

- Invio PEC non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/985faa804

- Visualizzazione modulo scadenzario non corretta
https://github.com/devcode-it/openstamanager/commit/bab9c9772

- Aggiunta prima nota da edit prima nota non funzionava
https://github.com/devcode-it/openstamanager/commit/0ff25b814

- Stampa DDT con sede di partenza e destinazione diverse non corretta
https://github.com/devcode-it/openstamanager/commit/0faa667a5

- Inizializzazione barcode in template di stampa causava errori
https://github.com/devcode-it/openstamanager/commit/e8e5241fa

- Link al modulo Mansioni referenti non funzionante
https://github.com/devcode-it/openstamanager/commit/226966276

- Gestione note di credito nell'esportazione bonifici XML, venivano sommate invece di sottratte
https://github.com/devcode-it/openstamanager/commit/e221a4736

- Correzione anteprima importi su evasione documenti non corretta
https://github.com/devcode-it/openstamanager/commit/7430a9795

- Creazione ordine non funzionava in alcuni casi
https://github.com/devcode-it/openstamanager/commit/53a9ea6bd

#### 2.8.3 - 30/07/2025

##### Problemi noti
- Query installazione causavano errori durante l'aggiornamento
https://github.com/devcode-it/openstamanager/commit/d3d33c283

- Ricalcolo IVA su sconto righe non corretto
https://github.com/devcode-it/openstamanager/commit/43a9e9bae

- Associazione aliquota IVA import FE vendita da zip non funzionava
https://github.com/devcode-it/openstamanager/commit/2132c23cc

- Select per modifica IVA da bulk righe documenti non funzionante
https://github.com/devcode-it/openstamanager/commit/550339919

- Modifica IVA righe da bulk non salvava le modifiche
https://github.com/devcode-it/openstamanager/commit/c6225b807

- Creazione autofattura causava errori
https://github.com/devcode-it/openstamanager/commit/fb50c0482

- Query viste moduli non corrette
https://github.com/devcode-it/openstamanager/commit/5f5c976b0

- Query di installazione multiple con errori
https://github.com/devcode-it/openstamanager/commit/da5e4cf3e

- Query di installazione aggiuntive non corrette
https://github.com/devcode-it/openstamanager/commit/cda02f072

- Importazione fatture da zip non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/802611044

- Formattazione query non corretta
https://github.com/devcode-it/openstamanager/commit/80d6cf3fd

- Correzione riferimento per colonna email inviata nei moduli
https://github.com/devcode-it/openstamanager/commit/6fda7233a

- Gestione sede per distinta base non corretta
https://github.com/devcode-it/openstamanager/commit/5b8f2f109

- Inserimento ordini causava errori di validazione
https://github.com/devcode-it/openstamanager/commit/09f67018a

- Aggiunta sottocategoria non salvava correttamente
https://github.com/devcode-it/openstamanager/commit/6bd69b1fc

- Ricerca datatables non funzionava con alcuni filtri
https://github.com/devcode-it/openstamanager/commit/9aa68f8b3

- Visualizzazione mappa non caricava correttamente
https://github.com/devcode-it/openstamanager/commit/b08ab15f0

- Visualizzazione errata valori tabelle con formattazione non corretta
https://github.com/devcode-it/openstamanager/commit/803c7f112

- Query installazione aggiuntive per compatibilità
https://github.com/devcode-it/openstamanager/commit/2860cba4a

- Query installazione ulteriori per stabilità
https://github.com/devcode-it/openstamanager/commit/532ad0b77

- Creazione intervento non funzionava in alcuni casi
https://github.com/devcode-it/openstamanager/commit/7f5ce275f

- Avviso aggiunta registrazione contabile da bulk non mostrato
https://github.com/devcode-it/openstamanager/commit/6457cf520

- Aggiunta registrazione da bulk non funzionava
https://github.com/devcode-it/openstamanager/commit/05cc9e934

- Problema abilitazione permessi utenti non corretta
https://github.com/devcode-it/openstamanager/commit/aaedbbb3f

- Inclusione file per stampa barcode con EAN13 causava errori
https://github.com/devcode-it/openstamanager/commit/a9fce1160

- Refuso nel codice corretto
https://github.com/devcode-it/openstamanager/commit/ecb6fb527

- Migrazione categorie impianto non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/045ed0d40

- Risolta inclusione di pdfjs per anteprime PDF
https://github.com/devcode-it/openstamanager/commit/c5b297c29

- Query migrazione categorie impianto non corrette
https://github.com/devcode-it/openstamanager/commit/a66d7dc00

- Background verde totali fatture non visualizzato correttamente
https://github.com/devcode-it/openstamanager/commit/5ab204b96

- Aggiunta categoria e marca da modale su edit non funzionava
https://github.com/devcode-it/openstamanager/commit/985d5a123

- Modulo backup non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/5abf8c2c3

- Importazione FE da zip causava errori
https://github.com/devcode-it/openstamanager/commit/cc70d6a25

- Miglioria form selezione riferimenti per migliorare usabilità
https://github.com/devcode-it/openstamanager/commit/a96e31ea5

- Visualizzazione link non corretta in alcuni moduli
https://github.com/devcode-it/openstamanager/commit/4d28549d3

- Correzione aggiunta righe descrittive in creazione ordine fornitore da ordine cliente
https://github.com/devcode-it/openstamanager/commit/4c28e9d7c

- Visualizzazione modulo backup non corretta
https://github.com/devcode-it/openstamanager/commit/f03d1abd3

- Campo duplicato selezione conto causava errori
https://github.com/devcode-it/openstamanager/commit/d28a1bfcf

- Query installazione finale per completezza
https://github.com/devcode-it/openstamanager/commit/36b42d3d0

- Allegati senza categoria non venivano gestiti correttamente
https://github.com/devcode-it/openstamanager/commit/9b6c2c6e4

#### 2.8.2 - 08/07/2025

##### Problemi noti
- Corretta esclusione agenti da plugin Provvigioni
https://github.com/devcode-it/openstamanager/commit/73fa91011

- Pulizia e aggiunta foreign key su provvigioni per articolo
https://github.com/devcode-it/openstamanager/commit/07a4dbcaf

- Spostamento query per ottimizzazione
https://github.com/devcode-it/openstamanager/commit/f19598cad

- Correzione impostazione multiple con query
https://github.com/devcode-it/openstamanager/commit/f49927fbb

- Stampa fattura con sconto finale percentuale non corretta
https://github.com/devcode-it/openstamanager/commit/a90411ca7

- Stampa fattura con sconto in fattura non corretta
https://github.com/devcode-it/openstamanager/commit/7be63d2ee

- Importazione fatture di vendita zip non funzionava
https://github.com/devcode-it/openstamanager/commit/60f13e57b

- Correzione problema di aggiornamento data scadenza cache
https://github.com/devcode-it/openstamanager/commit/254a00c9c

- Associazione conto righe da importFE non corretta
https://github.com/devcode-it/openstamanager/commit/25c4e14ab

- Search datatables non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/67cea2900

- Errore calcola percorso da mobile (#1408)
https://github.com/devcode-it/openstamanager/commit/d6076e06e

- Associazione categoria allegati non funzionava
https://github.com/devcode-it/openstamanager/commit/aba1cded3

- Redirect plugin ricevute FE non corretto
https://github.com/devcode-it/openstamanager/commit/6a30161e6

- Tooltip al mouseover su icona non visualizzato
https://github.com/devcode-it/openstamanager/commit/280c64185

- Ordinamento tabelle non rispettava i criteri
https://github.com/devcode-it/openstamanager/commit/0580f8883

- Ricerca datatables campi vuoti tramite '=' non funzionava
https://github.com/devcode-it/openstamanager/commit/a680cc031

- Template DDT senza prezzi non corretto
https://github.com/devcode-it/openstamanager/commit/451b2f2d7

- Selezione prezzo di acquisto in aggiunta articolo da importFE
https://github.com/devcode-it/openstamanager/commit/dcaf0607b

- Sede in creazione fattura non impostata correttamente
https://github.com/devcode-it/openstamanager/commit/cb7812da1

- Ripristino compilazione automatica codice e descrizione articolo in importFE
https://github.com/devcode-it/openstamanager/commit/31b7a7c8b

- Compilazione conto e IVA in importFE non corretta
https://github.com/devcode-it/openstamanager/commit/79f621d32

- Ripristino tasto add articolo in importFE
https://github.com/devcode-it/openstamanager/commit/98dd1d71b

- Migliora l'installazione inserendo il drop delle tabelle problematiche in try-catch
https://github.com/devcode-it/openstamanager/commit/daad3c8db

- Retrofix per installazione
https://github.com/devcode-it/openstamanager/commit/f2dfffc89

- Importazione sequenziale fatture di acquisto non funzionava
https://github.com/devcode-it/openstamanager/commit/8633f354f

- Get riga modifica righe documenti non corretto
https://github.com/devcode-it/openstamanager/commit/b710cae39

- Selezione aliquota in cambio IVA da bulk righe preventivi
https://github.com/devcode-it/openstamanager/commit/4745212a8

- Rimossi required non necessari
https://github.com/devcode-it/openstamanager/commit/2aa8495e9

- Impostazione prezzo in base a tipo di documento creato
https://github.com/devcode-it/openstamanager/commit/4118d5ba1

- Visualizzazione link in aggiunta attività non corretta
https://github.com/devcode-it/openstamanager/commit/504b1f1d8

- Link modulo Aggiornamenti non funzionante
https://github.com/devcode-it/openstamanager/commit/1612510e0

- Stampa inventario in base a fattore moltiplicativo
https://github.com/devcode-it/openstamanager/commit/0d733a80b

- Corretto riferimento tabella zz_categorie
https://github.com/devcode-it/openstamanager/commit/a0349bd93

- Retrofix, spostata rimozione foreign key dalla tabella my_impianti_marche_lang
https://github.com/devcode-it/openstamanager/commit/8d4c3ba76

- Numero di ore in stampa riepilogo senza prezzi non corretto
https://github.com/devcode-it/openstamanager/commit/a58623ed8

- Corretto calcolo next_execution_at per evitare date passate
https://github.com/devcode-it/openstamanager/commit/7eb22cb3a

- Calcolo scadenza task non corretto
https://github.com/devcode-it/openstamanager/commit/ea2caf9d0

- Importazione articoli collegati a ordini importFE
https://github.com/devcode-it/openstamanager/commit/69e1e78eb

- Risolta inaggiornabilità/installabilità moduli con templates
https://github.com/devcode-it/openstamanager/commit/fd7f020d4

- Calcolo data prossima esecuzione task non corretto
https://github.com/devcode-it/openstamanager/commit/d8b4429bd

- Visualizzazione table movimenti non corretta
https://github.com/devcode-it/openstamanager/commit/749e26f6e

- Stampa fatture non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/fdd18d29f

- Miglioria stampa fattura
https://github.com/devcode-it/openstamanager/commit/e532c63fd

- PHP ini per Docker non configurato correttamente
https://github.com/devcode-it/openstamanager/commit/97e814a8b

- Stampa fattura di vendita non corretta
https://github.com/devcode-it/openstamanager/commit/7a4e39e7b

#### 2.8.1 - 10/06/2025

##### Problemi noti
- Impostazione categoria files non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/47e44196e

- Aggiunta traduzioni mancanti per l'interfaccia
https://github.com/devcode-it/openstamanager/commit/32fe5549a

- Impostazione categoria documenti non salvava correttamente
https://github.com/devcode-it/openstamanager/commit/2992400fe

- Miglioria aggiunta conti per migliorare l'usabilità
https://github.com/devcode-it/openstamanager/commit/d65f3e753

- Sincronizzazione permessi non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/98c289db8

- Pulizia record segmenti e viste per ottimizzare il database
https://github.com/devcode-it/openstamanager/commit/7e191320e

- Funzioni dei plugin con stesso nome causavano conflitti
https://github.com/devcode-it/openstamanager/commit/b80aa7f83

- Aggiunto try-catch in fase di aggiunta file all'archivio del backup
https://github.com/devcode-it/openstamanager/commit/729ca960a

- Aggiornamento requisiti di sistema non corretto
https://github.com/devcode-it/openstamanager/commit/da7205e65

- Sovrascrittura vendor in fase di installazione causava errori
https://github.com/devcode-it/openstamanager/commit/7a5b4d81b

- Retrofix query installazione per compatibilità
https://github.com/devcode-it/openstamanager/commit/873c9b813

- Correzione e ottimizzazione query filtro datatables
https://github.com/devcode-it/openstamanager/commit/10b660c23

- Compatibilità PHP8 non completa
https://github.com/devcode-it/openstamanager/commit/66e519670

- Salvataggio partita IVA, codice fiscale e note da plugin sedi aggiuntive
https://github.com/devcode-it/openstamanager/commit/5ead50aa6

- Movimenti di giroconto per fatture non corretti
https://github.com/devcode-it/openstamanager/commit/6075550b3

- Stampa contratto scontato non corretta
https://github.com/devcode-it/openstamanager/commit/aa63ff20d

- Stampa contratti con sconto non funzionava
https://github.com/devcode-it/openstamanager/commit/beafb0b6c

- Creazione ordine moduli tradotti causava errori
https://github.com/devcode-it/openstamanager/commit/40d3ce81f

- Rimozione campo deprecato per pulizia codice
https://github.com/devcode-it/openstamanager/commit/4b7d4fac7

- Aggiunta opzioni variabili template stampa
https://github.com/devcode-it/openstamanager/commit/a8a5b5892

- Miglioria grafica menu dropdown
https://github.com/devcode-it/openstamanager/commit/db4a659e9

- Tasti in anagrafica non funzionanti
https://github.com/devcode-it/openstamanager/commit/db7c32347

- Name in import CSV articoli non impostato correttamente
https://github.com/devcode-it/openstamanager/commit/b66ca9f50

- Correzione minore su import FE con conversione unità di misura
https://github.com/devcode-it/openstamanager/commit/95679e3dd

- Stampa preventivi con descrizione non corretta
https://github.com/devcode-it/openstamanager/commit/84ebf0044

- Allineamento query vista Giacenze sedi
https://github.com/devcode-it/openstamanager/commit/085c3b220

- Link modulo da piano dei conti per vendita al banco non funzionante
https://github.com/devcode-it/openstamanager/commit/8cdb8763e

- Bug cambio di stato automatico documenti (#1518)
https://github.com/devcode-it/openstamanager/commit/3603d2914

- Import anche senza primary key non funzionava
https://github.com/devcode-it/openstamanager/commit/79962cd12

- Navigazione record per moduli custom non corretta
https://github.com/devcode-it/openstamanager/commit/8d46e8104

- Stampa DDT con sede destinazione diversa non corretta
https://github.com/devcode-it/openstamanager/commit/6dc317171

- Ricerca datatables non funzionava correttamente
https://github.com/devcode-it/openstamanager/commit/07dd32b1c

- Selezione sede partenza azienda non corretta
https://github.com/devcode-it/openstamanager/commit/1bfd469aa

- Nascoste info di ricevute scadute se prima della data da impostazioni
https://github.com/devcode-it/openstamanager/commit/e17453d06

- Blocco importazione ricevute infinita
https://github.com/devcode-it/openstamanager/commit/65930bf29

#### 2.8-beta - 20/05/2025

##### Problemi noti
- La cache degli hook veniva considerata scaduta fino a quando non trascorreva un intero giorno, causando l'esecuzione continua di alcuni hook per aggiornare la cache.
Questa modifica corregge la logica di validazione della cache, riducendo le chiamate non necessarie e migliorando le prestazioni.
https://github.com/devcode-it/openstamanager/commit/254a00c9c265c990ee708141455d30b9080132ff

- Presenza di vecchi file nella cartella vendor che danno errore in fase di installazione
https://github.com/devcode-it/openstamanager/commit/9d7120319

- Campi delle tabelle che non filtrano correttamente
https://github.com/devcode-it/openstamanager/commit/6649dbc2b

- Mancato salvataggio partita iva, codice fiscale e note da plugin sedi aggiuntive
https://github.com/devcode-it/openstamanager/commit/a093d7e1f

- Stampa errata dei contratti se presente uno sconto
https://github.com/devcode-it/openstamanager/commit/9c61ac4c4

- Creazione ordine nel caso di modulo rinominato o tradotto
https://github.com/devcode-it/openstamanager/commit/9eb2c0dc6

- Visualizzazione errata tasti in anagrafica
https://github.com/devcode-it/openstamanager/commit/30236f967

- Importazione errata CSV articoli non imposta correttamente il nome
https://github.com/devcode-it/openstamanager/commit/667c3d1f3

- Stampa errata di preventivi con descrizione
https://github.com/devcode-it/openstamanager/commit/331470044

- Errore nella vista del modulo Giacenze sedi
https://github.com/devcode-it/openstamanager/commit/c8ab59c32

- Bug cambio di stato automatico documenti (#1518)
https://github.com/devcode-it/openstamanager/commit/d0a914c67

- Navigazione record per moduli custom
https://github.com/devcode-it/openstamanager/commit/4ab83c587

- Info di ricevute scadute se prima della data da impostazioni
https://github.com/devcode-it/openstamanager/commit/e2730e695

- Blocco importazione ricevute infinita
https://github.com/devcode-it/openstamanager/commit/1164762c3

- Selezione sede partenza azienda
https://github.com/devcode-it/openstamanager/commit/6f9c71e7b

#### 2.7.3 - 15/04/2025

##### Problemi noti
- Associazione dell'articolo in importazione di una fattura elettronica
https://github.com/devcode-it/openstamanager/commit/95679e3dd96f0e87f3ec315e7f3cfa8e2e4ce05f

- Visualizzazione della tabella del plugin Serial in Articoli
https://github.com/devcode-it/openstamanager/commit/684783cc1219b573e2f3e38023f0dbd9458b19da

- Funzione di cambio stato della Newsletter
https://github.com/devcode-it/openstamanager/commit/b79aada8ed3c607d44cc3fd35001383fc99ba5e9

- Salvataggio del pagamento
https://github.com/devcode-it/openstamanager/commit/33e72b5f6aa205e0dafd483b10c598e485d0631d

- Spostamento degli allegati di fatture di acquisto e vendita
https://github.com/devcode-it/openstamanager/commit/9093ff7bd8d4ac56658523d532068a611d0e868f

- Duplicazione del preventivo
https://github.com/devcode-it/openstamanager/commit/84ebf0044b86c6bad6a7d7ea38cb9ef6306d7741

- Registrazione delle fatture di acquisto con split payment
https://github.com/devcode-it/openstamanager/commit/587b6c6b8958b426ebe7c762bea20aa46571cef7

- Avviso per fatture doppie per anno in fatture di acquisto
https://github.com/devcode-it/openstamanager/commit/61c50ea71f52d52b6553b0737afa87f6178ccce2

- Visualizzazione del nome articolo in Automezzi
https://github.com/devcode-it/openstamanager/commit/2e892e9b58096c2d4773adbde78dd2c2dbde3538

- Ricerca delle impostazioni
https://github.com/devcode-it/openstamanager/commit/89602045c9ccca55fb0fdeacd9fa73ae38ec00e7

- Generazione di presentazioni bancarie raggruppate per scadenza
https://github.com/devcode-it/openstamanager/commit/2f390067f7864c6cf98d210c4a497b6d64154cc5

- Generazione di autofatture in caso di IVA indetraibile
https://github.com/devcode-it/openstamanager/commit/c69bb1f40791a623278a477934f647dfb8e8ca66

- Blocco dei fornitori in base alla relazione
https://github.com/devcode-it/openstamanager/commit/b34667a7f0e59f83139492a4638755e316946619

- Inizializzazione di reverse charge e autofatture per le fatture
https://github.com/devcode-it/openstamanager/commit/e6dae1a8a1d954448b04eda286927a2dda9af1f4

#### 2.5.6 - 31/10/2024

##### Problemi noti
- registrazioni contabili con conto impostato Riepilogativo clienti o Riepilogativo fornitori
https://github.com/devcode-it/openstamanager/commit/37d8c7c34fc4e81f3a4f2f79c180365b7d447891


#### 2.5.3 - 07/08/2024

##### Problemi noti
- Non è possibile modificare la descrizione di una riga articolo inserita in un contratto, ddt, attività, ordine e preventivo.
https://github.com/devcode-it/openstamanager/commit/b82efb339f8df5da4f2279e25d72904778d2a8d3


- La ricerca globale non funziona.
https://github.com/devcode-it/openstamanager/commit/5c86d3b7489431b2e8001841b07769cd26e4c24c


#### 2.4.54 - 03/02/2024

##### Problemi noti
- In fase di installazione non viene compilato il file config se assente

##### Soluzione
Modificare il file index.php sostituendo il blocco di codice che inizia alla riga 30 con

```php
if ($dbo->isConnected()) {
    try {
        $microsoft = $dbo->selectOne('zz_oauth2', '*', ['nome' => 'Microsoft', 'enabled' => 1, 'is_login' => 1]);
    } catch (QueryException $e) {
    }
}
```
oppure aggiornare alla **v.2.5** di OpenSTAManager.

#### 2.4.35 - 12/08/2022

##### Problemi noti
- Colonna **id_module_start** mancante per tabella **zz_groups**
- Icona non aggiornata per il modulo **Causali movimenti**

##### Soluzione
Eseguire a database le seguenti query di allineamento:
```bash
UPDATE `zz_modules` SET `icon` = 'fa fa-exchange'  WHERE `title` = 'Causali movimenti';
ALTER TABLE `zz_groups` ADD `id_module_start` INT NULL AFTER `editable`;
```

oppure aggiornare alla **v.2.5** di OpenSTAManager.
