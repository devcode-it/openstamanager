<?php

namespace Plugins\PresentazioniBancarie\Cbi\Records;

/**
 * Classe dedicata alla gestione dei dati per il record 50 del formato CBI.
 *
 * @property string numero_progressivo Numero progressivo della ricevuta, uguale a quello indicato per il record 14 della disposizione.
 * @property string riferimento_debito_1 Riferimenti al debito.
 * @property string riferimento_debito_2 Riferimenti al debito.
 * @property string partita_iva_o_codice_fiscale_creditore Codice fiscale/Partita IVA del creditore. Se il campo è valorizzato, il controllo è di validità pertanto va verificata la presenza del CIN e la sua correttezza. L’obbligatorietà viene meno nel caso in cui il campo 82 del tipo record 70 delle “Riba presentate da clientela non residente” sia valorizzato a 1.
 */
class Record50 extends BaseRecord
{
    public static $struttura = [
        'numero_progressivo' => [
            'inizio' => 4,
            'dimensione' => 7,
            'tipo' => 'numeric',
        ],
        'riferimento_debito_1' => [
            'inizio' => 11,
            'dimensione' => 40,
            'tipo' => 'string',
        ],
        'riferimento_debito_2' => [
            'inizio' => 51,
            'dimensione' => 40,
            'tipo' => 'string',
        ],
        'partita_iva_o_codice_fiscale_creditore' => [
            'inizio' => 101,
            'dimensione' => 16,
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
        return '50';
    }
}
