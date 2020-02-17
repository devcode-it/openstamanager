<?php

namespace Modules\Contratti;

use Carbon\Carbon;
use Common\Components\Description;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Plugins\PianificazioneInterventi\Promemoria;
use Traits\RecordTrait;
use Util\Generator;

class Contratto extends Document
{
    use RecordTrait;

    protected $table = 'co_contratti';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'data_conclusione',
        'data_accettazione',
    ];

    /**
     * Crea un nuovo contratto.
     *
     * @param string $nome
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, $nome)
    {
        $model = parent::build();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();

        $id_anagrafica = $anagrafica->id;
        $id_agente = $anagrafica->idagente;

        $id_pagamento = $anagrafica->idpagamento_vendite;
        if (empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        $model->anagrafica()->associate($anagrafica);
        $model->stato()->associate($stato_documento);

        $model->numero = static::getNextNumero();

        $model->nome = $nome;
        $model->data_bozza = Carbon::now();

        if (!empty($id_agente)) {
            $model->idagente = $id_agente;
        }

        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        // Salvataggio delle informazioni
        $model->save();

        return $model;
    }

    public function fixTipiSessioni()
    {
        $database = database();

        $presenti = $database->fetchArray('SELECT idtipointervento AS id FROM co_contratti_tipiintervento WHERE idcontratto = '.prepare($this->id));

        // Aggiunta associazioni costi unitari al contratto
        $tipi = TipoSessione::whereNotIn('idtipointervento', array_column($presenti, 'id'))->get();

        foreach ($tipi as $tipo) {
            $database->insert('co_contratti_tipiintervento', [
                'idcontratto' => $this->id,
                'idtipointervento' => $tipo->id,
                'costo_ore' => $tipo->costo_orario,
                'costo_km' => $tipo->costo_km,
                'costo_dirittochiamata' => $tipo->costo_diritto_chiamata,
                'costo_ore_tecnico' => $tipo->costo_orario_tecnico,
                'costo_km_tecnico' => $tipo->costo_km_tecnico,
                'costo_dirittochiamata_tecnico' => $tipo->costo_diritto_chiamata_tecnico,
            ]);
        }
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Contratti';
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

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idcontratto');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idcontratto');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idcontratto');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idcontratto');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_contratto');
    }

    public function promemoria()
    {
        return $this->hasMany(Promemoria::class, 'idcontratto');
    }

    public function fixBudget()
    {
        $this->budget = $this->totale_imponibile ?: 0;
    }

    public function save(array $options = [])
    {
        $this->fixBudget();

        $result = parent::save($options);

        $this->fixTipiSessioni();

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

        // Impostazione del nuovo stato
        if ($qta_evasa == 0) {
            $descrizione = 'In lavorazione';
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
     * Calcola il nuovo numero di contratto.
     *
     * @return string
     */
    public static function getNextNumero()
    {
        $maschera = setting('Formato codice contratti');

        $ultimo = Generator::getPreviousFrom($maschera, 'co_contratti', 'numero');
        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
    }
}
