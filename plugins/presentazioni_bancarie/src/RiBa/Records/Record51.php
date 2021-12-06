<?php

namespace Plugins\PresentazioniBancarie\RiBa\Records;

/**
 * Classe dedicata alla gestione dei dati per il record 51 del formato CBI.
 *
 * @property string numero_progressivo Numero progressivo della ricevuta, uguale a quello indicato per il record 14 della disposizione.
 * @property string numero_ricevuta Numero ricevuta attribuito dal creditore.
 * @property string denominazione_creditore Denominazione sociale del creditore in forma abbreviata.
 * @property string provincia_bollo_virtuale Provincia dell'Intendenza di Finanza che ha autorizzato il pagamento del bollo in modo virtuale.
 * @property string numero_autorizzazione_bollo_virtuale Numero dell'autorizzazione concessa dall'Intendenza di Finanza.
 * @property string data_autorizzazione_bollo_virtuale Data (nel formato GGMMAA) di concessione dell'autorizzazione da parte della Intendenza di Finanza.
 */
class Record51 extends BaseRecord
{
    public static $struttura = [
        'numero_progressivo' => [
            'inizio' => 4,
            'dimensione' => 7,
            'tipo' => 'numeric',
        ],
        'numero_ricevuta' => [
            'inizio' => 11,
            'dimensione' => 10,
            'tipo' => 'numeric',
        ],
        'denominazione_creditore' => [
            'inizio' => 21,
            'dimensione' => 20,
            'tipo' => 'string',
        ],
        'provincia_bollo_virtuale' => [
            'inizio' => 41,
            'dimensione' => 15,
            'tipo' => 'string',
        ],
        'numero_autorizzazione_bollo_virtuale' => [
            'inizio' => 56,
            'dimensione' => 10,
            'tipo' => 'string',
        ],
        'data_autorizzazione_bollo_virtuale' => [
            'inizio' => 66,
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
        return '51';
    }
}
