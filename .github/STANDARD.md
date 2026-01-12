# Standard del codice

Lo standard prevede l'utilizzo di nomi in italiano per la maggior parte dei contenuti, esclusi i sistemi di gestione interna del gestionale (tabelle `zz_*` e codici particolarmente rilevanti).

I nomi delle variabili devono seguire uno standard comune, che prevede la sostituzione degli spazi con `_` (*underscore*) e la rimozione delle lettere accentate a favore di quelle semplici.

Le variabili devono possedere nomi completi e chiari.
Esempio:
 - Partita IVA -> `partita_iva` nel database, `$partita_iva` in PHP

## Database

Gli identificatori devono iniziare per `id_*` e i flag per `is_*`.
E' fondamentale ricordarsi di impostare correttamente le **FOREIGN KEYS** delle relative tabelle.

Ci sono inoltre alcuni campi utilizzati in modo riccorrente all'interno del gestionale:
 - `default boolean NOT NULL DEFAULT 0` per i valori di default, non cancellabili e con modificabilità limitata
 - `predefined boolean NOT NULL DEFAULT 0` per i valori predefiniti in selezioni o gruppi
 - `visible boolean NOT NULL DEFAULT 1` per nascondere gli elementi
 - `deleted_at timestamp NULL DEFAULT NULL,` per segnare un elemento come eliminato

Per tabelle non presenti all'interno della lista ufficiale di OpenSTAManager (file **update/tables.php**), è necessario inoltre provvedere all'aggiunta dei seguenti campi:
 - `updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
 - `created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP`


### Aggiunta modulo
Con l'introduzione delle traduzioni, in fase di aggiunta di nuovi moduli, è necessario definire le traduzioni per le due lingue disponibili.

Note:
- `id`: viene incrementato automaticamente, non valorizzare
- `created_at` e `updated_at`: si valorizzano automaticamente durante le operazioni di normalizzazione database in fase di aggiornamento database (se la tabella è in tables.php)
- `LAST_INSERT_ID()` funziona solo per inserimenti singoli, per inserimenti multipli usare SELECT MAX(`id`)

Questo è un esempio di query per l'aggiunta di un modulo:

```sql
-- Creazione tabella
CREATE TABLE `NOME_TABELLA_MODULO` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB; 
-- Aggiungere il nome della nuova tabella al file update/tables.php per i campi updated_at e created_at

-- Aggiunta modulo
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES 
('NOME MODULO', 'CARTELLA_MODULO', 'SELECT |select| FROM `NOME_TABELLA` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.9', '2.9', 1, (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'MODULO PARENT'), 1, 1, 0, 0);

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES 
(1, (SELECT MAX(`id`) FROM `zz_modules`), 'TITOLO MODULO ITALIANO'),
(2, (SELECT MAX(`id`) FROM `zz_modules`), 'TITOLO MODULO INGLESE');
```


### Aggiunta vista

Questo è un esempio di query per l'aggiunta di una vista:

```sql
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'NOME MODULO'), 'id', 'id', 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'NOME MODULO'), 'Nome', 'name', 2, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`)-1 FROM `zz_views`), 'id'),
(2, (SELECT MAX(`id`)-1 FROM `zz_views`), 'id'),
(1, (SELECT MAX(`id`) FROM `zz_views`), 'Nome'),
(2, (SELECT MAX(`id`) FROM `zz_views`), 'Name');
```


### Aggiunta plugin

Questo è un esempio di query per l'aggiunta di un plugin:

```sql
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options`, `directory`, `help`) VALUES
('NOME PLUGIN', (SELECT `id` FROM `zz_modules` WHERE `name` = 'MODULO INIZIALE'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'MODULO FINALE'), 'tab', '', 1, 1, 0, '2.*', '2.9', 'custom', 'CARTELLA_PLUGIN', '');

INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `name`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_plugins`), 'NOME PLUGIN', 'TITOLO PLUGIN ITALIANO'),
(2, (SELECT MAX(`id`) FROM `zz_plugins`), 'NOME PLUGIN', 'TITOLO PLUGIN INGLESE');
```


### Aggiunta impostazione

Questo è un esempio di query per l'aggiunta di un'impostazione:

```sql
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('NOME IMPOSTAZIONE', '', 'TIPO IMPOSTAZIONE', 1, 'SEZIONE');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'TITOLO IMPOSTAZIONE ITALIANO', ''), 
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'TITOLO IMPOSTAZIONE INGLESE', '');
```

## Note

Malgrado una buona parte del codice ufficiale non segua completamente queste buone pratiche, è consigliato l'implementazione di queste linee guida per nuove funzioni e strutture mentre il sistema di base viene lentamente standardizzato.
