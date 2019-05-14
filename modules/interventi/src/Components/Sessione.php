<?php

namespace Modules\Interventi\Components;

use Common\Model;
use Modules\Interventi\Intervento;

/**
 * Notazione: i costi sono rivolti all'azienda, i prezzi al cliente.
 *
 * @since 2.4.9
 */
class Sessione extends Model
{
    use RelationTrait;

    protected $table = 'in_interventi_tecnici';

    public function getParentID()
    {
        return 'idintervento';
    }

    public function parent()
    {
        return $this->belongsTo(Intervento::class, $this->getParentID());
    }

    // Costi per l'azienda

    /**
     * Restituisce il costo orario (per l'azienda) per la sessione del tecnico.
     *
     * @return float
     */
    public function getCostoOrarioAttribute()
    {
        return $this->attributes['prezzo_ore_unitario_tecnico'];
    }

    /**
     * Restituisce il costo del diritto di chiamata (per l'azienda) per la sessione del tecnico.
     *
     * @return float
     */
    public function getCostoDirittoChiamataAttribute()
    {
        return $this->attributes['prezzo_dirittochiamata_tecnico'];
    }

    /**
     * Restituisce il costo chilometrico (per l'azienda) del viaggio del tecnico.
     *
     * @return float
     */
    public function getCostoChilometricoAttribute()
    {
        return $this->attributes['prezzo_km_unitario_tecnico'];
    }

    /**
     * Restituisce il costo totale della manodopera escluso il diritto di chiamata (per l'azienda) per la sessione del tecnico.
     *
     * @return float
     */
    public function getCostoManodoperaAttribute()
    {
        return $this->costo_orario * $this->ore;
    }

    /**
     * Restituisce il costo totale (per l'azienda) del viaggio del tecnico.
     *
     * @return float
     */
    public function getCostoViaggioAttribute()
    {
        return $this->costo_chilometrico * $this->km;
    }

    // Prezzi per il cliente

    /**
     * Restituisce il prezzo del diritto di chiamata (per il cliente) per la sessione del tecnico.
     *
     * @return float
     */
    public function getPrezzoDirittoChiamataAttribute()
    {
        return $this->attributes['prezzo_dirittochiamata'];
    }

    /**
     * Restituisce il prezzo del diritto di chiamata (per il cliente) per la sessione del tecnico.
     *
     * @return float
     */
    public function getPrezzoChilometricoAttribute()
    {
        return $this->attributes['prezzo_km_unitario'];
    }

    /**
     * Restituisce il prezzo del diritto di chiamata (per il cliente) per la sessione del tecnico.
     *
     * @return float
     */
    public function getPrezzoOrarioAttribute()
    {
        return $this->attributes['prezzo_ore_unitario'];
    }

    /**
     * Restituisce il prezzo totale della manodopera escluso il diritto di chiamata (per il cliente) per la sessione del tecnico.
     *
     * @return float
     */
    public function getPrezzoManodoperaAttribute()
    {
        return $this->prezzo_orario * $this->ore;
    }

    /**
     * Restituisce lo sconto totale km in euro.
     *
     * @return float
     */
    public function getScontoTotaleManodoperaAttribute()
    {
        return calcola_sconto([
            'sconto' => $this->sconto_unitario,
            'prezzo' => $this->prezzo_manodopera,
            'tipo' => $this->tipo_sconto,
        ]);
    }

    /**
     * Restituisce il prezzo totale scontato (per il cliente) del viaggio del tecnico.
     *
     * @return float
     */
    public function getPrezzoManodoperaScontatoAttribute()
    {
        return $this->prezzo_manodopera - $this->sconto_totale_manodopera;
    }

    /**
     * Restituisce il prezzo totale (per il cliente) del viaggio del tecnico.
     *
     * @return float
     */
    public function getPrezzoViaggioAttribute()
    {
        return $this->prezzo_chilometrico * $this->km;
    }

    /**
     * Restituisce lo sconto totale km in euro.
     *
     * @return float
     */
    public function getScontoTotaleKmAttribute()
    {
        return calcola_sconto([
            'sconto' => $this->scontokm_unitario,
            'prezzo' => $this->prezzo_viaggio,
            'tipo' => $this->tipo_scontokm,
        ]);
    }

    /**
     * Restituisce il prezzo totale scontato (per il cliente) del viaggio del tecnico.
     *
     * @return float
     */
    public function getPrezzoViaggioScontatoAttribute()
    {
        return $this->prezzo_viaggio - $this->sconto_totale_km;
    }

    /**
     * Crea un nuova sessione collegata ad un intervento.
     *
     * @param Intervento $intervento
     *
     * @return self
     */
    public static function build(Intervento $intervento)
    {
        $model = parent::build($intervento);

        $model->parent()->associate($intervento);

        return $model;
    }
}
