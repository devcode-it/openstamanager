<?php

namespace Modules\Preventivi;

use Carbon\Carbon;
use Common\Components\Description;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Traits\RecordTrait;
use Util\Generator;

class Preventivo extends Document
{
    use RecordTrait;

    protected $table = 'co_preventivi';

    /**
     * Crea un nuovo preventivo.
     *
     * @param string $nome
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, TipoSessione $tipo_sessione, $nome, $data_bozza, $id_sede)
    {
        $model = parent::build();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();

        $id_anagrafica = $anagrafica->id;
        $id_agente = $anagrafica->idagente;
        $id_pagamento = $anagrafica->idpagamento_vendite;

        $costo_orario = $tipo_sessione['costo_orario'];
        $costo_diritto_chiamata = $tipo_sessione['costo_diritto_chiamata'];

        $id_iva = setting('Iva predefinita');
        if (empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        $model->anagrafica()->associate($anagrafica);
        $model->stato()->associate($stato_documento);
        $model->tipoSessione()->associate($tipo_sessione);

        $model->numero = static::getNextNumero($data_bozza);

        // Salvataggio delle informazioni
        $model->nome = $nome;
        if (empty($data_bozza)) {
            $model->data_bozza = Carbon::now();
        } else {
            $model->data_bozza = $data_bozza;
        }
        $model->data_conclusione = Carbon::now()->addMonth();

        if (!empty($id_sede)) {
            $model->idsede = $id_sede;
        }

        if (!empty($id_agente)) {
            $model->idagente = $id_agente;
        }

        if (!empty($id_iva)) {
            $model->idiva = $id_iva;
        }
        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        $model->save();

        // Gestione delle revisioni
        $model->master_revision = $model->id;
        $model->default_revision = 1;

        $model->save();

        return $model;
    }

    // Attributi Eloquent

    public function getOreInterventiAttribute()
    {
        if (!isset($this->info['ore_interventi'])) {
            $sessioni = collect();

            $interventi = $this->interventi;
            foreach ($interventi as $intervento) {
                $sessioni = $sessioni->merge($intervento->sessioni);
            }

            $this->info['ore_interventi'] = $sessioni->sum('ore');
        }

        return $this->info['ore_interventi'];
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Preventivi';
    }

    public function getDirezioneAttribute()
    {
        return 'entrata';
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstato');
    }

    public function tipoSessione()
    {
        return $this->belongsTo(TipoSessione::class, 'idtipointervento');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idpreventivo');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idpreventivo');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idpreventivo');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idpreventivo');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_preventivo');
    }

    public function fixBudget()
    {
        $this->budget = $this->totale_imponibile ?: 0;
    }

    public function save(array $options = [])
    {
        $this->fixBudget();

        return parent::save($options);
    }

    public function delete()
    {
        $this->interventi()->update(['id_preventivo' => null]);
        $revision = $this->master_revision;

        $result = parent::delete();

        self::where('master_revision', $revision)->delete();

        return $result;
    }

    /**
     * Effettua un controllo sui campi del documento.
     * Viene richiamatp dalle modifiche alle righe del documento.
     */
    public function triggerEvasione(Description $trigger)
    {
        parent::triggerEvasione($trigger);

        $righe = $this->getRighe();

        $qta_evasa = $righe->sum('qta_evasa');
        $qta = $righe->sum('qta');
        $parziale = $qta != $qta_evasa;

        $stato_attuale = $this->stato;

        // Impostazione del nuovo stato
        if ($qta_evasa == 0) {
            $descrizione = 'In lavorazione';
            $codice_intervento = 'OK';
        } elseif (!in_array($stato_attuale->descrizione, ['Parzialmente fatturato', 'Fatturato']) && $trigger->parent instanceof Ordine) {
            $descrizione = $this->stato->descrizione;
            $codice_intervento = 'OK';
        } else {
            $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';
            $codice_intervento = 'FAT';
        }

        $stato = Stato::where('descrizione', $descrizione)->first();
        $this->stato()->associate($stato);
        $this->save();

        // Trasferimento degli interventi collegati
        $interventi = $this->interventi;
        $stato_intervento = \Modules\Interventi\Stato::where('codice', $codice_intervento)->first();
        foreach ($interventi as $intervento) {
            $intervento->stato()->associate($stato_intervento);
            $intervento->save();
        }
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di preventivo.
     *
     * @return string
     */
    public static function getNextNumero($data)
    {
        $maschera = setting('Formato codice preventivi');

        if ((strpos($maschera, 'YYYY') !== false) or (strpos($maschera, 'yy') !== false)) {
            $ultimo = Generator::getPreviousFrom($maschera, 'co_preventivi', 'numero', [
                'YEAR(data_bozza) = '.prepare(date('Y', strtotime($data))),
            ]);
        } else {
            $ultimo = Generator::getPreviousFrom($maschera, 'co_preventivi', 'numero');
        }

        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
    }
}
