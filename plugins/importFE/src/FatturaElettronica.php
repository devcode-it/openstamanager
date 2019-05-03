<?php

namespace Plugins\ImportFE;

use Modules;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Nazione;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato as StatoFattura;
use Modules\Fatture\Tipo as TipoFattura;
use UnexpectedValueException;
use Uploads;
use Util\XML;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.9
 */
class FatturaElettronica
{
    protected static $directory = null;

    /** @var array Percorso del file XML */
    protected $file = null;

    /** @var array XML della fattura */
    protected $xml = null;

    /** @var Fattura Fattura collegata */
    protected $fattura = null;

    public function __construct($name)
    {
        $this->file = static::getImportDirectory().'/'.$name;

        if (ends_with($name, '.p7m')) {
            $file = XML::decodeP7M($this->file);

            if (!empty($file)) {
                delete($this->file);

                $this->file = $file;
            }
        }

        $this->xml = XML::readFile($this->file);

        // Individuazione fattura pre-esistente
        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];
        $numero = $dati_generali['Numero'];
        $progressivo_invio = $this->getHeader()['DatiTrasmissione']['ProgressivoInvio'];

        $fattura = Fattura::where([
            'progressivo_invio' => $progressivo_invio,
            'numero_esterno' => $numero,
            'data' => $data,
        ])->first();

