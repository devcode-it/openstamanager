<?php

namespace Controllers;

use DOMDocument;
use Models\Upload;
use XSLTProcessor;

class UploadController extends Controller
{
    public function view($request, $response, $args)
    {
        $file = Upload::find($args['upload_id']);

        if (empty($file)) {
            return $response;
        }

        $link = ROOTDIR.'/'.$file->filepath;

        $args['file'] = $file;
        $args['link'] = $link;

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

            $args['content'] = $xslt->transformToXML($xml);

            $response = $this->twig->render($response, 'uploads\xml.twig', $args);
        } elseif ($file->isImage()) {
            $response = $this->twig->render($response, 'uploads\img.twig', $args);
        } elseif ($file->isPDF()) {
            $args['link'] = \Prints::getPDFLink($file->filepath);

            $response = $this->twig->render($response, 'uploads\frame.twig', $args);
        } else {
            $response = $this->download($request, $response, $args);
        }

        return $response;
    }

    public function upload($request, $response, $args)
    {
        $response = $this->twig->render($response, 'uploads\editor.twig', $args);

        return $response;
    }

    public function remove($request, $response, $args)
    {
        $response = $this->twig->render($response, 'uploads\actions.twig', $args);

        return $response;
    }

    public function download($request, $response, $args)
    {
        $file = Upload::find($args['upload_id']);

        if (empty($file)) {
            return $response;
        }

        download($file->filepath);

        return $response;
    }

    public function list($request, $response, $args)
    {
        $response = $this->twig->render($response, 'uploads\actions.twig', $args);

        return $response;
    }
}
