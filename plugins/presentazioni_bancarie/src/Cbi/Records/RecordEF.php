<?php

namespace Plugins\PresentazioniBancarie\Cbi\Records;

/**
 * Classe dedicata alla gestione dei dati per il record EF del formato CBI.
 *
 * @property string codice_sia_mittente Codice assegnato dalla Sia all'Azienda Mittente; deve essere censito in associazione alla Banca Proponente presso il Centro Applicativo mittente.
 * @property string abi_assuntrice Codice ABI della banca assuntrice cui devono essere inviate le disposizioni; deve essere presente nella tabella Centri Applicativi in associazione al Centro Applicativo destinatario del flusso.
 * @property string data_creazione Data di creazione del 'flusso' da parte dell'Azienda mittente nel formato GGMMAA.
 * @property string nome_supporto Campo di libera composizione da parte dell'Azienda Mittente; dev'essere univoco nell'ambito della data di creazione e a parità di mittente e ricevente.
 * @property string campo_a_disposizione Campo a disposizione dell'Azienda mittente.
 * @property string numero_disposizioni Numero delle disposizioni (ricevute Ri.Ba. contenute nel flusso).
 * @property string totale_importi_negativi Importo totale – in centesimi di Euro - delle disposizioni contenute nel flusso.
 * @property string totale_importi_positivi Valorizzato con "zeri" per RiBa.
 * @property string numero_record Numero dei record che compongono il flusso (comprensivo dei record di testa e di coda).
 * @property string codice_divisa Assume il valore fisso "E" (Euro).
 * @property string giornata_applicativa Questo campo è di interesse soltanto nella tratta tra Centri Applicativi. Data della giornata applicativa in cui il supporto logico é stato elaborato presso il Centro Applicativo mittente (nel formato GGMMAA).
 */
class RecordEF extends BaseRecord
{
    public static $struttura = [
        'codice_sia_mittente' => [
            'inizio' => 4,
            'dimensione' => 5,
            'tipo' => 'numeric',
        ],
        'abi_assuntrice' => [
            'inizio' => 9,
            'dimensione' => 5,
            'tipo' => 'numeric',
        ],
        'data_creazione' => [
            'inizio' => 14,
            'dimensione' => 6,
            'tipo' => 'string',
        ],
        'nome_supporto' => [
            'inizio' => 20,
            'dimensione' => 20,
            'tipo' => 'string',
        ],
        'campo_a_disposizione' => [
            'inizio' => 40,
            'dimensione' => 6,
            'tipo' => 'string',
        ],
        'numero_disposizioni' => [
            'inizio' => 46,
            'dimensione' => 7,
            'tipo' => 'numeric',
        ],
        'totale_importi_negativi' => [
            'inizio' => 53,
            'dimensione' => 15,
            'tipo' => 'numeric',
        ],
        'totale_importi_positivi' => [
            'inizio' => 68,
            'dimensione' => 15,
            'tipo' => 'numeric',
        ],
        'numero_record' => [
            'inizio' => 83,
            'dimensione' => 7,
            'tipo' => 'numeric',
        ],
        'codice_divisa' => [
            'inizio' => 114,
            'dimensione' => 1,
            'tipo' => 'constant',
            'valore' => 'E',
        ],
        'giornata_applicativa' => [
            'inizio' => 115,
            'dimensione' => 6,
            'tipo' => 'string',
        ],
    ];

    public static function getStruttura(): array
    {
        return static::$struttura;
    }

    public static function getCodice(): string
    {
        return 'EF';
    }
}
