<?php

namespace Plugins\PresentazioniBancarie\Cbi\Records;

/**
 * Classe dedicata alla gestione dei dati per il record 14 del formato CBI.
 *
 * @property string numero_progressivo Numero della disposizione all'interno del flusso. Inizia con 1 ed è progressivo di 1.
 * @property string data_pagamento Data di scadenza, nel formato GGMMAA,
 * @property string causale Assume valore fisso "30000".
 * @property string importo Importo della ricevuta in centesimi di Euro.
 * @property string segno assume valore fisso " - ".
 * @property string abi_assuntrice Codice ABI della banca assuntrice delle ricevute; deve corrispondere a quello presente sul record di testa.
 * @property string cab_assuntrice Codice CAB dello sportello della banca.
 * @property string conto_assuntrice Conto corrente che il cliente chiede di
 * @property string abi_domiciliataria Codice ABI della banca domiciliataria.
 * @property string cab_domiciliataria Codice CAB dello sportello della banca.
 * @property string codice_azienda_creditrice Codice SIA del cliente ordinante; tale codice, se presente, deve essere valorizzato su tutte le singole disposizioni contenute nel medesimo supporto logico, e deve contenere sempre il medesimo valore. Questo può essere diverso dal codice SIA dell’azienda mittente indicato sul record di testa, e non necessariamente è censito tra i codici SIA riportati nelle tabelle di routing dei Centri Applicativi (cfr. par.3.6.4 sez. I).
 * @property string tipo_codice_creditrice Assume il valore fisso "4".
 * @property string codice_cliente_debitore Codice con il quale il debitore è conosciuto dal creditore.
 * @property string flag_tipo_debitore Nel caso il debitore sia una Banca deve assumere il valore "B" (il codice ABI è indicato in pos. 70-74).
 * @property string codice_divisa Questo campo deve coincidere con quello
omonimo del record di testa.
 */
class Record14 extends BaseRecord
{
    public static $struttura = [
        'numero_progressivo' => [
            'inizio' => 4,
            'dimensione' => 7,
            'tipo' => 'numeric',
        ],
        'data_pagamento' => [
            'inizio' => 23,
            'dimensione' => 6,
            'tipo' => 'string',
        ],
        'causale' => [
            'inizio' => 29,
            'dimensione' => 5,
            'tipo' => 'constant',
            'valore' => '30000',
        ],
        'importo' => [
            'inizio' => 34,
            'dimensione' => 13,
            'tipo' => 'numeric',
        ],
        'segno' => [
            'inizio' => 47,
            'dimensione' => 1,
            'tipo' => 'constant',
            'valore' => '-',
        ],
        'abi_assuntrice' => [
            'inizio' => 48,
            'dimensione' => 5,
            'tipo' => 'numeric',
        ],
        'cab_assuntrice' => [
            'inizio' => 53,
            'dimensione' => 5,
            'tipo' => 'numeric',
        ],
        'conto_assuntrice' => [
            'inizio' => 58,
            'dimensione' => 12,
            'tipo' => 'numeric',
        ],
        'abi_domiciliataria' => [
            'inizio' => 70,
            'dimensione' => 5,
            'tipo' => 'numeric',
        ],
        'cab_domiciliataria' => [
            'inizio' => 75,
            'dimensione' => 5,
            'tipo' => 'numeric',
        ],
        'codice_azienda_creditrice' => [
            'inizio' => 92,
            'dimensione' => 5,
            'tipo' => 'string',
        ],
        'tipo_codice_creditrice' => [
            'inizio' => 97,
            'dimensione' => 1,
            'tipo' => 'constant',
            'valore' => '4',
        ],
        'codice_cliente_debitore' => [
            'inizio' => 98,
            'dimensione' => 16,
            'tipo' => 'string',
        ],
        'flag_tipo_debitore' => [
            'inizio' => 114,
            'dimensione' => 1,
            'tipo' => 'string',
        ],
        'codice_divisa' => [
            'inizio' => 120,
            'dimensione' => 1,
            'tipo' => 'constant',
            'valore' => 'E',
        ],
    ];

    public static function getStruttura(): array
    {
        return static::$struttura;
    }

    public static function getCodice(): string
    {
        return '14';
    }
}
