<?php

namespace Plugins\ReceiptFE;

use Modules;
use Modules\Fatture\Fattura;
use Plugins;
use UnexpectedValueException;
use Uploads;
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

    public static function store($filename, $content)
    {
        $directory = static::getImportDirectory();
        $file = $directory.'/'.$filename;

        directory($directory);
        file_put_contents($file, $content);

        return $filename;
    }

    public static function getImportDirectory()
    {
        if (!isset(self::$directory)) {
            $plugin = Plugins::get('Ricevute FE');

            self::$directory = DOCROOT.'/'.$plugin->upload_directory;
        }

        return self::$directory;
    }

    public function saveAllegato($codice)
    {
        $module = Modules::get('Fatture di vendita');

        $info = [
            'category' => tr('Fattura Elettronica'),
            'id_module' => $module->id,
            'id_record' => $this->fattura->id,
        ];

        // Registrazione XML come allegato
        Uploads::upload($this->file, array_merge($info, [
            'name' => tr('Ricevuta _TYPE_', [
                '_TYPE_' => $codice,
            ]),
            'original' => basename($this->file),
        ]));
    }

    public function saveStato($codice)
    {
        $fattura = $this->getFattura();

        // Modifica lo stato solo se la fattura non è già stata consegnata (per evitare problemi da doppi invii)
        // In realtà per le PA potrebbe esserci lo stato NE (che può contenere un esito positivo EC01 o negativo EC02) successivo alla RC,
        // quindi aggiungo eccezzione nel caso il nuovo codice della ricevuta sia NE.
        if ($fattura->codice_stato_fe == 'RC' && ($codice != 'EC01' || $codice != 'EC02')) {
            return;
        }

        // Processo la ricevuta e salvo data ricezione, codice e messaggio
        $descrizione = $this->xml['Destinatario']['Descrizione'];
        $data = $this->xml['DataOraRicezione'];

        $fattura->data_stato_fe = date('Y-m-d H:i:s', strtotime($data));
        $fattura->codice_stato_fe = $codice;
        $fattura->descrizione_ricevuta_fe = $descrizione;

        $fattura->save();
    }

    public function save()
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

        $this->saveAllegato($codice_nome);

        // In caso di Notifica Esito il codice è definito dal nodo <Esito> della ricevuta
        if ($codice_stato == 'NE') {
            $codice_stato = $this->xml['EsitoCommittente']['Esito'];
        }

        $this->saveStato($codice_stato);
    }

    public function getFattura()
    {
        return $this->fattura;
    }

    public function delete()
    {
        delete($this->file);
    }
}
