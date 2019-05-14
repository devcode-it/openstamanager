<?php

include_once __DIR__.'/core.php';

$file_id = filter('file_id');

$file = Models\Upload::find($file_id);

if (empty($file)) {
    return;
}

$link = ROOTDIR.'/'.$file->filepath;

if ($file->isFatturaElettronica()) {
    $content = file_get_contents(DOCROOT.'/'.$file->filepath);

    // Individuazione stylesheet
    $default_stylesheet = 'asso-invoice';

    $name = basename($file->original);
    $filename = explode('.', $name)[0];
    $pieces = explode('_', $filename);
    $stylesheet = $pieces[2];

    $stylesheet = DOCROOT.'/plugins/xml/'.$stylesheet.'.xsl';
    $stylesheet = file_exists($stylesheet) ? $stylesheet : DOCROOT.'/plugins/xml/'.$default_stylesheet.'.xsl';

    // XML
    $xml = new DOMDocument();
    $xml->loadXML($content);

    // XSL
    $xsl = new DOMDocument();
    $xsl->load($stylesheet);

    // XSLT
    $xslt = new XSLTProcessor();
    $xslt->importStylesheet($xsl);

    echo '
<style>
    #notifica {
        min-width: 860px !important;
    }
</style>';

    echo $xslt->transformToXML($xml);
} else {
    echo '
<style>
    body, iframe, img{
        border: 0;
        margin: 0;
        max-width: 100%;
    }
    iframe{
        width:100%;
        height:100%;
        min-height: 500px;
    }
</style>';

    if ($file->isImage()) {
        echo '
    <img src="'.$link.'"></img>';
    } else {
        if ($file->isPDF()) {
            $src = \Prints::getPDFLink($file->filepath);
        }

        echo '
    <iframe src="'.($link ?: $src).'">
        <a src="'.$link.'">'.tr('Il browser non supporta i contenuti iframe: clicca qui per raggiungere il file originale').'</a>
    </iframe>';
    }
}
