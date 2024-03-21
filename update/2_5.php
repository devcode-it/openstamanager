<?php
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
include __DIR__.'/../config.inc.php';

// File e cartelle deprecate
$files = [
    'assets/src/js/wacom/sigCaptDialog/libs/',
    'modules/impianti/plugins/',
    'modules/voci_servizio/'
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

/* Fix per file sql di update aggiornato dopo rilascio 2.4.35 */
$has_column = null;
$col_righe = $database->fetchArray('SHOW COLUMNS FROM `zz_groups`');
$has_column = array_search('id_module_start', array_column($col_righe, 'Field'));
if (empty($has_column)) {
    $database->query('ALTER TABLE `zz_groups` ADD `id_module_start` INT NULL AFTER `editable`');
}


if ($backup_dir){
    /* Rinomino i file zip all'interno della cartella di backup, aggiungendo "FULL" alla fine del nome*/
    $filesystem = new SymfonyFilesystem();
    //glob viene utilizzata per ottenere la lista dei file zip all'interno della cartella $backup_dir.
    $files = glob($backup_dir . '/*.zip');

    foreach ($files as $file) {
        $fileName = basename($file);
        
        if (strpos($fileName, 'FULL') === false) {
            $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . ' FULL.zip';
            $newFilePath = $backup_dir . '/' . $newFileName;
            
            $filesystem->rename($file, $newFilePath);
        }
    }
}else{
    echo "Impossibile completare l'aggiornamento. Variabile <b>$backup_dir</b> non impostata.\n";
}

// Aggiunta record per lingua inglese
$tables = [
    'an_provenienze_lang',
    'an_relazioni_lang',
    'an_settori_lang',
    'an_tipianagrafiche_lang',
    'co_iva_lang',
    'co_pagamenti_lang',
    'co_staticontratti_lang',
    'co_statidocumento_lang',
    'co_statipreventivi_lang',
    'co_tipidocumento_lang',
    'co_tipi_scadenze_lang',
    'do_categorie_lang',
    'dt_aspettobeni_lang',
    'dt_causalet_lang',
    'dt_porto_lang',
    'dt_spedizione_lang',
    'dt_statiddt_lang',
    'dt_tipiddt_lang',
    'em_lists_lang',
    'em_templates_lang',
    'in_fasceorarie_lang',
    'in_statiintervento_lang',
    'in_tipiintervento_lang',
    'mg_articoli_lang',
    'mg_attributi_lang',
    'mg_categorie_lang',
    'mg_causali_movimenti_lang',
    'mg_combinazioni_lang',
    'or_statiordine_lang',
    'or_tipiordine_lang',
    'zz_cache_lang',
    'zz_currencies_lang',
    'zz_groups_lang',
    'zz_group_module_lang',
    'zz_hooks_lang',
    'zz_imports_lang',
    'zz_modules_lang',
    'zz_plugins_lang',
    'zz_prints_lang',
    'zz_segments_lang',
    'zz_settings_lang',
    'zz_tasks_lang',
    'zz_views_lang',
    'zz_widgets_lang',
];

foreach ($tables as $table) {
    $database->query('CREATE TEMPORARY TABLE `tmp` SELECT * FROM '.$table);
    $database->query('ALTER TABLE `tmp` DROP `id`');
    $database->query('UPDATE `tmp` SET `id_lang` = 2');
    $database->query('INSERT INTO '.$table.' SELECT NULL,tmp. * FROM tmp');
    $database->query('DROP TEMPORARY TABLE tmp');
}

// Traduzione moduli
$traduzioni = [
    ['zz_modules_lang', 'Customers', 'Anagrafiche'],
    ['zz_modules_lang', 'Tasks', 'Interventi'],
    ['zz_modules_lang', 'Updates', 'Aggiornamenti'],
    ['zz_modules_lang', 'Type of customers', 'Tipi di anagrafiche'],
    ['zz_modules_lang', 'Type of Tasks', 'Tipi di intervento'],
    ['zz_modules_lang', 'Tasks states', 'Stati di intervento'],
    ['zz_modules_lang', 'Accounting', 'Contabilità'],
    ['zz_modules_lang', 'Quotes', 'Preventivi'],
    ['zz_modules_lang', 'Sales invoices', 'Fatture di vendita'],
    ['zz_modules_lang', 'Purchase invoices', 'Fatture di acquisto'],
    ['zz_modules_lang', 'Scheduled payments', 'Scadenzario'],
    ['zz_modules_lang', 'Storage', 'Magazzino'],
    ['zz_modules_lang', 'Pricing lists', 'Listini'],
    ['zz_modules_lang', 'Customer orders', 'Ordini cliente'],
    ['zz_modules_lang', 'Supplier orders', 'Ordini fornitore'],
    ['zz_modules_lang', 'Sales delivery document', 'Ddt di vendita'],
    ['zz_modules_lang', 'Suppliers delivery document', 'Ddt di acquisto'],
    ['zz_modules_lang', 'Area', 'Zone'],
    ['zz_modules_lang', 'Technicians rates', 'Tecnici e tariffe'],
    ['zz_modules_lang', 'Balances', 'Piano dei conti'],
    ['zz_modules_lang', 'Products', 'Articoli'],
    ['zz_modules_lang', 'Discounts and Surcharges', 'Piani di sconto/maggiorazione'],
    ['zz_modules_lang', 'Plants', 'Impianti'],
    ['zz_modules_lang', 'Contracts', 'Contratti'],
    ['zz_modules_lang', 'Sales', 'Vendite'],
    ['zz_modules_lang', 'Purchases', 'Acquisti'],
    ['zz_modules_lang', 'Tools', 'Strumenti'],
    ['zz_modules_lang', 'Views', 'Viste'],
    ['zz_modules_lang', 'Users and permissions', 'Utenti e permessi'],
    ['zz_modules_lang', 'Settings', 'Impostazioni'],
    ['zz_modules_lang', 'Tables', 'Tabelle'],
    ['zz_modules_lang', 'VAT', 'IVA'],
    ['zz_modules_lang', 'Causals', 'Causali'],
    ['zz_modules_lang', 'Goods appearances', 'Aspetto beni'],
    ['zz_modules_lang', 'Units of measure', 'Unità di misura'],
    ['zz_modules_lang', 'Payments', 'Pagamenti'],
    ['zz_modules_lang', 'Products categories', 'Categorie articoli'],
    ['zz_modules_lang', 'Movements', 'Movimenti'],
    ['zz_modules_lang', 'Charts', 'Statistiche'],
    ['zz_modules_lang', 'Email accounts', 'Account email'],
    ['zz_modules_lang', 'Email templates', 'Template email'],
    ['zz_modules_lang', 'Email managements', 'Gestione email'],
    ['zz_modules_lang', 'Segments', 'Segmenti'],
    ['zz_modules_lang', 'Banks', 'Banche'],
    ['zz_modules_lang', 'Custom fields', 'Campi personalizzati'],
    ['zz_modules_lang', 'Documents management', 'Gestione documentale'],
    ['zz_modules_lang', 'Document categories', 'Categorie documenti'],
    ['zz_modules_lang', 'Types of shipping', 'Tipi di spedizione'],
    ['zz_modules_lang', 'Plants categories', 'Categorie impianti'],
    ['zz_modules_lang', 'Quote statuses', 'Stati dei preventivi'],
    ['zz_modules_lang', 'Contract statuses', 'Stati dei contratti'],
    ['zz_modules_lang', 'Services status', 'Stato dei servizi'],
    ['zz_modules_lang', 'Type of deadlines', 'Tipi scadenze'],
    ['zz_modules_lang', 'Email statuses', 'Coda di invio'],
    ['zz_modules_lang', 'Lists', 'Liste'],
    ['zz_modules_lang', 'Relations', 'Relazioni'],
    ['zz_modules_lang', 'Prints', 'Stampe'],
    ['zz_modules_lang', 'Movements causes', 'Causali movimenti'],
    ['zz_modules_lang', 'Stock locations', 'Giacenze sedi'],
    ['zz_modules_lang', 'Types of document', 'Tipi documento'],
    ['zz_modules_lang', 'Combination attributes', 'Attributi combinazioni'],
    ['zz_modules_lang', 'Combinations', 'Combinazioni'],
    ['zz_modules_lang', 'Contact roles', 'Mansioni referenti'],
    ['zz_modules_lang', 'Time slots', 'Fasce orarie'],
    ['zz_modules_lang', 'Events', 'Eventi'],
    ['zz_modules_lang', 'Customers origins', 'Provenienze clienti'],
    ['zz_modules_lang', 'Product sectors', 'Settori merceologici'],
    ['zz_modules_lang', 'Maps', 'Mappa'],
    ['zz_modules_lang', 'Customers price lists', 'Listini cliente'],
    ['zz_modules_lang', 'Orders statuses', 'Stati degli ordini'],
    ['zz_modules_lang', 'Invoices statuses', 'Stati fatture'],
    ['zz_modules_lang', 'Internal tasks management', 'Gestione task'],
    ['zz_modules_lang', 'Vehicles', 'Automezzi'],
    ['zz_modules_lang', 'OAuth access', 'Accesso con OAuth'],
    ['zz_modules_lang', 'Storage connectors', 'Adattatori di archiviazione'],
    ['zz_modules_lang', 'test', 'test'],

    ['zz_widgets_lang', 'Customers number', 'Numero di clienti'],
    ['zz_widgets_lang', 'Technicians number', 'Numero di tecnici'],
    ['zz_widgets_lang', 'Supplier number', 'Numero di fornitori'],
    ['zz_widgets_lang', 'Agents number', 'Numero di Agenti'],
    ['zz_widgets_lang', 'Reminders for contracts to plan', 'Promemoria contratti da pianificare'],
    ['zz_widgets_lang', 'Deadlines', 'Scadenze'],
    ['zz_widgets_lang', 'Products running out', 'Articoli in esaurimento'],
    ['zz_widgets_lang', 'Quotations in process', 'Preventivi in lavorazione'],
    ['zz_widgets_lang', 'Contracts due for renewal', 'Contratti in scadenza'],
    ['zz_widgets_lang', 'Contractual rates', 'Rate contrattuali'],
    ['zz_widgets_lang', 'Inventory printout', 'Stampa inventario'],
    ['zz_widgets_lang', 'Sales revenue', 'Fatturato'],
    ['zz_widgets_lang', 'Purchases', 'Acquisti'],
    ['zz_widgets_lang', 'Customer credit balances', 'Crediti da clienti'],
    ['zz_widgets_lang', 'Bills due', 'Debiti verso fornitori'],
    ['zz_widgets_lang', 'Number of carriers', 'Numero di vettori'],
    ['zz_widgets_lang', 'All company records', 'Tutte le anagrafiche'],
    ['zz_widgets_lang', 'Inventory value', 'Valore magazzino'],
    ['zz_widgets_lang', 'Items in stock', 'Articoli in magazzino'],
    ['zz_widgets_lang', 'Print calendar', 'Stampa calendario'],
    ['zz_widgets_lang', 'Planned activities', 'Attività da pianificare'],
    ['zz_widgets_lang', 'Activities to be scheduled', 'Attività nello stato da programmare'],
    ['zz_widgets_lang', 'Confirmed activities', 'Attività confermate'],
    ['zz_widgets_lang', 'Inventory usage', 'Spazio utilizzato'],
    ['zz_widgets_lang', 'Internal notes', 'Note interne'],
    ['zz_widgets_lang', 'Unsubscribed synchronization', 'Sincronizzazione disiscritti'],
    ['zz_widgets_lang', 'Active price lists', 'Listini attivi'],
    ['zz_widgets_lang', 'Expired price lists', 'Listini scaduti'],
    ['zz_widgets_lang', 'Weekly calendar printout', 'Stampa calendario settimanale'],
    ['zz_widgets_lang', 'Quotations to invoice', 'Preventivi da fatturare'],
    ['zz_widgets_lang', 'Print load/unload document', 'Stampa carico odierno'],
    ['zz_widgets_lang', 'Print inventory report', 'Stampa giacenza'],
    ['zz_widgets_lang', 'Print assets report', 'Stampa cespiti'],
];

foreach ($traduzioni as $traduzione) {
    $database->query('UPDATE '.$traduzione[0].' SET `title` = "'.$traduzione[1].'" WHERE `name` = "'.$traduzione[2].'" AND `id_lang` = 2');
}
