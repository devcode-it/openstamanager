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

use Modules\Anagrafiche\Nazione;

$replaces = [];

// Retrocompatibilità
$id_cliente = $id_cliente ?: $idcliente;

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
if (empty($id_sede) || $id_sede == '-1') {
    $queryc = 'SELECT * FROM `an_anagrafiche` WHERE `id`='.prepare($id_cliente);
} else {
    $queryc = 'SELECT `an_anagrafiche`.*, `an_sedi`.*, if(`an_sedi`.`codice_fiscale` != "", `an_sedi`.`codice_fiscale`, `an_anagrafiche`.`codice_fiscale`) AS codice_fiscale, if(`an_sedi`.`p_iva` != "", `an_sedi`.`p_iva`, `an_anagrafiche`.`p_iva`) AS p_iva, if(`an_sedi`.`id_nazione` != "", `an_sedi`.`id_nazione`, `an_anagrafiche`.`id_nazione`) AS id_nazione FROM `an_sedi` JOIN `an_anagrafiche` ON `an_anagrafiche`.`id`=`an_sedi`.`id_anagrafica` WHERE `an_sedi`.`id_anagrafica`='.prepare($id_cliente).' AND `an_sedi`.`id`='.prepare($id_sede);
}
/**
 * @deprecated
 */
$cliente = $dbo->fetchOne($queryc);

// Lettura dati aziendali
/**
 * @deprecated
 */
$id_azienda = setting('Azienda predefinita');
$azienda = $dbo->fetchOne('SELECT *, (SELECT `iban` FROM `co_banche` WHERE `id` IN (SELECT `id_banca_azienda` FROM `co_documenti` WHERE `id` = '.prepare($id_record).')) AS codice_iban, (SELECT `nome` FROM `co_banche` WHERE `id` IN (SELECT `id_banca_azienda` FROM `co_documenti` WHERE `id` = '.prepare($id_record).')) AS appoggio_bancario, (SELECT `bic` FROM `co_banche` WHERE `id` IN (SELECT `id_banca_azienda` FROM `co_documenti` WHERE `id` = '.prepare($id_record).")) AS bic FROM `an_anagrafiche` WHERE `id` = (SELECT `valore` FROM `zz_settings` WHERE `nome`='Azienda predefinita')");

// Prefissi e contenuti del replace
/**
 * @deprecated
 */
$replace = [
    'c_' => $cliente ?? [],
    'f_' => $azienda ?? [],
];

// Rinominazione di particolari campi all'interno delle informazioni su anagrafica e azienda
$rename = [
    'capitale_sociale' => 'capsoc',
    'ragione_sociale' => 'ragionesociale',
    'codice_fiscale' => 'codicefiscale',
];

$keys = [];

// Predisposizione delle informazioni delle anagrafiche per la sostituzione automatica
foreach ($replace as $prefix => $values) {
    $values = (array) $values;

    // Rinominazione dei campi
    foreach ($rename as $key => $value) {
        $val = null;

        if (isset($values[$key])) {
            $val = $values[$key];
        }

        $values[$value] = $val;
        unset($values[$key]);
    }

    // Eventuali estensioni dei contenuti
    $citta = '';
    if (!empty($values['cap'])) {
        $citta .= $values['cap'];
    }
    if (!empty($values['citta'])) {
        $citta .= ' '.$values['citta'];
    }
    if (!empty($values['provincia'])) {
        $citta .= ' ('.$values['provincia'].')';
    }
    if (!empty($values['id_nazione'])) {
        $nazione = Nazione::find($values['id_nazione']);
        if ($nazione['iso2'] != 'IT') {
            $citta .= ' - '.$nazione->getTranslation('title');
        }
    }

    $values['citta_full'] = $citta;

    $replace[$prefix] = $values;

    // Individuazione dei campi minimi
    $keys = array_merge($keys, array_keys($values));
}

$keys = array_unique($keys);

foreach ($replace as $prefix => $values) {
    // Impostazione di default per le informazioni mancanti
    foreach ($keys as $key) {
        if (!isset($values[$key])) {
            $values[$key] = '';
        }
    }

    // Salvataggio dei campi come variabili PHP e aggiunta delle informazioni per la sostituzione automatica
    foreach ($values as $key => $value) {
        ${$prefix.$key} = $value;
        $replaces[$prefix.$key] = $value;
    }
}

// Header di default
$header_file = App::filepath('templates/base|custom|/header.php');
$default_header = include $header_file;
$default_header = !empty($options['hide-header']) ? '' : $default_header;

// Footer di default
$footer_file = App::filepath('templates/base|custom|/footer.php');
$default_footer = include $footer_file;
$default_footer = !empty($options['hide-footer']) ? '' : $default_footer;

// Logo di default
$default_logo_path = App::filepath('templates/base|custom|/logo_azienda.jpg');
$default_logo = str_replace(base_dir(), base_path_osm(), $default_logo_path);

// Logo specifico dell'anagrafica
$anagrafica_logo = null;
if (!empty($id_azienda)) {
    $upload = \Models\Upload::where('id_module', Models\Module::where('name', 'Anagrafiche')->first()->id)
        ->where('id_record', $id_azienda)
        ->where('key', 'print_logo')
        ->first();

    if (!empty($upload)) {
        $fileinfo = \Uploads::fileInfo($upload->filename);
        $logo_directory = '/files/anagrafiche/';
        $image = $logo_directory.$upload->filename;
        $image_thumbnail = $logo_directory.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'];
        $anagrafica_logo = file_exists(base_dir().$image_thumbnail) ? base_path_osm().$image_thumbnail : base_path_osm().$image;
    }
}

// Usa il logo di default se non è stato caricato un logo specifico
if (empty($anagrafica_logo)) {
    $anagrafica_logo = $default_logo;
}

// Valori aggiuntivi per la sostituzione
$replaces = array_merge($replaces, [
    'default_header' => $default_header,
    'default_footer' => $default_footer,
    'default_logo' => $default_logo,
    'logo' => $anagrafica_logo,
    'base_dir()' => base_dir(),
    'base_link()' => base_path_osm(),
    'directory' => Prints::get($id_print)['full_directory'],
    'footer' => !empty($footer) ? $footer : '',
    'dicitura_fissa_fattura' => setting('Dicitura fissa fattura').((setting('Regime Fiscale') != 'RF02' && setting('Regime Fiscale') != 'RF19' && setting('Regime Fiscale') != 'RF18') ? ($tipo_cliente != 'Privato' ? tr('Documento privo di valenza fiscale ai sensi dell’art. 21 Dpr 633/72. L’originale è disponibile all’indirizzo telematico da Lei fornito oppure nella Sua area riservata dell’Agenzia delle Entrate') : tr('Copia della fattura elettronica disponibile nella Sua area riservata dell’Agenzia delle Entrate')) : ''),
]);

unset($replace);
