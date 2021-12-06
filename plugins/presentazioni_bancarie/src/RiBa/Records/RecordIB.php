<?php

namespace Plugins\PresentazioniBancarie\RiBa\Records;

/**
 * Classe dedicata alla gestione dei dati per il record IB del formato CBI.
 *
 * @property string codice_sia_mittente Codice assegnato dalla Sia all'Azienda Mittente; deve essere censito in associazione alla Banca Proponente presso il Centro Applicativo codice_sia_mittente.
 * @property string abi_assuntrice Codice ABI della banca assuntrice cui devono essere inviate le disposizioni; deve essere presente nella tabella Centri Applicativi in associazione al Centro Applicativo destinatario del flusso.
 * @property string data_creazione Data di creazione del 'flusso' da parte dell'Azienda codice_sia_mittente nel formato GGMMAA.
 * @property string nome_supporto Campo di libera composizione da parte dell'Azienda Mittente; dev'essere univoco nell'ambito della data di creazione e a parità di codice_sia_mittente e abi_assuntrice.
 * @property string campo_a_disposizione Campo a disposizione dell'Azienda codice_sia_mittente.
 * @property string tipo_flusso Assume il valore: "1" = operazioni generate nell’ambito di attività Market Place.
 * @property string qualificatore_flusso Assume il valore fisso "$".
 * @property string soggetto_veicolare Se i due campi tipo_flusso e qualificatore_flusso sono valorizzati con i valori previsti, deve essere indicato il codice ABI della Banca Gateway MP.
 * @property string codice_divisa Assume il valore fisso "E" (Euro).
 * @property string centro_applicativo Questo campo è di interesse soltanto della tratta tra Centri Applicativi. Codice ABI del Centro Applicativo destinatario del supporto.
 */
class RecordIB extends BaseRecord
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
        'tipo_flusso' => [
            'inizio' => 105,
            'dimensione' => 1,
            'tipo' => 'string',
        ],
        'qualificatore_flusso' => [
            'inizio' => 106,
            'dimensione' => 1,
            'tipo' => 'constant',
            'valore' => '$',
        ],
        'soggetto_veicolare' => [
            'inizio' => 107,
            'dimensione' => 5,
            'tipo' => 'string',
        ],
        'codice_divisa' => [
            'inizio' => 114,
            'dimensione' => 1,
            'tipo' => 'constant',
            'valore' => 'E',
        ],
        'centro_applicativo' => [
            'inizio' => 116,
            'dimensione' => 5,
            'tipo' => 'string',
        ],
    ];

    public static function getStruttura(): array
    {
        return static::$struttura;
    }

    public static function getCodice(): string
    {
        return 'IB';
    }
}
