<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

$replaces = [];

// Retrocompatibilità
$id_cliente = $id_cliente ?: $idcliente;

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
if (empty($id_sede) || $id_sede == '-1') {
    $queryc = 'SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare($id_cliente);
} else {
    $queryc = 'SELECT an_anagrafiche.*, an_sedi.*, if(an_sedi.codice_fiscale != "", an_sedi.codice_fiscale, an_anagrafiche.codice_fiscale) AS codice_fiscale, if(an_sedi.piva != "", an_sedi.piva, an_anagrafiche.piva) AS piva FROM an_sedi JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=an_sedi.idanagrafica WHERE an_sedi.idanagrafica='.prepare($id_cliente).' AND an_sedi.id='.prepare($id_sede);
}
$cliente = $dbo->fetchOne($queryc);

// Lettura dati aziendali
$azienda = $dbo->fetchOne('SELECT *, (SELECT iban FROM co_banche WHERE id IN (SELECT idbanca FROM co_documenti WHERE id = '.prepare($id_record).' ) ) AS codiceiban, (SELECT nome FROM co_banche WHERE id IN (SELECT idbanca FROM co_documenti WHERE id = '.prepare($id_record).' ) ) AS appoggiobancario, (SELECT bic FROM co_banche WHERE id IN (SELECT idbanca FROM co_documenti WHERE id = '.prepare($id_record)." ) ) AS bic FROM an_anagrafiche WHERE idanagrafica = (SELECT valore FROM zz_settings WHERE nome='Azienda predefinita')");

// Prefissi e contenuti del replace
$replace = [
    'c_' => isset($cliente) ? $cliente : [],
    'f_' => isset($azienda) ? $azienda : [],
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
$default_logo = App::filepath('templates/base|custom|/logo_azienda.jpg');

// Logo generico
if (!empty(setting('Logo stampe'))) {
    $custom_logo = App::filepath('files/anagrafiche/'.setting('Logo stampe'));
}

// Logo specifico della stampa
$logo = Prints::filepath($id_print, 'logo_azienda.jpg');

if (empty($logo)) {
    $logo = empty($custom_logo) ? $default_logo : $custom_logo;
}

// Valori aggiuntivi per la sostituzione
$replaces = array_merge($replaces, [
    'default_header' => $default_header,
    'default_footer' => $default_footer,
    'default_logo' => $default_logo,
    'logo' => $logo,
    'docroot' => DOCROOT,
    'rootdir' => ROOTDIR,
    'directory' => Prints::get($id_print)['full_directory'],
    'footer' => !empty($footer) ? $footer : '',
    'dicitura_fissa_fattura' => setting('Dicitura fissa fattura').((!empty(setting('OSMCloud Services API Token'))) ? tr('Documento privo di valenza fiscale (art 21 dpr 633/72).') : ''),
]);

unset($replace);
