<?php

namespace Modules\Articoli;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules;
use Modules\Interventi\Components\Articolo as ArticoloIntervento;
use Uploads;

class Articolo extends Model
{
    use SoftDeletes;

    protected $table = 'mg_articoli';

    public static function build($codice, $nome, Categoria $categoria, Categoria $sottocategoria = null)
    {
        $model = parent::build();

        $model->codice = $codice;
        $model->descrizione = $nome;

        $model->abilita_serial = false;
        $model->attivo = true;

        $model->categoria()->associate($categoria);
        $model->sottocategoria()->associate($sottocategoria);

        $model->save();

        return $model;
    }

    /**
     * Funzione per inserire i movimenti di magazzino.
     *
     * @param $qta
     * @param null  $descrizone
     * @param null  $data
     * @param bool  $manuale
     * @param array $array
     *
     * @return bool
     */
    public function movimenta($qta, $descrizone = null, $data = null, $manuale = false, $array = [])
    {
        $id = $this->registra($qta, $descrizone, $data, $manuale, $array);

        if ($this->servizio == 0) {
            $this->qta += $qta;

            $this->save();
        }

        return $id;
    }

    /**
     * Funzione per registrare i movimenti di magazzino.
     *
     * @param $qta
     * @param null  $descrizone
     * @param null  $data
     * @param bool  $manuale
     * @param array $array
     *
     * @return bool
     */
    public function registra($qta, $descrizone = null, $data = null, $manuale = false, $array = [])
    {
        $id = false;

        if (empty($qta)) {
            return false;
        }

        // Movimento il magazzino solo se l'articolo non è un servizio
        if ($this->servizio == 0) {
            // Registrazione della movimentazione
            database()->insert('mg_movimenti', array_merge($array, [
                'idarticolo' => $this->id,
                'qta' => $qta,
                'movimento' => $descrizone,
                'data' => $data,
                'manuale' => $manuale,
            ]));
        }
        $id = database()->lastInsertedID();

        return $id;
    }

    // Attributi Eloquent

    public function getImageAttribute()
    {
        if (empty($this->immagine)) {
            return null;
        }

        $module = Modules::get($this->module);
        $fileinfo = Uploads::fileInfo($this->immagine);

        $directory = '/'.$module->upload_directory.'/';
        $image = $directory.$this->immagine;
        $image_thumbnail = $directory.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'];

        $url = file_exists(DOCROOT.$image_thumbnail) ? ROOTDIR.$image_thumbnail : ROOTDIR.$image;

        return $url;
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Articoli';
    }

    // Relazioni Eloquent

    public function articoli()
    {
        return $this->hasMany(ArticoloIntervento::class, 'idarticolo');
    }

    /**
     * Restituisce i movimenti di magazzino dell'articolo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Query\Builder
     */
    public function movimenti()
    {
        return $this->hasMany(Movimento::class, 'idarticolo');
    }

    /**
     * Restituisce i movimenti di magazzino dell'articolo raggruppati per documento relativo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Query\Builder
     */
    public function movimentiComposti()
    {
        return $this->movimenti()
            ->selectRaw('*, sum(qta) as qta_documento, IFNULL(reference_type, UUID()) as tipo_gruppo')
            ->groupBy('tipo_gruppo', 'reference_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function sottocategoria()
    {
        return $this->belongsTo(Categoria::class, 'id_sottocategoria');
    }
}
