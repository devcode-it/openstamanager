<?php

namespace Modules\Anagrafiche;

use Base\Model;
use Modules\Fatture\Fattura;
use Util\Generator;
use Settings;

class Anagrafica extends Model
{
    protected $table = 'an_anagrafiche';
    protected $primaryKey = 'idanagrafica';

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
    public static function new($ragione_sociale, array $tipologie = [])
    {
        $model = parent::new();

        $model->ragione_sociale = $ragione_sociale;
        $model->updateTipologie($tipologie);

        $ultimo = database()->fetchOne('SELECT codice FROM an_anagrafiche ORDER BY CAST(codice AS SIGNED) DESC LIMIT 1');
        $codice = Generator::generate(setting('Formato codice anagrafica'), $ultimo['codice']);

        $model->codice = $codice;
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

            $database->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare(post('ragione_sociale')).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Crediti clienti e crediti diversi'), 1, 1)");
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

            $database->query('INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, can_delete, can_edit) VALUES('.prepare($new_numero).', '.prepare(post('ragione_sociale')).", (SELECT id FROM co_pianodeiconti2 WHERE descrizione='Debiti fornitori e debiti diversi'), 1, 1)");
            $idconto = $database->lastInsertedID();

            // Collegamento conto
            $anagrafica->idconto_fornitore = $idconto;
            $anagrafica->save();
        }
    }

    public static function fixTecnico(Anagrafica $anagrafica)
    {
        // Copio già le tariffe per le varie attività
        if (in_array($id_tecnico, $idtipoanagrafica)) {
            $result = $database->query('INSERT INTO in_tariffe(idtecnico, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico) SELECT '.prepare($model->id).', idtipointervento, costo_orario, costo_km, costo_diritto_chiamata, costo_orario_tecnico, costo_km_tecnico, costo_diritto_chiamata_tecnico FROM in_tipiintervento');

            if (!$result) {
                flash()->error(tr("Errore durante l'importazione tariffe!"));
            }
        }
    }

    /**
     * Aggiorna la tipologia dell'anagrafica.
     *
     * @param array $tipologie
     */
    public function updateTipologie(array $tipologie)
    {
        if ($this->isAzienda()) {
            $tipologie[] = Tipo::where('descrizione', 'Azienda')->first()->id;
        }

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

    public function setCodiceAttribute($value)
    {
        if (self::where([
            ['codice', $value],
            [$this->primaryKey, '<>', $this->id],
        ])->count() == 0) {
            $this->attributes['codice'] = $value;
        }
    }

    public function getPartitaIvaAttribute()
    {
        return $this->piva;
    }

    public function setPartitaIvaAttribute($value)
    {
        $this->attributes['piva'] = trim(strtoupper($value));
    }

    public function setCodiceFiscaleAttribute($value)
    {
        $this->attributes['codice_fiscale'] = trim(strtoupper($value));
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
    public function sedeLegale()
    {
        return $this;
    }
}
