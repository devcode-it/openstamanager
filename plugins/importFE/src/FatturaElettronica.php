<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

            $plugins = $module->plugins;
            if (!empty($plugins)) {
                $plugin = $plugins->first(function ($value, $key) {
                    return $value->name == 'Fatturazione Elettronica';
                });

                self::$directory = DOCROOT.'/'.$plugin->upload_directory;
            }
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
            $manager = new FatturaOrdinaria($name);

            $tipo = $manager->getBody()['DatiGenerali']['DatiGeneraliDocumento']['TipoDocumento'];
            if ($tipo == 'TD06') {
                $manager = new Parcella($name);
            }
        } catch (UnexpectedValueException $e) {
            $manager = new FatturaSemplificata($name);
        }

        return $manager;
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
        Uploads::upload($this->file, array_merge($info, [
            'name' => tr('Fattura Elettronica'),
            'original' => basename($this->file),
        ]));
    }

    public function findAnagrafica($type = 'Fornitore')
    {
        $info = $this->getAnagrafe();

        $tipologia = TipoAnagrafica::where('descrizione', $type)->first();

        $anagrafica = Anagrafica::whereHas('tipi', function ($query) use ($tipologia) {
            $query->where('an_tipianagrafiche.idtipoanagrafica', '=', $tipologia->id);
        });

        if (!empty($info['partita_iva']) && !empty($info['codice_fiscale'])) {
            $anagrafica->where('piva', $info['partita_iva'])
                ->orWhere('codice_fiscale', $info['codice_fiscale']);
        } elseif (!empty($info['codice_fiscale'])) {
            $anagrafica->where('codice_fiscale', $info['codice_fiscale']);
        } elseif (!empty($info['partita_iva'])) {
            $anagrafica->where('piva', $info['partita_iva']);
        }

        return $anagrafica->first();
    }

    /**
     * Restituisce l'anagrafica collegata alla fattura, eventualmente generandola con i dati forniti.
     *
     * @param string $type
     *
     * @return Anagrafica
     */
    public function saveAnagrafica($type = 'Fornitore')
    {
        $anagrafica = $this->findAnagrafica($type);

        if (!empty($anagrafica)) {
            return $anagrafica;
        }

        $info = $this->getAnagrafe();

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
        $sede->citta = $info['sede']['citta'];
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
     * @param int    $id_pagamento
     * @param int    $id_sezionale
     * @param int    $id_tipo
     * @param string $data_registrazione
     * @param int    $ref_fattura
     *
     * @return Fattura
     */
    public function saveFattura($id_pagamento, $id_sezionale, $id_tipo, $data_registrazione, $ref_fattura)
    {
        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];

        $fattura = $this->prepareFattura($id_tipo, $data, $id_sezionale, $ref_fattura);
        $this->fattura = $fattura;

        $numero_esterno = $dati_generali['Numero'];
        $progressivo_invio = $this->getHeader()['DatiTrasmissione']['ProgressivoInvio'];

        $fattura->progressivo_invio = $progressivo_invio;
        $fattura->numero_esterno = $numero_esterno;
        $fattura->idpagamento = $id_pagamento;

        // Riferimento per nota di credito e debito
        $fattura->ref_documento = $ref_fattura ?: null;

        // Per il destinatario, la data di registrazione della fattura assume grande rilievo ai fini IVA, poiché determina la decorrenza dei termini per poter esercitare il diritto alla detrazione.
        // La data di ricezione della fattura è contenuta all’interno della “ricevuta di consegna” visibile al trasmittente della stessa.
        $fattura->data_registrazione = $data_registrazione;
        $fattura->data_competenza = $fattura->data_registrazione;

        $stato_documento = StatoFattura::where('descrizione', 'Emessa')->first();
        $fattura->stato()->associate($stato_documento);

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

        // Fix generazione idsede
        $fattura->refresh();

        return $fattura;
    }

    public function getFattura()
    {
        return $this->fattura;
    }

    public function save($info = [])
    {
        $this->saveFattura($info['id_pagamento'], $info['id_segment'], $info['id_tipo'], $info['data_registrazione'], $info['ref_fattura']);

        $this->saveRighe($info['articoli'], $info['iva'], $info['conto'], $info['movimentazione'], $info['crea_articoli'], $info['tipo_riga_riferimento'], $info['id_riga_riferimento']);

        $this->saveAllegati();

        return $this->getFattura()->id;
    }

    protected function prepareFattura($id_tipo, $data, $id_sezionale, $ref_fattura)
    {
        $anagrafica = $this->saveAnagrafica();

        $tipo = TipoFattura::where('id', $id_tipo)->first();

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_sezionale);
        $this->fattura = $fattura;

        // Riferimento per nota di credito e debito
        $fattura->ref_documento = $ref_fattura ?: null;

        return $fattura;
    }

    protected function forceArray($result)
    {
        $result = isset($result[0]) ? $result : [$result];

        return $result;
    }
}
