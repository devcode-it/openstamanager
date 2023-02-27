<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\Anagrafiche;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contratti\Contratto;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Plugins\DichiarazioniIntento\Dichiarazione;
use Settings;
use Traits\RecordTrait;
use Util\Generator;

class Anagrafica extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    use SoftDeletes;

    protected $table = 'an_anagrafiche';
    protected $primaryKey = 'idanagrafica';
    protected $module = 'Anagrafiche';

    protected $guarded = [];

    protected $appends = [
        'id',
        'partita_iva',
    ];

    protected $hidden = [
        'idanagrafica',
        'piva',
    ];

    /**
     * Crea una nuova anagrafica.
     *
     * @param string $ragione_sociale
     *
     * @return self
     */
    public static function build($ragione_sociale, $nome = '', $cognome = '', array $tipologie = [])
    {
        $model = new static();

        $model->ragione_sociale = $ragione_sociale;

        $model->nome = $nome;
        $model->cognome = $cognome;

        $model->codice = static::getNextCodice();
        $model->id_ritenuta_acconto_vendite = setting("Ritenuta d'acconto predefinita");
        $model->save();

        $model->tipologie = $tipologie;
        $model->save();

        return $model;
    }

    public static function fromTipo($type)
    {
        $tipologia = Tipo::where('descrizione', $type)->first();

        $anagrafiche = self::whereHas('tipi', function ($query) use ($tipologia) {
            $query->where('an_tipianagrafiche.idtipoanagrafica', '=', $tipologia->id);
        });

        return $anagrafiche;
    }

    public static function fixAzienda(Anagrafica $anagrafica)
    {
        Settings::setValue('Azienda predefinita', $anagrafica->id);
    }

    public static function fixCliente(Anagrafica $anagrafica)
    {
        // Creo il relativo conto nel partitario se non esiste
        if (empty($anagrafica->idconto_cliente)) {
            $id_conto = self::creaConto($anagrafica, 'idconto_cliente');

            // Collegamento conto
            $anagrafica->idconto_cliente = $id_conto;
            $anagrafica->save();
        }
    }

    public static function fixFornitore(Anagrafica $anagrafica)
    {
        // Creo il relativo conto nel partitario se non esiste
        if (empty($anagrafica->idconto_fornitore)) {
            $id_conto = self::creaConto($anagrafica, 'idconto_fornitore');

            // Collegamento conto
            $anagrafica->idconto_fornitore = $id_conto;
            $anagrafica->save();
        }
    }

    public static function fixTecnico(Anagrafica $anagrafica)
    {
        $database = database();

        $presenti = $database->fetchArray('SELECT idtipointervento AS id FROM in_tariffe WHERE idtecnico = '.prepare($anagrafica->id));

        // Aggiunta associazioni costi unitari al contratto
        $tipi = TipoSessione::whereNotIn('idtipointervento', array_column($presenti, 'id'))->get();

        foreach ($tipi as $tipo) {
            $database->insert('in_tariffe', [
                'idtecnico' => $anagrafica->id,
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
     * Aggiorna la tipologia dell'anagrafica.
     */
    public function setTipologieAttribute(array $tipologie)
    {
        if ($this->isAzienda()) {
            $tipologie[] = Tipo::where('descrizione', 'Azienda')->first()->id;
        }

        $tipologie = array_clean($tipologie);

        $previous = $this->tipi()->get();
        $this->tipi()->sync($tipologie);
        $actual = $this->tipi()->get();

        $diff = $actual->diff($previous);

        foreach ($diff as $tipo) {
            $method = 'fix'.$tipo->descrizione;
            if (method_exists($this, $method)) {
                self::$method($this);
            }
        }
    }

    /**
     * Controlla se l'anagrafica è di tipo 'Azienda'.
     *
     * @return bool
     */
    public function isAzienda()
    {
        return $this->isTipo('Azienda');
    }

    /**
     * Controlla se l'anagrafica è di tipo 'Azienda'.
     *
     * @return bool
     */
    public function isTipo($type)
    {
        return $this->tipi()->get()->search(function ($item, $key) use ($type) {
            return $item->descrizione == $type;
        }) !== false;
    }

    public function delete()
    {
        if (!$this->isAzienda()) {
            return parent::delete();
        }
    }

    public function save(array $options = [])
    {
        $this->fixRagioneSociale();

        return parent::save($options);
    }

    // Attributi Eloquent

    public function getModuleAttribute()
    {
        return 'Anagrafiche';
    }

    /**
     * Restituisce l'identificativo.
     *
     * @return int
     */
    public function getIdAttribute()
    {
        return $this->idanagrafica;
    }

    public function getPartitaIvaAttribute()
    {
        return $this->attributes['piva'];
    }

    public function setPartitaIvaAttribute($value)
    {
        if (in_array($value, ['99999999999', '00000000000'])) {
            $value = null;
        }

        $this->attributes['piva'] = trim(strtoupper($value));
    }

    public function setNomeAttribute($value)
    {
        $this->attributes['nome'] = trim($value);
    }

    public function setCognomeAttribute($value)
    {
        $this->attributes['cognome'] = trim($value);
    }

    public function setCodiceFiscaleAttribute($value)
    {
        $this->attributes['codice_fiscale'] = trim(strtoupper($value));
    }

    public function setCodiceDestinatarioAttribute($value)
    {
        if (!empty($this->sedeLegale->nazione) && $this->sedeLegale->nazione->iso2 != 'IT') {
            $value = '';
        }

        $this->attributes['codice_destinatario'] = trim(strtoupper($value));
    }

    /**
     * Restituisce la sede legale collegata.
     *
     * @return self
     */
    public function getSedeLegaleAttribute()
    {
        return $this;
    }

    // Relazioni Eloquent

    public function tipi()
    {
        return $this->belongsToMany(Tipo::class, 'an_tipianagrafiche_anagrafiche', 'idanagrafica', 'idtipoanagrafica');
    }

    public function sedi()
    {
        return $this->hasMany(Sede::class, 'idanagrafica');
    }

    public function nazione()
    {
        return $this->belongsTo(Nazione::class, 'id_nazione');
    }

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idanagrafica');
    }

    public function fattureVendita()
    {
        return $this->fatture()->vendita();
    }

    public function fattureAcquisto()
    {
        return $this->fatture()->acquisto();
    }

    public function ordini()
    {
        return $this->hasMany(Ordine::class, 'idanagrafica');
    }

    public function ddt()
    {
        return $this->hasMany(DDT::class, 'idanagrafica');
    }

    public function contratti()
    {
        return $this->hasMany(Contratto::class, 'idanagrafica');
    }

    public function preventivi()
    {
        return $this->hasMany(Preventivo::class, 'idanagrafica');
    }

    public function dichiarazioni()
    {
        return $this->hasMany(Dichiarazione::class, 'id_anagrafica');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'idanagrafica');
    }

    // Metodi statici

    /**
     * Calcola il nuovo codice di anagrafica.
     *
     * @return string
     */
    public static function getNextCodice()
    {
        // Recupero maschera per le anagrafiche
        $maschera = setting('Formato codice anagrafica');

        $ultimo = Generator::getPreviousFrom($maschera, 'an_anagrafiche', 'codice', [
            "codice != ''",
            'deleted_at IS NULL',
        ]);
        $codice = Generator::generate($maschera, $ultimo);

        return $codice;
    }

    protected static function creaConto(Anagrafica $anagrafica, $campo)
    {
        $categoria_conto_id = null;
        if ($campo == 'idconto_cliente') {
            $categoria_conto_id = setting('Conto di secondo livello per i crediti clienti');
        } else {
            $categoria_conto_id = setting('Conto di secondo livello per i debiti fornitori');
        }

        $database = database();

        // Query di base
        $table = $database->table('co_pianodeiconti3')
            ->where('idpianodeiconti2', '=', $categoria_conto_id);

        // Verifica su un possibile conto esistente ma non collegato
        if (!empty($anagrafica->ragione_sociale)) {
            $conto = (clone $table)
                ->where('descrizione', 'like', '%'.$anagrafica->ragione_sociale.'%')
                ->first();
            if (!empty($conto)) {
                $anagrafiche_collegate = Anagrafica::where($campo, '=', $conto->id)->count();
                $conto = $anagrafiche_collegate == 0 ? $conto : null;
            }

            // Collegamento a conto esistente
            if (!empty($conto)) {
                return $conto->id;
            }
        }

        // Calcolo prossimo numero cliente
        $numero = (clone $table)
            ->selectRaw('MAX(CAST(numero AS UNSIGNED)) AS max_numero')
            ->first();
        $new_numero = $numero->max_numero + 1;
        $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

        // Creazione del conto
        $id_conto = $database->table('co_pianodeiconti3')
            ->insertGetId([
                'numero' => $new_numero,
                'descrizione' => $anagrafica->ragione_sociale ?: 'N.D.',
                'idpianodeiconti2' => $categoria_conto_id,
            ]);

        return $id_conto;
    }

    protected function aggiornaConto()
    {
        $database = database();

        if ($this->isTipo('Cliente')) {
            $database->update('co_pianodeiconti3', ['descrizione' => $this->ragione_sociale], ['id' => $this->idconto_cliente]);
        } 
        
        if ($this->isTipo('Fornitore')) {
            $database->update('co_pianodeiconti3', ['descrizione' => $this->ragione_sociale], ['id' => $this->idconto_fornitore]);
        }
    }
    protected function fixRagioneSociale()
    {
        if (!empty($this->cognome) || !empty($this->nome)) {
            $this->ragione_sociale = $this->cognome.' '.$this->nome;
        }
        $this->aggiornaConto();
    }
}
