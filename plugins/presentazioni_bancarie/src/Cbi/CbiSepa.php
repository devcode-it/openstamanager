<?php

namespace Plugins\PresentazioniBancarie\Cbi;

use DOMDocument;
use DOMXPath;

class CbiSepa
{
    /**
     * @var Intestazione
     */
    protected $intestazione;

    /**
     * @var Ricevuta[]
     */
    protected $ricevute = [];

    public function __construct(Intestazione $intestazione)
    {
        $this->intestazione = $intestazione;
    }

    public function addRicevuta(Ricevuta $ricevuta)
    {
        $this->ricevute[] = $ricevuta;
    }

    /**
     * @return Intestazione
     */
    public function getIntestazione()
    {
        return $this->intestazione;
    }

    /**
     * @return Bonifico
     */
    public function setIntestazione(Intestazione $intestazione)
    {
        $this->intestazione = $intestazione;

        return $this;
    }

    /**
     * @return Ricevuta[]
     */
    public function getRicevute()
    {
        return $this->ricevute;
    }

    /**
     * @param Ricevuta[] $ricevute
     *
     * @return Bonifico
     */
    public function setRicevute($ricevute)
    {
        $this->ricevute = $ricevute;

        return $this;
    }

    public function asXML()
    {
        global $rootdir;
        $intestazione = $this->intestazione;
        $ricevute = $this->ricevute;
        $contenuto = '';

        // Verifica sulla presenza di ricevute
        if (empty($ricevute)) {
            throw new \Exception();
        }

        $credtm = date('Y-m-d\TH:i:s');
        $msgid = dechex(rand(100, 999).date('siHdmY')).'-';

        $content = file_get_contents(base_dir().'/plugins/presentazioni_bancarie/template/template_CBIPaymentRequest.xml');

        $CtrlSum = 0.00;
        $NbOfTxs = 1;
        $domDoc = new DOMDocument();
        $domDoc->preserveWhiteSpace = false;
        $domDoc->formatOutput = true;
        $domDoc->loadXML($content);
        $xpath = new DOMXPath($domDoc);
        $rootNamespace = $domDoc->lookupNamespaceUri($domDoc->namespaceURI);
        $xpath->registerNamespace('x', $rootNamespace);
        $results = $xpath->query('//x:GrpHdr/x:MsgId')->item(0);
        $attrVal = $domDoc->createTextNode($msgid);
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:GrpHdr/x:CreDtTm')->item(0);
        $attrVal = $domDoc->createTextNode($credtm);
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:GrpHdr/x:InitgPty/x:Nm')->item(0);
        $attrVal = $domDoc->createTextNode($intestazione->descrizione_banca);
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:GrpHdr/x:InitgPty/x:Id/x:OrgId/x:Othr/x:Id')->item(0);
        $attrVal = $domDoc->createTextNode('SIA'.$intestazione->codice_sia);
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:PmtInf/x:PmtInfId')->item(0);
        $attrVal = $domDoc->createTextNode($msgid);
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:PmtInf/x:ReqdExctnDt')->item(0);
        $attrVal = $domDoc->createTextNode(substr($credtm, 0, 10));
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:PmtInf/x:Dbtr/x:Nm')->item(0);
        $attrVal = $domDoc->createTextNode($intestazione->descrizione_banca);
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:PmtInf/x:Dbtr/x:Id/x:OrgId/x:Othr/x:Id')->item(0);
        $attrVal = $domDoc->createTextNode($intestazione->identificativo_creditore);
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:PmtInf/x:DbtrAcct/x:Id/x:IBAN')->item(0);
        $attrVal = $domDoc->createTextNode($intestazione->iban);
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:PmtInf/x:DbtrAgt/x:FinInstnId/x:ClrSysMmbId/x:MmbId')->item(0);
        $attrVal = $domDoc->createTextNode($intestazione->abi);
        $results->appendChild($attrVal);
        // creo gli elementi dei singoli bonifici
        foreach ($ricevute as $ricevuta) {
            $PmtInf = $xpath->query('//x:PmtInf')->item(0);
            $el = $domDoc->createElement('CdtTrfTxInf', '');
            $el1 = $domDoc->createElement('PmtId', '');
            $el2 = $domDoc->createElement('InstrId', $NbOfTxs);
            $el1->appendChild($el2);
            $el2 = $domDoc->createElement('EndToEndId', $msgid.$NbOfTxs);
            $el1->appendChild($el2);
            $el->appendChild($el1);
            $el1 = $domDoc->createElement('PmtTpInf', '');
            $el2 = $domDoc->createElement('CtgyPurp', '');
            $el3 = $domDoc->createElement('Cd', $ricevuta->ctgypurp);
            $el2->appendChild($el3);
            $el1->appendChild($el2);
            $el->appendChild($el1);
            $el1 = $domDoc->createElement('Amt', '');
            $el2 = $domDoc->createElement('InstdAmt', number_format($ricevuta->importo, 2, '.', ''));
            $newel2 = $el1->appendChild($el2);
            $newel2->setAttribute('Ccy', 'EUR');
            $el->appendChild($el1);
            $el1 = $domDoc->createElement('Cdtr', '');
            $el2 = $domDoc->createElement('Nm', $ricevuta->nome_debitore);
            $el1->appendChild($el2);
            $el->appendChild($el1);
            $el1 = $domDoc->createElement('CdtrAcct', '');
            $el2 = $domDoc->createElement('Id', '');
            $el3 = $domDoc->createElement('IBAN', $ricevuta->iban);
            $el2->appendChild($el3);
            $el1->appendChild($el2);
            $el->appendChild($el1);
            $el1 = $domDoc->createElement('RmtInf', '');
            $el2 = $domDoc->createElement('Ustrd', substr($ricevuta->descrizione, 0, 135));
            $el1->appendChild($el2);
            $el->appendChild($el1);
            $PmtInf->appendChild($el);
            ++$NbOfTxs;
            $CtrlSum += $ricevuta->importo;
        }
        $results = $xpath->query('//x:GrpHdr/x:NbOfTxs')->item(0);
        $attrVal = $domDoc->createTextNode($NbOfTxs - 1);
        $results->appendChild($attrVal);
        $results = $xpath->query('//x:GrpHdr/x:CtrlSum')->item(0);
        $attrVal = $domDoc->createTextNode(number_format(round($CtrlSum, 2), 2, '.', ''));
        $results->appendChild($attrVal);
        $contenuto = $domDoc->saveXML();

        return $contenuto;
    }
}
