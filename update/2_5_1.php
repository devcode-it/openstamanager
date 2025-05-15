<?php

use Modules\Articoli\Categoria;
use Modules\FileAdapters\FileAdapter;

include __DIR__.'/core.php';
// Imposto l'adattatore locale di default se non definito
$default = FileAdapter::where('is_default', 1)->first();
if (empty($default)) {
    $adapter = FileAdapter::where('name', 'Adattatore locale')->first();
    $adapter->is_default = 1;
    $adapter->save();
}

// Se non è installato il modulo distinta base elimino il plugin
$fk = $database->fetchArray('SELECT * FROM `zz_plugins` WHERE `directory` = "distinta_base"');
if (empty($fk)) {
    // File e cartelle deprecate
    delete(realpath(base_dir().'/plugins/distinta_base/'));
}

// Controllo se è presente il campo name in an_provenienze
$has_name = database()->columnExists('an_provenienze', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `an_provenienze` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `an_provenienze` SET `name` = (SELECT `name` FROM `an_provenienze_lang` WHERE `id_record` = `an_provenienze`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('an_provenienze_lang', 'name')) {
    $database->query('ALTER TABLE `an_provenienze_lang` DROP `name`');
}

// Controllo se è presente il campo name in an_regioni
$has_name = database()->columnExists('an_regioni', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `an_regioni` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `an_regioni` SET `name` = (SELECT `name` FROM `an_regioni_lang` WHERE `id_record` = `an_regioni`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('an_regioni_lang', 'name')) {
    $database->query('ALTER TABLE `an_regioni_lang` DROP `name`');
}

// Controllo se è presente il campo name in an_relazioni
$has_name = database()->columnExists('an_relazioni', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `an_relazioni` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `an_relazioni` SET `name` = (SELECT `name` FROM `an_relazioni_lang` WHERE `id_record` = `an_relazioni`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('an_relazioni_lang', 'name')) {
    $database->query('ALTER TABLE `an_relazioni_lang` DROP `name`');
}

// Controllo se è presente il campo name in an_settori
$has_name = database()->columnExists('an_settori', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `an_settori` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `an_settori` SET `name` = (SELECT `name` FROM `an_settori_lang` WHERE `id_record` = `an_settori`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('an_settori_lang', 'name')) {
    $database->query('ALTER TABLE `an_settori_lang` DROP `name`');
}

// Controllo se è presente il campo name in an_tipianagrafiche
$has_name = database()->columnExists('an_tipianagrafiche', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `an_tipianagrafiche` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `an_tipianagrafiche` SET `name` = (SELECT `name` FROM `an_tipianagrafiche_lang` WHERE `id_record` = `an_tipianagrafiche`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('an_tipianagrafiche_lang', 'name')) {
    $database->query('ALTER TABLE `an_tipianagrafiche_lang` DROP `name`');
}

// Controllo se è presente il campo name in co_iva
$has_name = database()->columnExists('co_iva', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `co_iva` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `co_iva` SET `name` = (SELECT `name` FROM `co_iva_lang` WHERE `id_record` = `co_iva`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('co_iva_lang', 'name')) {
    $database->query('ALTER TABLE `co_iva_lang` DROP `name`');
}

// Controllo se è presente il campo name in co_pagamenti
$has_name = database()->columnExists('co_pagamenti', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `co_pagamenti` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `co_pagamenti` SET `name` = (SELECT `name` FROM `co_pagamenti_lang` WHERE `id_record` = `co_pagamenti`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('co_pagamenti_lang', 'name')) {
    $database->query('ALTER TABLE `co_pagamenti_lang` DROP `name`');
}

// Controllo se è presente il campo name in co_staticontratti
$has_name = database()->columnExists('co_staticontratti', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `co_staticontratti` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `co_staticontratti` SET `name` = (SELECT `name` FROM `co_staticontratti_lang` WHERE `id_record` = `co_staticontratti`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('co_staticontratti_lang', 'name')) {
    $database->query('ALTER TABLE `co_staticontratti_lang` DROP `name`');
}

// Controllo se è presente il campo name in co_statidocumento
$has_name = database()->columnExists('co_statidocumento', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `co_statidocumento` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `co_statidocumento` SET `name` = (SELECT `name` FROM `co_statidocumento_lang` WHERE `id_record` = `co_statidocumento`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('co_statidocumento_lang', 'name')) {
    $database->query('ALTER TABLE `co_statidocumento_lang` DROP `name`');
}

// Controllo se è presente il campo name in co_statidocumento
$has_name = database()->columnExists('co_statipreventivi', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `co_statipreventivi` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `co_statipreventivi` SET `name` = (SELECT `name` FROM `co_statipreventivi_lang` WHERE `id_record` = `co_statipreventivi`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('co_statipreventivi_lang', 'name')) {
    $database->query('ALTER TABLE `co_statipreventivi_lang` DROP `name`');
}

// Controllo se è presente il campo name in co_tipidocumento
$has_name = database()->columnExists('co_tipidocumento', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `co_tipidocumento` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `co_tipidocumento` SET `name` = (SELECT `name` FROM `co_tipidocumento_lang` WHERE `id_record` = `co_tipidocumento`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('co_tipidocumento_lang', 'name')) {
    $database->query('ALTER TABLE `co_tipidocumento_lang` DROP `name`');
}

// Controllo se è presente il campo name in co_tipi_scadenze
$has_name = database()->columnExists('co_tipi_scadenze', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `co_tipi_scadenze` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `co_tipi_scadenze` SET `name` = (SELECT `name` FROM `co_tipi_scadenze_lang` WHERE `id_record` = `co_tipi_scadenze`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('co_tipi_scadenze_lang', 'name')) {
    $database->query('ALTER TABLE `co_tipi_scadenze_lang` DROP `name`');
}
if (database()->columnExists('co_tipi_scadenze_lang', 'description')) {
    $database->query('ALTER TABLE `co_tipi_scadenze_lang` CHANGE `description` `title` VARCHAR(255) NOT NULL');
}

// Controllo se è presente il campo name in do_categorie
$has_name = database()->columnExists('do_categorie', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `do_categorie` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `do_categorie` SET `name` = (SELECT `name` FROM `do_categorie_lang` WHERE `id_record` = `do_categorie`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('do_categorie_lang', 'name')) {
    $database->query('ALTER TABLE `do_categorie_lang` DROP `name`');
}

// Controllo se è presente il campo name in dt_aspettobeni
$has_name = database()->columnExists('dt_aspettobeni', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `dt_aspettobeni` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `dt_aspettobeni` SET `name` = (SELECT `name` FROM `dt_aspettobeni_lang` WHERE `id_record` = `dt_aspettobeni`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('dt_aspettobeni_lang', 'name')) {
    $database->query('ALTER TABLE `dt_aspettobeni_lang` DROP `name`');
}

// Controllo se è presente il campo name in dt_causalet
$has_name = database()->columnExists('dt_causalet', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `dt_causalet` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `dt_causalet` SET `name` = (SELECT `name` FROM `dt_causalet_lang` WHERE `id_record` = `dt_causalet`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('dt_causalet_lang', 'name')) {
    $database->query('ALTER TABLE `dt_causalet_lang` DROP `name`');
}

// Controllo se è presente il campo name in dt_porto
$has_name = database()->columnExists('dt_porto', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `dt_porto` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `dt_porto` SET `name` = (SELECT `name` FROM `dt_porto_lang` WHERE `id_record` = `dt_porto`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('dt_porto_lang', 'name')) {
    $database->query('ALTER TABLE `dt_porto_lang` DROP `name`');
}

// Controllo se è presente il campo name in dt_spedizione
$has_name = database()->columnExists('dt_spedizione', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `dt_spedizione` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `dt_spedizione` SET `name` = (SELECT `name` FROM `dt_spedizione_lang` WHERE `id_record` = `dt_spedizione`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('dt_spedizione_lang', 'name')) {
    $database->query('ALTER TABLE `dt_spedizione_lang` DROP `name`');
}

// Controllo se è presente il campo name in dt_statiddt
$has_name = database()->columnExists('dt_statiddt', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `dt_statiddt` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `dt_statiddt` SET `name` = (SELECT `name` FROM `dt_statiddt_lang` WHERE `id_record` = `dt_statiddt`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('dt_statiddt_lang', 'name')) {
    $database->query('ALTER TABLE `dt_statiddt_lang` DROP `name`');
}

// Controllo se è presente il campo name in dt_tipiddt
$has_name = database()->columnExists('dt_tipiddt', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `dt_tipiddt` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `dt_tipiddt` SET `name` = (SELECT `name` FROM `dt_tipiddt_lang` WHERE `id_record` = `dt_tipiddt`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('dt_tipiddt_lang', 'name')) {
    $database->query('ALTER TABLE `dt_tipiddt_lang` DROP `name`');
}

// Controllo se è presente il campo name in em_lists
$has_name = database()->columnExists('em_lists', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `em_lists` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `em_lists` SET `name` = (SELECT `name` FROM `em_lists_lang` WHERE `id_record` = `em_lists`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('em_lists_lang', 'name')) {
    $database->query('ALTER TABLE `em_lists_lang` DROP `name`');
}

// Controllo se è presente il campo name in em_templates
$has_name = database()->columnExists('em_templates', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `em_templates` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `em_templates` SET `name` = (SELECT `name` FROM `em_templates_lang` WHERE `id_record` = `em_templates`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('em_templates_lang', 'name')) {
    $database->query('ALTER TABLE `em_templates_lang` DROP `name`');
}

// Controllo se è presente il campo name in fe_modalita_pagamento
$has_name = database()->columnExists('fe_modalita_pagamento', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `fe_modalita_pagamento` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `codice`');
    $database->query('UPDATE `fe_modalita_pagamento` SET `name` = (SELECT `name` FROM `fe_modalita_pagamento_lang` WHERE `id_record` = `fe_modalita_pagamento`.`codice` AND `id_lang` = 1)');
}
if (database()->columnExists('fe_modalita_pagamento_lang', 'name')) {
    $database->query('ALTER TABLE `fe_modalita_pagamento_lang` DROP `name`');
}

// Controllo se è presente il campo name in fe_natura
$has_name = database()->columnExists('fe_natura', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `fe_natura` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `codice`');
    $database->query('UPDATE `fe_natura` SET `name` = (SELECT `name` FROM `fe_natura_lang` WHERE `id_record` = `fe_natura`.`codice` AND `id_lang` = 1)');
}
if (database()->columnExists('fe_natura_lang', 'name')) {
    $database->query('ALTER TABLE `fe_natura_lang` DROP `name`');
}

// Controllo se è presente il campo name in fe_regime_fiscale
$has_name = database()->columnExists('fe_regime_fiscale', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `fe_regime_fiscale` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `codice`');
    $database->query('UPDATE `fe_regime_fiscale` SET `name` = (SELECT `name` FROM `fe_regime_fiscale_lang` WHERE `id_record` = `fe_regime_fiscale`.`codice` AND `id_lang` = 1)');
}
if (database()->columnExists('fe_regime_fiscale_lang', 'name')) {
    $database->query('ALTER TABLE `fe_regime_fiscale_lang` DROP `name`');
}

// Controllo se è presente il campo name in fe_stati_documento
$has_name = database()->columnExists('fe_stati_documento', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `fe_stati_documento` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `codice`');
    $database->query('UPDATE `fe_stati_documento` SET `name` = (SELECT `name` FROM `fe_stati_documento_lang` WHERE `id_record` = `fe_stati_documento`.`codice` AND `id_lang` = 1)');
}
if (database()->columnExists('fe_stati_documento_lang', 'name')) {
    $database->query('ALTER TABLE `fe_stati_documento_lang` DROP `name`');
}

// Controllo se è presente il campo name in fe_tipi_documento
$has_name = database()->columnExists('fe_tipi_documento', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `fe_tipi_documento` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `codice`');
    $database->query('UPDATE `fe_tipi_documento` SET `name` = (SELECT `name` FROM `fe_tipi_documento_lang` WHERE `id_record` = `fe_tipi_documento`.`codice` AND `id_lang` = 1)');
}
if (database()->columnExists('fe_tipi_documento_lang', 'name')) {
    $database->query('ALTER TABLE `fe_tipi_documento_lang` DROP `name`');
}

// Controllo se è presente il campo name in in_fasceorarie_lang
if (database()->columnExists('in_fasceorarie_lang', 'name')) {
    $database->query('ALTER TABLE `in_fasceorarie_lang` DROP `name`');
}

// Controllo se è presente il campo name in in_statiintervento
$has_name = database()->columnExists('in_statiintervento', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `in_statiintervento` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `codice`');
    $database->query('UPDATE `in_statiintervento` SET `name` = (SELECT `title` FROM `in_statiintervento_lang` WHERE `id_record` = `in_statiintervento`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('in_statiintervento_lang', 'name')) {
    $database->query('ALTER TABLE `in_statiintervento_lang` DROP `name`');
}

// Controllo se è presente il campo name in in_tipiintervento
$has_name = database()->columnExists('in_tipiintervento', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `in_tipiintervento` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `codice`');
    $database->query('UPDATE `in_tipiintervento` SET `name` = (SELECT `title` FROM `in_tipiintervento_lang` WHERE `id_record` = `in_tipiintervento`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('in_tipiintervento_lang', 'name')) {
    $database->query('ALTER TABLE `in_tipiintervento_lang` DROP `name`');
}

// Controllo se è presente il campo name in mg_articoli
$has_name = database()->columnExists('mg_articoli', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `mg_articoli` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `mg_articoli` SET `name` = (SELECT `name` FROM `mg_articoli_lang` WHERE `id_record` = `mg_articoli`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('mg_articoli_lang', 'name')) {
    $database->query('ALTER TABLE `mg_articoli_lang` DROP `name`');
}

// Controllo se è presente il campo name in mg_attributi_lang
if (database()->columnExists('mg_attributi_lang', 'name')) {
    $database->query('ALTER TABLE `mg_attributi_lang` DROP `name`');
}

// Controllo se è presente il campo name in mg_categorie_lang
if (database()->columnExists('mg_categorie_lang', 'name')) {
    $database->query('ALTER TABLE `mg_categorie_lang` DROP `name`');
}

// Rimozione categoria 'Componenti' se non collegata ad articoli
$categoria = $database->fetchOne('SELECT `mg_categorie`.* FROM `mg_categorie`
    LEFT JOIN `mg_categorie_lang` ON (`mg_categorie`.`id` = `mg_categorie_lang`.`id_record` AND `mg_categorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE `mg_categorie_lang`.`title` = "Componenti"');
if (!empty($categoria)) {
    $articoli_collegati = $database->fetchNum('SELECT COUNT(*) FROM `mg_articoli` WHERE `id_categoria` = '.prepare($categoria['id']).' OR `id_sottocategoria` = '.prepare($categoria['id']));
    if ($articoli_collegati === 0) {
        $database->query('DELETE FROM `mg_categorie` WHERE `id` = '.prepare($categoria['id']));
    }
}

// Controllo se è presente il campo name in mg_causali_movimenti_lang
if (database()->columnExists('mg_causali_movimenti_lang', 'name')) {
    $database->query('ALTER TABLE `mg_causali_movimenti_lang` DROP `name`');
}

// Controllo se è presente il campo name in mg_combinazioni_lang
if (database()->columnExists('mg_combinazioni_lang', 'name')) {
    $database->query('ALTER TABLE `mg_combinazioni_lang` DROP `name`');
}

// Controllo se è presente il campo name in my_impianti_categorie_lang
if (database()->columnExists('my_impianti_categorie_lang', 'name')) {
    $database->query('ALTER TABLE `my_impianti_categorie_lang` DROP `name`');
}

// Controllo se è presente il campo name in or_statiordine
$has_name = database()->columnExists('or_statiordine', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `or_statiordine` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `or_statiordine` SET `name` = (SELECT `name` FROM `or_statiordine_lang` WHERE `id_record` = `or_statiordine`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('or_statiordine_lang', 'name')) {
    $database->query('ALTER TABLE `or_statiordine_lang` DROP `name`');
}

// Controllo se è presente il campo name in or_tipiordine
$has_name = database()->columnExists('or_tipiordine', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `or_tipiordine` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `or_tipiordine` SET `name` = (SELECT `name` FROM `or_tipiordine_lang` WHERE `id_record` = `or_tipiordine`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('or_tipiordine_lang', 'name')) {
    $database->query('ALTER TABLE `or_tipiordine_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_cache
$has_name = database()->columnExists('zz_cache', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_cache` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_cache` SET `name` = (SELECT `name` FROM `zz_cache_lang` WHERE `id_record` = `zz_cache`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_cache_lang', 'name')) {
    $database->query('ALTER TABLE `zz_cache_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_currencies_lang
if (database()->columnExists('zz_currencies_lang', 'name')) {
    $database->query('ALTER TABLE `zz_currencies_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_groups
$has_name = database()->columnExists('zz_groups', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_groups` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_groups` SET `name` = (SELECT `name` FROM `zz_groups_lang` WHERE `id_record` = `zz_groups`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_groups_lang', 'name')) {
    $database->query('ALTER TABLE `zz_groups_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_group_module
$has_name = database()->columnExists('zz_group_module', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_group_module` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_group_module` SET `name` = (SELECT `name` FROM `zz_group_module_lang` WHERE `id_record` = `zz_group_module`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_group_module_lang', 'name')) {
    $database->query('ALTER TABLE `zz_group_module_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_hooks
$has_name = database()->columnExists('zz_hooks', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_hooks` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_hooks` SET `name` = (SELECT `name` FROM `zz_hooks_lang` WHERE `id_record` = `zz_hooks`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_hooks_lang', 'name')) {
    $database->query('ALTER TABLE `zz_hooks_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_imports
$has_name = database()->columnExists('zz_imports', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_imports` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_imports` SET `name` = (SELECT `name` FROM `zz_imports_lang` WHERE `id_record` = `zz_imports`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_imports_lang', 'name')) {
    $database->query('ALTER TABLE `zz_imports_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_modules
$has_name = database()->columnExists('zz_modules', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_modules` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_modules` SET `name` = (SELECT `name` FROM `zz_modules_lang` WHERE `id_record` = `zz_modules`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_modules_lang', 'name')) {
    $database->query('ALTER TABLE `zz_modules_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_plugins
$has_name = database()->columnExists('zz_plugins', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_plugins` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_plugins` SET `name` = (SELECT `name` FROM `zz_plugins_lang` WHERE `id_record` = `zz_plugins`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_plugins_lang', 'name')) {
    $database->query('ALTER TABLE `zz_plugins_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_prints_lang
if (database()->columnExists('zz_prints_lang', 'name')) {
    $database->query('ALTER TABLE `zz_prints_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_segments
$has_name = database()->columnExists('zz_segments', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_segments` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_segments` SET `name` = (SELECT `name` FROM `zz_segments_lang` WHERE `id_record` = `zz_segments`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_segments_lang', 'name')) {
    $database->query('ALTER TABLE `zz_segments_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_tasks
$has_name = database()->columnExists('zz_tasks', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_tasks` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_tasks` SET `name` = (SELECT `name` FROM `zz_tasks_lang` WHERE `id_record` = `zz_tasks`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_tasks_lang', 'name')) {
    $database->query('ALTER TABLE `zz_tasks_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_views
$has_name = database()->columnExists('zz_views', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_views` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_views` SET `name` = (SELECT `name` FROM `zz_views_lang` WHERE `id_record` = `zz_views`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_views_lang', 'name')) {
    $database->query('ALTER TABLE `zz_views_lang` DROP `name`');
}

// Controllo se è presente il campo name in zz_widgets
$has_name = database()->columnExists('zz_widgets', 'name');
if (!$has_name) {
    $database->query('ALTER TABLE `zz_widgets` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`');
    $database->query('UPDATE `zz_widgets` SET `name` = (SELECT `name` FROM `zz_widgets_lang` WHERE `id_record` = `zz_widgets`.`id` AND `id_lang` = 1)');
}
if (database()->columnExists('zz_widgets_lang', 'name')) {
    $database->query('ALTER TABLE `zz_widgets_lang` DROP `name`');
}
