---
currentMenu: extra
---

# Extra

<!-- TOC depthFrom:2 depthTo:6 orderedList:false updateOnSave:true withLinks:true -->

- [Campi personalizzati](#campi-personalizzati)

<!-- /TOC -->

## Campi personalizzati

A partire dalla versione 2.4 è possibile sfruttare dei campi personalizzati per aggiungere informazioni ai moduli principali in modo dinamico.

Questi campi sono gestiti a livello di database attarverso le tabelle `zz_fields` e `zz_field_record`, che si occupano riespettivamente della gestione generale dei campi e del salvataggio dei record personalizzati.
Le procedure automatiche di gestione di questi campi sono integrate nei file `actions.php`, `editor.php` e `add.php`.

E' eventualmente disponibile il modulo **Campi personalizzati**, da abilitare, per la gestione dinamica di queste informazioni.
