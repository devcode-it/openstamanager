<?php

namespace Plugins\PresentazioniBancarie\Cbi\Records;

/**
 * Classe dedicata alla gestione dei dati per il record 40 del formato CBI.
 *
 * @property string numero_progressivo Numero progressivo della ricevuta, uguale a quello indicato per il record 14 della disposizione.
 * @property string indirizzo_debitore Via, numero civico e/o nome della frazione.
 * @property string cap_debitore Codice di avviamento postale.
 * @property string comune_debitore Comune del debitore.
 * @property string provincia_debitore Sigla della provincia del debitore.
 * @property string banca_domiciliataria Banca/sportello domiciliataria: eventuale denominazione in chiaro della banca/sportello domiciliataria/o.
 */
class Record40 extends BaseRecord
{
    public static $struttura = [
        'numero_progressivo' => [
            'inizio' => 4,
            'dimensione' => 7,
            'tipo' => 'numeric',
        ],
        'indirizzo_debitore' => [
            'inizio' => 11,
            'dimensione' => 30,
            'tipo' => 'string',
        ],
        'cap_debitore' => [
            'inizio' => 41,
            'dimensione' => 5,
            'tipo' => 'string',
        ],
        'comune_debitore' => [
            'inizio' => 46,
            'dimensione' => 23,
            'tipo' => 'string',
        ],
        'provincia_debitore' => [
            'inizio' => 69,
            'dimensione' => 2,
            'tipo' => 'string',
        ],
        'banca_domiciliataria' => [
            'inizio' => 71,
            'dimensione' => 50,
            'tipo' => 'string',
        ],
    ];

    public static function getStruttura(): array
    {
        return static::$struttura;
    }

    public static function getCodice(): string
    {
        return '40';
    }
}
