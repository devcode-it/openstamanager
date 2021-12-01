<?php

namespace Plugins\PresentazioniBancarie\RiBa\Records;

/**
 * Classe dedicata alla gestione dei dati per il record 30 del formato CBI.
 *
 * @property string numero_progressivo Numero progressivo della ricevuta, uguale a quello indicato per il record 14 della disposizione.
 * @property string descrizione_debitore_1 Descrizione del debitore (30 caratteri alfanumerici).
 * @property string descrizione_debitore_2 Descrizione del debitore (30 caratteri alfanumerici).
 * @property string codice_fiscale_debitore Codice fiscale del cliente debitore; il controllo è di validità; pertanto va verificata la presenza del CIN e la sua correttezza. Il campo non è sottoposto ad alcun controllo né di presenza né formale sulla validità nel caso in cui il codice ABI della Banca domiciliataria (pos. 70-74, rec. 14) sia uno dei seguenti: 03034 – 03145 – 03171 - 03178 - 03195 – 03225 - 03530 – 06067 – 08540 - 3262 1.
 */
class Record30 extends BaseRecord
{
    public static $struttura = [
        'numero_progressivo' => [
            'inizio' => 4,
            'dimensione' => 7,
            'tipo' => 'numeric',
        ],
        'descrizione_debitore_1' => [
            'inizio' => 11,
            'dimensione' => 30,
            'tipo' => 'string',
        ],
        'descrizione_debitore_2' => [
            'inizio' => 41,
            'dimensione' => 30,
            'tipo' => 'string',
        ],
        'codice_fiscale_debitore' => [
            'inizio' => 71,
            'dimensione' => 16,
            'tipo' => 'string',
        ],
    ];

    public static function getStruttura(): array
    {
        return static::$struttura;
    }

    public static function getCodice(): string
    {
        return '30';
    }
}
