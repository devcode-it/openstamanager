<?php

namespace Modules\Anagrafiche;

use Common\Model;
use Modules\Fatture\Fattura;
use Settings;
use Traits\RecordTrait;
use Util\Generator;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anagrafica extends Model
{
    use RecordTrait, SoftDeletes;

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
     * @param array  $tipologie
     *
     * @return self
     */
    public static function build($ragione_sociale, $nome = '', $cognome = '', array $tipologie = [])
    {
        $model = parent::build();

        $model->ragione_sociale = $ragione_sociale;

        $model->nome = $nome;
        $model->cognome = $cognome;

        $model->codice = static::getNextCodice();
        $model->id_ritenuta_acconto_vendite = setting("Percentuale ritenuta d'acconto");
        $model->save();

        $model->tipologie = $tipologie;
        $model->save();

        return $model;
    }

    public static function fixAzienda(Anagrafica $anagrafica)
    {
        Settings::setValue('Azienda predefinita', $anagrafica->id);
    }

    public static function fixCliente(Anagrafica $anagrafica)
    {
        $database = database();

        // Creo il relativo conto nel partitario se non esiste
        if (empty($anagrafica->idconto_cliente)) {
            // Calcolo prossimo numero cliente
            $rs = $database->fetchArray("SELECT MAX(CAST(co_pianodeiconti3.numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti2.descrizione='Crediti clienti e crediti diversi'");
            $new_numero = $rs[0]['max_numero'] + 1;
            $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

            $database->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare($anagrafica->ragione_sociale).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Crediti clienti e crediti diversi'), 1, 1)");
            $idconto = $database->lastInsertedID();

            // Collegamento conto
            $anagrafica->idconto_cliente = $idconto;
            $anagrafica->save();
        }
    }

    public static function fixFornitore(Anagrafica $anagrafica)
    {
        $database = database();

        // Creo il relativo conto nel partitario se non esiste
        if (empty($anagrafica->idconto_fornitore)) {
            // Calcolo prossimo numero cliente
            $rs = $database->fetchArray("SELECT MAX(CAST(co_pianodeiconti3.numero AS UNSIGNED)) AS max_numero FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti2.descrizione='Debiti fornitori e debiti diversi'");
            $new_numero = $rs[0]['max_numero'] + 1;
            $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

            $database->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare($anagrafica->ragione_sociale).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Debiti fornitori e debiti diversi'), 1, 1)");
            $idconto = $database->lastInsertedID();

            // Collegamento conto
            $anagrafica->idconto_fornitore = $idconto;
            $anagrafica->save();
        }
    }

    public static function fixTecnico(Anagrafica $anagrafica)
    {
        // Copio già le tariffe per le varie attività
        $result = database()->query('INSERT INTO in_tariffe(idtecnico, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico) SELECT '.prepare($anagrafica->id).', idtipointervento, costo_orario, costo_km, costo_diritto_chiamata, costo_orario_tecnico, costo_km_tecnico, costo_diritto_chiamata_tecnico FROM in_tipiintervento');

        if (!$result) {
            flash()->error(tr("Errore durante l'importazione tariffe!"));
        }
    }

    /**
     * Aggiorna la tipologia dell'anagrafica.
     *
     * @param array $tipologie
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
        return $this->tipi()->get()->search(function ($item, $key) {
            return $item->descrizione == 'Azienda';
        }) !== false;
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
        return $this->piva;
    }

    public function setPartitaIvaAttribute($value)
    {
        $this->attributes['piva'] = trim(strtoupper($value));
    }

    public function setNomeAttribute($value)
    {
        $this->attributes['nome'] = trim($value);

        $this->fixRagioneSociale();
    }

    public function setCognomeAttribute($value)
    {
        $this->attributes['cognome'] = trim($value);

        $this->fixRagioneSociale();
    }

    public function setCodiceFiscaleAttribute($value)
    {
        $this->attributes['codice_fiscale'] = trim(strtoupper($value));
    }

    public function setCodiceDestinatarioAttribute($value)
    {
        if ($this->tipo == 'Privato' || in_array($value, ['999999', '0000000']) || $this->sedeLegale->nazione->iso2 != 'IT') {
            $codice_destinatario = '';
        } else {
            $codice_destinatario = $value;
        }

        $this->attributes['codice_destinatario'] = trim(strtoupper($codice_destinatario));
    }

    public function tipi()
    {
        return $this->belongsToMany(Tipo::class, 'an_tipianagrafiche_anagrafiche', 'idanagrafica', 'idtipoanagrafica');
    }

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idanagrafica');
    }

    public function nazione()
    {
        return $this->belongsTo(Nazione::class, 'id_nazione');
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

    protected function fixRagioneSociale()
    {
        if (!empty($this->cognome) || !empty($this->nome)) {
            $this->ragione_sociale = $this->cognome.' '.$this->nome;
        }
    }
}
