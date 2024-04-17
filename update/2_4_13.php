<?php

// File e cartelle deprecate
$files = [
    'lib\init.js',
    'lib\functions.js',
    'include\src\HookManager.php',
    'plugins\xml\AT_v1.0.xml',
    'plugins\xml\DT_v1.0.xml',
    'plugins\xml\EC_v1.0.xml',
    'plugins\xml\MC_v1.0.xml',
    'plugins\xml\MT_v1.0.xml',
    'plugins\xml\NE_v1.0.xml',
    'plugins\xml\NS_v1.0.xml',
    'plugins\xml\RC_v1.0.xml',
    'plugins\xml\SE_v1.0.xml',
    'plugins\importFE\rows.php',
    'plugins\exportFE\view.php',
    'plugins\exportFE\src\stylesheet-1.2.1.xsl',
    'plugins\exportFE\src\Connection.php',
    'templates\riepilogo_interventi\pdfgen.riepilogo_interventi.php',
    'templates\riepilogo_interventi\intervento_body.html',
    'templates\riepilogo_interventi\intervento.html',
    'templates\scadenzario\pdfgen.scadenzario.php',
    'templates\scadenzario\scadenzario_body.html',
    'templates\scadenzario\scadenzario.html',
    'templates\registro_iva\pdfgen.registro_iva.php',
    'templates\registro_iva\registroiva_body.html',
    'templates\registro_iva\body.php',
    'templates\preventivi_cons\body.php',
    'templates\contratti_cons\body.php',
    'templates\magazzino_inventario\pdfgen.magazzino_inventario.php',
    'templates\magazzino_inventario\magazzino_inventario_body.html',
    'templates\magazzino_inventario\magazzino_inventario.html',
    'modules\contratti\plugins\contratti.ordiniservizio.interventi.php',
    'modules\contratti\plugins\contratti.ordiniservizio.php',
    'modules\interventi\src\TipoSessione.php',
    'modules\anagrafiche\plugins\statistiche.php',
    'modules\partitario\dettagli_movimento.php',
    'modules\interventi\api\*',
    'modules\anagrafiche\api\*',
    'modules\articoli\api\*',
    'modules\aggiornamenti\api\*',
    'modules\stati_contratto\api\*',
    'modules\stati_intervento\api\*',
    'modules\stati_preventivo\api\*',
    'modules\tipi_intervento\api\*',
    'modules\utenti\api\*',
    'templates\interventi_ordiniservizio\*',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'\\'.$value);
}

delete($files);
