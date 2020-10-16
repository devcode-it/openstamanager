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

namespace Plugins\ReceiptFE;

use Models\Upload;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Plugins;
use UnexpectedValueException;
use Util\XML;
use Util\Zip;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.2
 */
class Ricevuta
{
    protected static $directory = null;

    /** @var array Percorso del file XML */
    protected $file = null;
    /** @var array XML della ricevuta */
    protected $xml = null;

    /** @var array XML della ricevuta */
    protected $fattura = null;

    public function __construct($name)
    {
        $file = static::getImportDirectory().'/'.$name;

        // Estrazione implicita per il formato ZIP
        if (ends_with($name, '.zip')) {
            $original_file = $file;

            $extraction_dir = static::getImportDirectory().'/tmp';
            Zip::extract($file, $extraction_dir);

            $name = basename($name, '.zip').'.xml';
            $file = static::getImportDirectory().'/'.$name;
            copy($extraction_dir.'/'.$name, $file);

            delete($original_file);
            delete($extraction_dir);
        }

        $this->file = $file;
        $this->xml = XML::readFile($this->file);

        $filename = explode('.', $name)[0];
        $pieces = explode('_', $filename);

        $progressivo_invio = $pieces[1];

        $this->fattura = Fattura::where([
            'progressivo_invio' => $progressivo_invio,
        ])->first();

        if (empty($this->fattura)) {
            throw new UnexpectedValueException();
        }
    }

    /**
     * Funzione per gestire in modo autonomo il download, l'importazione e il salvataggio di una specifica ricevuta identificata tramite nome.
     *
     * @param string $name
     * @param bool   $cambia_stato
     *
     * @return Fattura|null
     */
    public static function process($name, $cambia_stato = true)
    {
        Interaction::getReceipt($name);

        $fattura = null;
        try {
            $receipt = new Ricevuta($name);
            $receipt->save($cambia_stato);

            $fattura = $receipt->getFattura();

            $receipt->cleanup();

            Interaction::processReceipt($name);
        } catch (UnexpectedValueException $e) {
        }

        return $fattura;
    }

    /**
     * Salva il file indicato nella cartella temporanea per una futura elaborazione.
     *
     * @param string $filename
     * @param string $content
     *
     * @return string
     */
    public static function store($filename, $content)
    {
        $directory = static::getImportDirectory();
        $file = $directory.'/'.$filename;

        directory($directory);
        file_put_contents($file, $content);

        return $filename;
    }

    /**
     * Restituisce la cartella temporanea utilizzabile per il salvataggio della ricevuta.
     *
     * @return string|null
     */
    public static function getImportDirectory()
    {
        if (!isset(self::$directory)) {
            $plugin = Plugins::get('Ricevute FE');

            self::$directory = base_dir().'/'.$plugin->upload_directory;
        }

        return self::$directory;
    }

    /**
     * @param string $codice
     *
     * @return Upload|null
     */
    public function saveAllegato($codice)
    {
        $filename = basename($this->file);
        $fattura = $this->getFattura();

        // Controllo sulla presenza della stessa ricevuta
        $module = $fattura->getModule();
        $upload_esistente = $module
            ->uploads($fattura->id)
            ->where('original', $filename)
            ->first();
        if (!empty($upload_esistente)) {
            return $upload_esistente;
        }

        // Registrazione del file XML come allegato
        $upload = Upload::build($this->file, [
            'category' => tr('Fattura Elettronica'),
            'id_module' => $module->id,
            'id_record' => $fattura->id,
            'name' => tr('Ricevuta _TYPE_', [
                '_TYPE_' => $codice,
            ]),
            'original' => $filename,
        ]);

        return $upload;
    }

    /**
     * Aggiorna lo stato della fattura relativa alla ricevuta in modo tale da rispecchiare i dati richiesti.
     *
     * @param $codice
     * @param $id_allegato
     */
    public function saveStato($codice, $id_allegato)
    {
        $fattura = $this->getFattura();

        // Modifica lo stato solo se la fattura non è già stata consegnata (per evitare problemi da doppi invii)
        // In realtà per le PA potrebbe esserci lo stato NE (che può contenere un esito positivo EC01 o negativo EC02) successivo alla RC, quindi aggiungo eccezione nel caso il nuovo codice della ricevuta sia NE.
        if ($fattura->codice_stato_fe == 'RC' && ($codice != 'EC01' || $codice != 'EC02')) {
            return;
        }

        // Processo la ricevuta e salvo data ricezione, codice e messaggio
        $descrizione = $this->xml['Destinatario']['Descrizione'];
        $data = $this->xml['DataOraRicezione'];

        $fattura->data_stato_fe = $data ? date('Y-m-d H:i:s', strtotime($data)) : '';
        $fattura->codice_stato_fe = $codice;
        $fattura->descrizione_ricevuta_fe = $descrizione;
        $fattura->id_ricevuta_principale = $id_allegato;

        $fattura->save();
    }

    /**
     * Effettua le operazioni di salvataggio della ricevuta nella fattura relativa.
     */
    public function save($cambia_stato = true)
    {
        $name = basename($this->file);
        $filename = explode('.', $name)[0];
        $pieces = explode('_', $filename);
        $codice_stato = $pieces[2];

        // Individuazione codice per il nome dell'allegato
        $codice_nome = $codice_stato;
        if ($codice_nome == 'NS') {
            $lista_errori = $this->xml['ListaErrori'];
            $errore = $lista_errori[0] ?: $lista_errori;
            $codice_nome = $codice_nome.' - '.$errore['Errore']['Codice'];
        }

        $upload = $this->saveAllegato($codice_nome);

        // Correzione eventuale per lo stato della fattura in Bozza
        $fattura = $this->getFattura();
        if ($fattura->stato->descrizione == 'Bozza') {
            $stato_emessa = Stato::where('descrizione', 'Emessa')->first();
            $fattura->stato()->associate($stato_emessa);
            $fattura->save();
        }

        // Controllo per il cambio di stato FE
        if ($cambia_stato) {
            // In caso di Notifica Esito il codice è definito dal nodo <Esito> della ricevuta
            if ($codice_stato == 'NE') {
                $codice_stato = $this->xml['EsitoCommittente']['Esito'];
            }

            $this->saveStato($codice_stato, $upload->id);
        }
    }

    /**
     * Restituisce la fattura identificata per la ricevuta.
     *
     * @return Fattura|null
     */
    public function getFattura()
    {
        return $this->fattura;
    }

    /**
     * Rimuove i file temporanei relativi alla ricevuta.
     */
    public function cleanup()
    {
        delete($this->file);
    }
}
