<?php

$replaces = [];

// Retrocompatibilità
$id_cliente = $id_cliente ?: $idcliente;

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
if (empty($id_sede) || $id_sede == '-1') {
    $queryc = 'SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare($id_cliente);
} else {
    $queryc = 'SELECT an_anagrafiche.*, an_sedi.* FROM an_sedi JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=an_sedi.idanagrafica WHERE an_sedi.idanagrafica='.prepare($id_cliente).' AND an_sedi.id='.prepare($id_sede);
}
$rsc = $dbo->fetchArray($queryc);

// Lettura dati aziendali
$rsf = $dbo->fetchArray("SELECT * FROM an_anagrafiche WHERE idanagrafica = (SELECT valore FROM zz_settings WHERE nome='Azienda predefinita')");

// Prefissi e contenuti del replace
$replace = [
    'c_' => $rsc[0],
    'f_' => $rsf[0],
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
    // Individuazione dei campi minimi
    $values = (array) $values;
    if ($prefix == 'c_') {
        $keys = array_keys($values);
    }

    // Se l'azienda predefinita non è impostata
    if (empty($values) && $prefix == 'f_') {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = '';
        }
    }

    // Rinominazione dei campi
    foreach ($rename as $key => $value) {
        $values[$value] = $values[$key];
        unset($values[$key]);
    }

    // Salvataggio dei campi come variabili PHP
    foreach ($values as $key => $value) {
        ${$prefix.$key} = $value;
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

    // Completamento dei campi minimi
    if ($key == 'c_') {
        $keys = array_unique(array_merge($keys, array_keys($values)));
    }

    // Aggiunta delle informazioni per la sostituzione automatica
    foreach ($values as $key => $value) {
        $replaces[$prefix.$key] = $value;
    }
}

// Valori aggiuntivi per la sostituzione
$replaces = array_merge($replaces, [
    'default_header' => include DOCROOT.'/templates/base/header.php',
    'default_footer' => include DOCROOT.'/templates/base/footer.php',
    'docroot' => DOCROOT,
    'rootdir' => ROOTDIR,
    'directory' => Prints::getPrint($id_print)['full_directory'],
    'footer' => !empty($footer) ? $footer : '',
    'dicitura_fissa_fattura' => get_var('Dicitura fissa fattura'),
]);