        if (!empty($fattura)) {
            throw new UnexpectedValueException();
        }
    }

    public static function getImportDirectory()
    {
        if (!isset(self::$directory)) {
            $module = Modules::get('Fatture di acquisto');

            $plugin = $module->plugins->first(function ($value, $key) {
                return $value->name = 'Fatturazione Elettronica';
            });

            self::$directory = DOCROOT.'/'.$plugin->upload_directory;
        }

        return self::$directory;
    }

    public static function store($filename, $content)
    {
        $directory = static::getImportDirectory();
        $file = $directory.'/'.$filename;

        directory($directory);
        file_put_contents($file, $content);

        return $filename;
    }

    public static function isValid($name)
    {
        try {
            new static($name);

            return true;
        } catch (UnexpectedValueException $e) {
            $file = static::getImportDirectory().'/'.$name;
            delete($file);

            return false;
        }
    }

    public static function manage($name)
    {
        try {
            $fattura = new FatturaOrdinaria($name);

            return $fattura;
        } catch (UnexpectedValueException $e) {
            $fattura = new FatturaSemplificata($name);

            return $fattura;
        }
    }

    public function getHeader()
    {
        return $this->xml['FatturaElettronicaHeader'];
    }

    public function getBody()
    {
        return $this->xml['FatturaElettronicaBody'];
    }

    public function delete()
    {
        delete($this->file);
    }

    public function getAllegati()
    {
        $result = $this->getBody()['Allegati'];

        $result = $this->forceArray($result);

        return array_clean($result);
    }

    public function saveAllegati()
    {
        $allegati = $this->getAllegati();

        $module = Modules::get('Fatture di acquisto');

        $info = [
            'category' => tr('Fattura Elettronica'),
            'id_module' => $module->id,
            'id_record' => $this->fattura->id,
        ];

        foreach ($allegati as $allegato) {
            $content = base64_decode($allegato['Attachment']);

            $extension = '';
            if (!empty($allegato['FormatoAttachment'])) {
                $extension = '.'.strtolower($allegato['FormatoAttachment']);
            }

            $original = $allegato['NomeAttachment'].$extension;
            $filename = Uploads::getName($original, [
                'id_module' => $module['id'],
            ]);

            file_put_contents($module->upload_directory.'/'.$filename, $content);

            Uploads::register(array_merge($info, [
                'filename' => $filename,
                'original' => $original,
            ]));
        }

        // Registrazione XML come allegato
        $filename = Uploads::upload($this->file, array_merge($info, [
            'name' => tr('Fattura Elettronica'),
            'original' => basename($this->file),
        ]));
    }

    public function saveAnagrafica($type = 'Fornitore')
    {
        $info = $this->getAnagrafe();

        $tipologia = TipoAnagrafica::where('descrizione', $type)->first();

        $anagrafica = Anagrafica::whereHas('tipi', function ($query) use ($tipologia) {
            $query->where('an_tipianagrafiche.idtipoanagrafica', '=', $tipologia->id);
        });

        if (!empty($info['partita_iva'])) {
            $anagrafica->where('piva', $info['partita_iva']);
        }

        if (!empty($info['codice_fiscale'])) {
            $anagrafica->where('codice_fiscale', $info['codice_fiscale']);
        }

        $anagrafica = $anagrafica->first();

        if (!empty($anagrafica)) {
            return $anagrafica;
        }

        $anagrafica = Anagrafica::build($info['ragione_sociale'], $info['nome'], $info['cognome'], [
            TipoAnagrafica::where('descrizione', $type)->first()->id,
        ]);

        if (!empty($info['partita_iva'])) {
            $anagrafica->partita_iva = $info['partita_iva'];
        }

        if (!empty($info['codice_fiscale'])) {
            $anagrafica->codice_fiscale = $info['codice_fiscale'];
        }

        // Informazioni sull'anagrafica
        if (!empty($info['rea'])) {
            if (!empty($info['rea']['codice'])) {
                $anagrafica->codicerea = $info['rea']['codice'];
            }

            if (!empty($info['rea']['capitale_sociale'])) {
                $anagrafica->capitale_sociale = $info['rea']['capitale_sociale'];
            }
        }

        $anagrafica->save();

        // Informazioni sulla sede
        $sede = $anagrafica->sedeLegale;

        $sede->indirizzo = $info['sede']['indirizzo'];
        $sede->cap = $info['sede']['cap'];
        $sede->citta = $info['sede']['comune'];
        if (!empty($info['sede']['provincia'])) {
            $sede->provincia = $info['sede']['provincia'];
        }
        $sede->nazione()->associate(Nazione::where('iso2', $info['sede']['nazione'])->first());

        $contatti = $info['contatti'];
        if (!empty($contatti)) {
            if (!empty($contatti['telefono'])) {
                $sede->telefono = $contatti['telefono'];
            }

            if (!empty($contatti['fax'])) {
                $sede->fax = $contatti['fax'];
            }

            if (!empty($contatti['email'])) {
                $sede->email = $contatti['email'];
            }
        }

        $sede->save();

        return $anagrafica;
    }

    /**
     * Registra la fattura elettronica come fattura del gestionale.
     *
     * @return Fattura
     */
    public function saveFattura($id_pagamento, $id_sezionale, $id_tipo)
    {
        $anagrafica = $this->saveAnagrafica();

        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];

        $numero_esterno = $dati_generali['Numero'];
        $progressivo_invio = $this->getHeader()['DatiTrasmissione']['ProgressivoInvio'];

        $tipo = TipoFattura::where('id', $id_tipo)->first();

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_sezionale);
        $this->fattura = $fattura;

        $fattura->progressivo_invio = $progressivo_invio;
        $fattura->numero_esterno = $numero_esterno;
        $fattura->idpagamento = $id_pagamento;

        // Per il destinatario, la data di ricezione della fattura assume grande rilievo ai fini IVA, poiché determina la decorrenza dei termini per poter esercitare il diritto alla detrazione.
        // La data di ricezione della fattura è contenuta all’interno della “ricevuta di consegna” visibile al trasmittente della stessa.
        $fattura->data_ricezione = $dati_generali['Data'];

        $stato_documento = StatoFattura::where('descrizione', 'Emessa')->first();
        $fattura->stato()->associate($stato_documento);

        // Ritenuta d'Acconto
        $ritenuta = $dati_generali['DatiRitenuta'];
        if (!empty($ritenuta)) {
            $percentuale = $ritenuta['AliquotaRitenuta'];
            $importo = $ritenuta['ImportoRitenuta'];

            // TODO: salvare in fattura
        }

        $causali = $dati_generali['Causale'];
        if (!empty($causali)) {
            $note = '';
            foreach ($causali as $causale) {
                $note .= $causale;
            }
            $fattura->note = $note;
        }

        // Bollo
        $bollo = $dati_generali['DatiBollo'];
        if (!empty($bollo)) {
            $fattura->bollo = $bollo['ImportoBollo'];
        }

        $fattura->save();

        return $fattura;
    }

    public function getFattura()
    {
        return $this->fattura;
    }

    public function save($info = [])
    {
        $this->saveFattura($info['id_pagamento'], $info['id_segment'], $info['id_tipo']);

        $this->saveRighe($info['articoli'], $info['iva'], $info['conto'], $info['movimentazione']);

        $this->saveAllegati();

        return $this->getFattura()->id;
    }

    protected function forceArray($result)
    {
        $result = isset($result[0]) ? $result : [$result];

        return $result;
    }
}
