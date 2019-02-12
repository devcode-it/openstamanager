<?php

include_once __DIR__.'/../../core.php';

$directory = Plugins\ImportFE\FatturaElettronica::getImportDirectory();
$filename = get('filename');

$content = file_get_contents($directory.'/'.$filename);

// XML
$xml = new DOMDocument();
$xml->loadXML($content);

// XSL
$xsl = new DOMDocument();
$xsl->load(DOCROOT.'/assets/src/xml/fe-stylesheet-1.2.1.xsl');

// XSLT
$xslt = new XSLTProcessor();
$xslt->importStylesheet($xsl);

echo $xslt->transformToXML($xml);
