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
        $this->registra($qta, $descrizone, $data, $manuale, $array);

        if ($this->servizio == 0) {
            $this->qta += $qta;

            $this->save();

            $this->movimentaRicorsivo($qta, $descrizone, $data, $manuale, $array);
        }

        return true;
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

        return true;
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

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function sottocategoria()
    {
        return $this->belongsTo(Categoria::class, 'id_sottocategoria');
    }

    /**
     * @version distinta_base
     *
     * @return mixed
     */
    public function componenti()
    {
        return $this->belongsToMany(Articolo::class, 'mg_articoli_distinte', 'id_articolo', 'id_figlio')->withPivot('qta');
    }

    public function parti()
    {
        return $this->belongsToMany(Articolo::class, 'mg_articoli_distinte', 'id_figlio', 'id_articolo')->withPivot('qta');
    }

    /**
     * Funzione per inserire i movimenti di magazzino.
     *
     * @version distinta_base
     *
     * @param $qta
     * @param null  $descrizone
     * @param null  $data
     * @param bool  $manuale
     * @param array $array
     *
     * @return bool
     */
    protected function movimentaRicorsivo($qta, $descrizone = null, $data = null, $manuale = false, $array = [])
    {
        $componenti = $this->componenti;

        $suffix = ' (di.ba.)';
        $descrizone = strpos($descrizone, $suffix) !== false ? $descrizone : $descrizone.$suffix;

        foreach ($componenti as $componente) {
            $qta_componente = $qta * $componente->pivot->qta;
            $componente->movimenta($qta_componente, $descrizone, $data, $manuale, $array);
        }
    }
}
