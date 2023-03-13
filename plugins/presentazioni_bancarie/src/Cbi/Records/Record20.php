<?php

namespace Plugins\PresentazioniBancarie\Cbi\Records;

/**
 * Classe dedicata alla gestione dei dati per il record 20 del formato CBI.
 *
 * @property string numero_progressivo Numero progressivo della ricevuta, uguale a quello indicato per il record 14 della disposizione.
 * @property string descrizione_creditore_1 Descrizione del creditore (24 caratteri alfanumerici): ragione sociale
 * @property string descrizione_creditore_2 Descrizione del creditore (24 caratteri alfanumerici): indirizzo
 * @property string descrizione_creditore_3 Descrizione del creditore (24 caratteri alfanumerici): citta
 * @property string descrizione_creditore_4 Descrizione del creditore (24 caratteri alfanumerici): codice fiscale o partita iva -> allineamento a dx (vedi http://www.m8k.org/tracciatocbinet)
 */
class Record20 extends BaseRecord
{
    public static $struttura = [
        'numero_progressivo' => [
            'inizio' => 4,
            'dimensione' => 7,
            'tipo' => 'numeric',
        ],
        'descrizione_creditore_1' => [
            'inizio' => 11,
            'dimensione' => 24,
            'tipo' => 'string',
        ],
        'descrizione_creditore_2' => [
            'inizio' => 35,
            'dimensione' => 24,
            'tipo' => 'string',
        ],
        'descrizione_creditore_3' => [
            'inizio' => 59,
            'dimensione' => 24,
            'tipo' => 'string',
        ],
        'descrizione_creditore_4' => [
            'inizio' => 83,
            'dimensione' => 24,
            'tipo' => 'string',
            'forzaPadding' => STR_PAD_LEFT
        ],
    ];

    public static function getStruttura(): array
    {
        return static::$struttura;
    }

    public static function getCodice(): string
    {
        return '20';
    }
}
