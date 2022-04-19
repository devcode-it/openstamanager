<?php

namespace Plugins\PresentazioniBancarie\Cbi\Records;

/**
 * Classe dedicata alla gestione dei dati per il record 70 del formato CBI.
 *
 * @property string numero_progressivo Numero progressivo della ricevuta, uguale a quello indicato per il record 14 della disposizione.
 * @property string indicatori_circuito Campo a disposizione per altri circuiti (Teleincassi, Reteincassi, ecc.) da valorizzare secondo gli standard propri di ogni circuito.
 * @property string tipo_documento_per_debitore Indica il tipo di documento da rilasciare al debitore al momento dell'esazione dell'incasso; assume i seguenti valori: 1 = ricevuta bancaria; 2 = Conferma d'ordine di bonifico; 0 o blank = il cliente chiede alla Banca di comportarsi secondo accordi bilaterali predefiniti.
 * @property string flag_richiesta_esito Assume i seguenti valori: 1 = é richiesta la notifica del pagato; 2 = non é richiesta la notifica del pagato; 0 o blank = il cliente chiede alla Banca di comportarsi secondo accordi bilaterali predefiniti.
 * @property string flag_stampa_avviso Indica chi é incaricato della stampa e invio dell'avviso di pagamento; può assumere i
 * @property string chiavi_controllo Campo a disposizione, valorizzabile dall'Azienda previ accordi diretti con la Banca Assuntrice.
 */
class Record70 extends BaseRecord
{
    public static $struttura = [
        'numero_progressivo' => [
            'inizio' => 4,
            'dimensione' => 7,
            'tipo' => 'numeric',
        ],
        'indicatori_circuito' => [
            'inizio' => 89,
            'dimensione' => 12,
            'tipo' => 'string',
        ],
        'tipo_documento_per_debitore' => [
            'inizio' => 101,
            'dimensione' => 1,
            'tipo' => 'string',
        ],
        'flag_richiesta_esito' => [
            'inizio' => 102,
            'dimensione' => 1,
            'tipo' => 'string',
        ],
        'flag_stampa_avviso' => [
            'inizio' => 103,
            'dimensione' => 1,
            'tipo' => 'string',
        ],
        'chiavi_controllo' => [
            'inizio' => 104,
            'dimensione' => 17,
            'tipo' => 'string',
        ],
    ];

    public static function getStruttura(): array
    {
        return static::$struttura;
    }

    public static function getCodice(): string
    {
        return '70';
    }
}
