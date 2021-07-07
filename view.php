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

include_once __DIR__.'/core.php';

$file_id = filter('file_id');

$file = Models\Upload::find($file_id);

if (empty($file)) {
    return;
}

$link = base_path().'/'.$file->filepath;

// Force download of the file
if (get('force') == '1') {
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: Binary');
    header('Content-disposition: attachment; filename="'.basename($file->original_name).'"');
    readfile($docroot.'/'.$file->filepath);
    exit();
} else {
    if ($file->isFatturaElettronica()) {
        $content = file_get_contents(base_dir().'/'.$file->filepath);

        // Individuazione stylesheet
        $default_stylesheet = 'asso-invoice';

        $name = basename($file->original_name);
        $filename = explode('.', $name)[0];
        $pieces = explode('_', $filename);
        $stylesheet = $pieces[2];

        $stylesheet = base_dir().'/plugins/xml/'.$stylesheet.'.xsl';
        $stylesheet = file_exists($stylesheet) ? $stylesheet : base_dir().'/plugins/xml/'.$default_stylesheet.'.xsl';

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
        <iframe src="'.base_path().'/view.php?file_id='.$file_id.'&force=1">
            <a src="'.base_path().'/view.php?file_id='.$file_id.'&force=1">'.tr('Il browser non supporta i contenuti iframe: clicca qui per raggiungere il file originale').'</a>
        </iframe>';
        }
    }
}
