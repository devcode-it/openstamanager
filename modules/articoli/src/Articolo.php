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

namespace Modules\Articoli;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\Module;
use Modules\AttributiCombinazioni\ValoreAttributo;
use Modules\CombinazioniArticoli\Combinazione;
use Modules\Interventi\Components\Articolo as ArticoloIntervento;
use Modules\Iva\Aliquota;
use Plugins\ListinoFornitori\DettaglioFornitore;
use Traits\RecordTrait;

class Articolo extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;
    use RecordTrait;

    protected $guarded = [
        'qta',
    ];

    protected $table = 'mg_articoli';

    protected static $translated_fields = [
        'name',
    ];

    public static function build($codice = null, ?Categoria $categoria = null, ?Categoria $sottocategoria = null)
    {
        $model = new static();

        $model->codice = $codice;
        $model->abilita_serial = false;
        $model->attivo = true;

        $model->categoria()->associate($categoria);
        $model->sottocategoria()->associate($sottocategoria);
        $model->save();

        return $model;
    }

    /**
     * Funzione per registrare un movimento del magazzino in relazione all'articolo corrente, modificando di conseguenza la quantità dell'articolo stesso.
     *
     * @param string $descrizone
     * @param string $data
     * @param bool   $manuale
     * @param array  $array
     *
     * @return bool
     */
    public function movimenta($qta, $descrizone = null, $data = null, $manuale = false, $array = [])
    {
        $data = ($data ?: date('Y-m-d H:i:s'));
        $id = $this->registra($qta, $descrizone, $data, $manuale, $array);

        if (empty($this->servizio)) {
            $this->qta += $qta;

            $this->save();
        }

        return $id;
    }

    /**
     * Funzione per registrare un movimento del magazzino in relazione all'articolo corrente, senza movimentare la quantità dell'articolo stesso.
     *
     * @param string $descrizone
     * @param string $data
     * @param bool   $manuale
     * @param array  $array
     *
     * @return bool
     */
    public function registra($qta, $descrizone = null, $data = null, $manuale = false, $array = [])
    {
        if (empty($qta)) {
            return false;
        }

        global $user;

        // Movimento il magazzino solo se l'articolo non è un servizio
        if (empty($this->servizio)) {
            // Registrazione della movimentazione
            database()->insert('mg_movimenti', array_merge($array, [
                'idarticolo' => $this->id,
                'qta' => $qta,
                'movimento' => $descrizone,
                'data' => $data,
                'manuale' => $manuale,
                'idutente' => $user->id,
            ]));
        }
        $id = database()->lastInsertedID();

        return $id;
    }

    /**
     * Imposta il prezzo di vendita sulla base dell'impostazione per l'utilizzo dei prezzi comprensivi di IVA.
     */
    public function setPrezzoVendita($prezzo_vendita, $id_iva)
    {
        $this->idiva_vendita = $id_iva;

        // Calcolo prezzo di vendita ivato e non ivato
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $id_iva = $id_iva ?: setting('Iva predefinita');
        $percentuale_aliquota = floatval(Aliquota::find($id_iva)->percentuale);
        if ($prezzi_ivati) {
            $this->prezzo_vendita_ivato = $prezzo_vendita;
            $this->prezzo_vendita = $prezzo_vendita / (1 + $percentuale_aliquota / 100);
        } else {
            $this->prezzo_vendita = $prezzo_vendita;
            $this->prezzo_vendita_ivato = $prezzo_vendita * (1 + $percentuale_aliquota / 100);
        }
    }

    /**
     * Imposta il prezzo di vendita sulla base dell'impostazione per l'utilizzo dei prezzi comprensivi di IVA.
     */
    public function setMinimoVendita($prezzo_minimo, $id_iva)
    {
        // Calcolo prezzo minimo di vendita ivato e non ivato
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $id_iva = $id_iva ?: setting('Iva predefinita');
        $percentuale_aliquota = floatval(Aliquota::find($id_iva)->percentuale);
        if ($prezzi_ivati) {
            $this->minimo_vendita_ivato = $prezzo_minimo;
            $this->minimo_vendita = $prezzo_minimo / (1 + $percentuale_aliquota / 100);
        } else {
            $this->minimo_vendita = $prezzo_minimo;
            $this->minimo_vendita_ivato = $prezzo_minimo * (1 + $percentuale_aliquota / 100);
        }
    }

    /**
     * Imposta il prezzo di acquisto e aggiorna il prezzo di vendita in base al coefficiente.
     */
    public function setPrezzoAcquistoAttribute($value)
    {
        $this->attributes['prezzo_acquisto'] = $value;

        if ($this->coefficiente != 0) {
            $prezzo_vendita = $value * $this->coefficiente;

            $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
            $id_iva = $this->idiva_vendita ?: setting('Iva predefinita');
            $percentuale_aliquota = floatval(Aliquota::find($id_iva)->percentuale);

            if ($prezzi_ivati) {
                $prezzo_vendita = $prezzo_vendita * (1 + $percentuale_aliquota / 100);
            }

            $this->setPrezzoVendita(round($prezzo_vendita, 2), $this->idiva_vendita);
        }
    }

    /**
     * Verifica se l'articolo corrente è una variante per una Combinazione.
     *
     * @return bool
     */
    public function isVariante()
    {
        return !empty($this->id_combinazione);
    }

    // Attributi Eloquent

    public function getImmagineUploadAttribute()
    {
        if (empty($this->immagine)) {
            return null;
        }

        return $this->uploads()->where('filename', $this->immagine)->first();
    }

    public function getImageAttribute()
    {
        if (empty($this->immagine)) {
            return null;
        }

        $module = Module::find((new Module())->getByField('name', $this->module, \Models\Locale::getPredefined()->id));
        $fileinfo = \Uploads::fileInfo($this->immagine);

        $directory = '/'.$module->upload_directory.'/';
        $image = $directory.$this->immagine;
        $image_thumbnail = $directory.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'];

        $url = file_exists(base_dir().$image_thumbnail) ? base_path().$image_thumbnail : base_path().$image;

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

    public function getNomeVarianteAttribute()
    {
        $valori = database()->fetchArray("SELECT 
            CONCAT(`mg_attributi_lang`.`title`, ': ', `mg_valori_attributi`.`nome`) AS nome
        FROM 
            `mg_articolo_attributo`
            INNER JOIN `mg_valori_attributi` ON `mg_valori_attributi`.`id` = `mg_articolo_attributo`.`id_valore`
            INNER JOIN `mg_attributi` ON `mg_attributi`.`id` = `mg_valori_attributi`.`id_attributo`
            LEFT JOIN `mg_attributi_lang` ON (`mg_attributi_lang`.`id_record` = `mg_attributi`.`id` AND `mg_attributi_lang`.`id_lang` = ".prepare(\Models\Locale::getDefault()->id).')
            INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `mg_articolo_attributo`.`id_articolo`
            INNER JOIN `mg_combinazioni` ON `mg_combinazioni`.`id` = `mg_articoli`.`id_combinazione`
            INNER JOIN `mg_attributo_combinazione` ON `mg_attributo_combinazione`.`id_combinazione` = `mg_combinazioni`.`id` AND `mg_attributo_combinazione`.`id_attributo` = `mg_attributi`.`id`
        WHERE 
            `mg_articoli`.`id` = '.prepare($this->id).'
        ORDER BY 
            `mg_attributo_combinazione`.`order`');

        return implode(', ', array_column($valori, 'nome'));
    }

    // Relazioni Eloquent

    public function articoli()
    {
        return $this->hasMany(ArticoloIntervento::class, 'idarticolo');
    }

    public function combinazione()
    {
        return $this->belongsTo(Combinazione::class, 'id_combinazione');
    }

    public function attributi()
    {
        return $this->belongsToMany(ValoreAttributo::class, 'mg_articolo_attributo', 'id_articolo', 'id_valore');
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
     * Restituisce le giacenze per sede dell'articolo.
     *
     * @param $data Indica la data fino alla quale calcolare le giacenze totali. Di default tutte
     *
     * @return array
     */
    public function getGiacenze($data = null)
    {
        $movimenti = $this->movimenti()
            ->select(
                'idsede',
                database()->raw('SUM(qta) AS qta')
            )->groupBy(['idsede']);

        if (!empty($data)) {
            $movimenti = $movimenti->where('data', '<=', \Carbon\Carbon::parse($data)->format('Y-m-d'));
        }

        $movimenti = $movimenti->get()
            ->mapToGroups(function ($item, $key) {
                return [$item->idsede => (float) $item->attributes['qta']];
            })
            ->toArray();

        return $movimenti;
    }

    /**
     * Restituisce i movimenti di magazzino dell'articolo raggruppati per documento relativo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Query\Builder
     */
    public function movimentiComposti($mostra_vuoti = false)
    {
        $movimenti = $this->movimenti()
            ->selectRaw('*, mg_movimenti.created_at AS data_movimento, SUM(mg_movimenti.qta) as qta_documento, IFNULL(mg_movimenti.reference_type, mg_movimenti.id) as tipo_gruppo')
            ->groupBy(['tipo_gruppo', 'mg_movimenti.reference_id', 'mg_movimenti.idutente']);

        if (!empty($mostra_vuoti)) {
            return $movimenti;
        }

        return $movimenti->havingRaw('mg_movimenti.reference_type IS NULL OR qta_documento != 0');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function sottocategoria()
    {
        return $this->belongsTo(Categoria::class, 'id_sottocategoria');
    }

    public function dettaglioFornitori()
    {
        return $this->hasMany(DettaglioFornitore::class, 'id_articolo');
    }

    public function dettaglioFornitore($id_fornitore)
    {
        return $this->dettaglioFornitori()
            ->where('id_fornitore', $id_fornitore)
            ->first();
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
