<?php

include_once __DIR__.'/init.php';

// XML
$xml = new DOMDocument();
$xml->loadXML($fattura_pa->toXML());

// XSL
$xsl = new DOMDocument();
$xsl->load(__DIR__.'/src/stylesheet-1.2.1.xsl');

// XSLT
$xslt = new XSLTProcessor();
$xslt->importStylesheet($xsl);

echo $xslt->transformToXML($xml);
