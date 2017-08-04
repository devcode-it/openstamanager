---
currentMenu: api
---

# API

> Con application programming interface (in acronimo API, in italiano interfaccia di programmazione di un'applicazione), in informatica, si indica ogni insieme di procedure disponibili al programmatore, di solito raggruppate a formare un set di strumenti specifici per l'espletamento di un determinato compito all'interno di un certo programma.
>
> \-- <cite>[Wikipedia](https://it.wikipedia.org/wiki/Application_programming_interface)</cite>

L'API del progetto è attualmente ancora in sviluppo, e pertanto le funzioni disponibili potrebbero essere piuttosto ridotte. Di seguito sono elencate le basi per connettersi al sistema e ottenere i dati a cui si è interessati.

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Accesso](#accesso)
- [Output](#output)
- [Messaggi](#messaggi)
- [Formato dei componenti](#formato-dei-componenti)
- [Richieste di lettura](#richieste-di-lettura)
    - [Interventi](#interventi)
    - [Anagrafiche](#anagrafiche)
    - [Richieste disabilitate](#richieste-disabilitate)
        - [Modifiche](#modifiche)
        - [Eliminazioni](#eliminazioni)

<!-- /TOC -->

## Accesso

L'accesso all'API viene effettuato concatenendo la chiave dell'utenza all'URL del sito su cui è ospitato il progetto.

    http://<url_osm>/api/?token=<token>

La chiave di accesso è ottenibile eseguendo la seguente query all'interno del database del progetto:

```sql
SELECT `token` FROM `zz_tokens` WHERE `id_utente` = <id_utente>
```

## Output

L'API del progetto permette di ottenere le informazioni attraverso un array in formato JSON.
Per poter interpretare correttamente i dati, si devono ignorare gli indici numerici di primo livello  (non rilevanti all'interno del formato) e sfruttare in particolare i seguenti campi generici:

- `records`, rappresentante il numero totale dei record richiesti;
- `pages`, indicante il numero totale della pagine disponibili.

Si ricorda che l'API prevede la restituzione di un insieme di dati limitato rispetto alla richiesta effettuatua: per ottenere l'intero insieme di informazioni è necessario eseguire molteplici richieste consecutive basate sul campo `page`.

## Messaggi

Ogni richiesta effettuata all'API viene accompagnata da un messaggio predefinito che permette di interpretare in modo più preciso la risposta.
In particolare, sono presenti i seguenti _status_:

- `200: OK` - La richiesta è andata a buon fine.
- `400: Errore interno dell'API` - La richiesta effettuata risulta invalida per l'API.
- `401: Non autorizzato` - Accesso non autorizzato.
- `404: Non trovato` - La risorsa richiesta non risulta disponibile.
- `500: Errore del server` - Il gestionale non è in grado di completare la richiesta.

## Formato dei componenti

I seguenti componenti delle richieste devono seguire una rigida struttura:

- `page` (intero).
- `upd` (yyyy-MM-dd hh:mm:ss).

## Richieste di lettura

### Interventi

Tutto il contenuto della tabella in_interventi:

    http://<url_osm>/api/?token=<token>&resource=in_interventi

Singolo intervento (riga della tabella):

    http://<url_osm>/api/?token=<token>&resource=in_interventi&filter[id]=[1]

### Anagrafiche

Tutto il contenuto della tabella an_anagrafiche:

    http://<url_osm>/api/?token=<token>&resource=an_anagrafiche

Singolo intervento (riga della tabella):

    http://<url_osm>/api/?token=<token>&resource=an_anagrafiche&filter[idanagrafica]=[1]

Ricerca per ragione sociale:

    http://<url_osm>/api/?token=<token>&resource=an_anagrafiche&filter[ragione_sociale]=[%<stringa_ragione_sociale>%]

### Richieste disabilitate

#### Modifiche

Tutti i contenuti di tutte le tabelle:

    http://<url_osm>/api/?token=<token>&resource=updates

Tutti i contenuti di tutte le tabelle, aggiornati a partire da una data precisa:

    http://<url_osm>/api/?token=<token>&resource=updates&upd=2016-01-31%2010:44:31

#### Eliminazioni

Tutte le eliminazioni di tutte le tabelle:

    http://<url_osm>/api/?token=<token>&resource=deleted
