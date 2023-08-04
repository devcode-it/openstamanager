<?php

namespace Modules\CombinazioniArticoli;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Articoli\Articolo;
use Modules\AttributiCombinazioni\Attributo;
use Modules\AttributiCombinazioni\ValoreAttributo;
use Traits\RecordTrait;

class Combinazione extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;
    use RecordTrait;

    protected $table = 'mg_combinazioni';

    /**
     * Elenco dei campi della combinazione da sincronizzare da e verso gli Articoli della combinazione.
     *
     * @var string[]
     */
    protected static $campi_combinazione = [
        'id_categoria',
        'id_sottocategoria',
    ];

    /**
     * Elenco dei campi degli Articoli da sincronizzare da e verso gli Articoli della combinazione.
     *
     * @var string[]
     */
    protected static $campi_varianti = [
        'id_categoria',
        'id_sottocategoria',
        //'descrizione',
        'um',
        'gg_garanzia',
        'servizio',
    ];

    public function delete()
    {
        $database = database();

        // Rimozione articoli collegati
        $articoli = $this->articoli;
        foreach ($articoli as $articolo) {
            $articolo->delete();
            $database->query('DELETE FROM mg_articolo_attributo WHERE id_articolo='.prepare($articolo['id']));
        }

        return parent::delete();
    }

    public function save(array $options = [])
    {
        $result = parent::save($options);

        // Sincronizzazione dei campi condivisi con la Combinazione
        $sincro = collect($this->toArray())->filter(function ($value, $key) {
            return in_array($key, self::$campi_combinazione);
        });
        $this->sincronizzaCampi($sincro->toArray());

        return $result;
    }

    public function getModuleAttribute()
    {
        return 'Combinazioni';
    }

    /**
     * Metodo per generare dinamicamente una variante dell'articolo per la combinazione corrente.
     *
     * @param $valori_attributi
     */
    public function generaVariante($valori_attributi, $id_articolo = null)
    {
        $database = database();

        // Generazione nome variante
        $variante = ValoreAttributo::findMany($valori_attributi)->pluck('nome')->all();

        // Generazione Articolo di base
        if (empty($id_articolo)) {
            $articoli = $this->articoli;
            if ($articoli->isEmpty()) {
                $articolo = Articolo::build($this->nome, $this->nome);
                $articolo->id_combinazione = $this->id;

                $articolo->id_categoria = $this->id_categoria;
                $articolo->id_sottocategoria = $this->id_sottocategoria;
            } else {
                $articolo_base = $articoli->first();
                $articolo = $articolo_base->replicate();

                $nome_immagine = $articolo_base->immagine_upload->name;
                $allegato = $articolo_base->uploads()->where('name', $nome_immagine)->first();

                if (!empty($allegato)) {
                    $allegato->copia([
                        'id_module' => $articolo->getModule()->id,
                        'id_record' => $articolo->id,
                    ]);

                    $articolo->immagine = $articolo->uploads()->where('name', $nome_immagine)->first()->filename;
                    $articolo->save();
                }
            }
            $articolo->descrizione = $this->nome.' ['.implode(', ', $variante).']';
            $articolo->codice = $this->codice.'-'.implode('|', $variante);
            $articolo->save();
        }

        // Uso di un articolo già esistente
        else {
            $articolo = Articolo::find($id_articolo);
            $articolo->id_combinazione = $this->id;
            $articolo->save();
        }

        // Associazione valori della variante
        foreach ($valori_attributi as $id => $id_valore) {
            $database->insert('mg_articolo_attributo', [
                'id_articolo' => $articolo->id,
                'id_valore' => $id_valore,
            ]);
        }
    }

    /**
     * Metodo per la generazione di tutte le varianti disponibili per la combinazione corrente.
     */
    public function generaTutto()
    {
        if ($this->articoli()->count() !== 0) {
            return;
        }

        // Individuazione valori disponibili per gli attributi
        $valori = [];
        $attributi = $this->attributi;
        foreach ($attributi as $attributo) {
            $valori[] = $attributo->valori->pluck('id')->all();
        }

        // Generazione di tutte le combinazioni
        $varianti = cartesian($valori);

        // Generazione delle singole varianti
        foreach ($varianti as $variante) {
            $this->generaVariante($variante);
        }
    }

    /**
     * Funzione dedicata a mantenere sincronizzati i campi condivisi delle varianti di uno specifico articolo.
     */
    public static function sincronizzaVarianti(Articolo $articolo)
    {
        $combinazione = $articolo->combinazione;
        if (empty($combinazione)) {
            return;
        }

        $sincro = collect($articolo->toArray())->filter(function ($value, $key) {
            return in_array($key, self::$campi_varianti);
        });

        $combinazione->sincronizzaCampi($sincro->toArray());
    }

    /* Relazioni Eloquent */

    public function attributi()
    {
        return $this->belongsToMany(Attributo::class, 'mg_attributo_combinazione', 'id_combinazione', 'id_attributo')
            ->orderBy('order', 'ASC');
    }

    public function articoli()
    {
        return $this->hasMany(Articolo::class, 'id_combinazione');
    }

    /**
     * Funzione per sincronizzare i campi condivisi dagli Articoli di tipo Variante.
     *
     * @param $values
     */
    protected function sincronizzaCampi($values)
    {
        $articoli = $this->articoli->pluck('id')->all();
        if (empty($articoli)) {
            return;
        }

        // Aggiornamento dati varianti
        database()->table('mg_articoli')
            ->whereIn('id', $articoli)
            ->update($values);

        // Filtro campi combinazioni
        $combo = collect($values)->filter(function ($value, $key) {
            return in_array($key, self::$campi_combinazione);
        });

        // Aggiornamento dati combinazioni
        database()->table('mg_combinazioni')
            ->where('id', $this->id)
            ->update($combo->toArray());
    }
}
