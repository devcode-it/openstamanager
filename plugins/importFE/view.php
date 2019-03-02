<?php

$directory = Plugins\ImportFE\FatturaElettronica::getImportDirectory();
$filename = get('filename');

$content = file_get_contents($directory.'/'.$filename);

// XML
$xml = new DOMDocument();
$xml->loadXML($content);

// XSL
$xsl = new DOMDocument();
$xsl->load(DOCROOT.'/plugins/xml/asso-invoice.xsl');

// XSLT
$xslt = new XSLTProcessor();
$xslt->importStylesheet($xsl);

echo $xslt->transformToXML($xml);
