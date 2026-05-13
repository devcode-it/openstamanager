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
use Models\Locale;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Contratti\Contratto;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Modules\PianiSconto\PianoSconto;
use Modules\Preventivi\Preventivo;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Plugins\DichiarazioniIntento\Dichiarazione;
use Traits\RecordTrait;

class Anagrafica extends Model
{
    use RecordTrait;
    use SimpleModelTrait;
    use SoftDeletes;

    protected $table = 'an_anagrafiche';

    protected $primaryKey = 'id';

    protected $module = 'Anagrafiche';

    protected $guarded = [];

    protected $appends = [
        'partita_iva',
    ];

    protected $hidden = [
        'p_iva',
    ];

    /**
     * Crea una nuova anagrafica.
     *
     * @param string $ragione_sociale
     *
     * @return self
     */
    public static function build($ragione_sociale = null, $nome = '', $cognome = '', array $tipologie = [])
    {
        $model = new static();

        $model->ragione_sociale = $ragione_sociale;

        $model->nome = $nome ?: '';
        $model->cognome = $cognome ?: '';

        $model->codice = static::getNextCodice();
        $model->id_ritenuta_acconto_vendite = setting("Ritenuta d'acconto predefinita");
        $model->save();

        $model->tipologie = $tipologie;
        $model->save();

        if ($model->isTipo('Cliente') && setting('Listino cliente predefinito')) {
            $model->id_listino = setting('Listino cliente predefinito');
            $model->save();
        }

        return $model;
    }

    public static function fromTipo($type)
    {
        $tipologia = TipoAnagrafica::where('name', $type)->first()->id;

        $anagrafiche = self::whereHas('tipi', function ($query) use ($tipologia) {
            $query->where('an_tipianagrafiche.id', '=', $tipologia);
        });

        return $anagrafiche;
    }

    public static function fixAzienda(Anagrafica $anagrafica)
    {
        \Settings::setValue('Azienda predefinita', $anagrafica->id);
    }

    public static function fixCliente(Anagrafica $anagrafica)
    {
        // Creo il relativo conto nel partitario se non esiste
        if (empty($anagrafica->id_conto_cliente)) {
            $id_conto = self::creaConto($anagrafica, 'id_conto_cliente');

            // Collegamento conto
            $anagrafica->id_conto_cliente = $id_conto;
            $anagrafica->save();
        } else {
            $conto = $anagrafica->id_conto_cliente;
            $is_esistente = database()->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE id = '.$anagrafica['id_conto_cliente']);
            if (!$is_esistente) {
                $anagrafica->id_conto_cliente = null;
                $anagrafica->save();
                Anagrafica::fixCliente($anagrafica);
            }
        }
    }

    public static function fixFornitore(Anagrafica $anagrafica)
    {
        // Creo il relativo conto nel partitario se non esiste
        if (empty($anagrafica->id_conto_fornitore)) {
            $id_conto = self::creaConto($anagrafica, 'id_conto_fornitore');

            // Collegamento conto
            $anagrafica->id_conto_fornitore = $id_conto;
            $anagrafica->save();
        } else {
            $conto = $anagrafica->id_conto_fornitore;
            $is_esistente = database()->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE id = '.$anagrafica['id_conto_fornitore']);
            if (!$is_esistente) {
                $anagrafica->id_conto_fornitore = null;
                $anagrafica->save();
                Anagrafica::fixFornitore($anagrafica);
            }
        }
    }

    public static function fixTecnico(Anagrafica $anagrafica)
    {
        $database = database();

        $presenti = $database->fetchArray('SELECT id_tipo_intervento FROM in_tariffe WHERE id_tecnico = '.prepare($anagrafica->id));

        // Aggiunta associazioni costi unitari al contratto
        $tipi = TipoSessione::whereNotIn('id', array_column($presenti, 'id_tipo_intervento'))->get();

        foreach ($tipi as $tipo) {
            $database->insert('in_tariffe', [
                'id_tecnico' => $anagrafica->id,
                'id_tipo_intervento' => $tipo->id,
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
            $tipologie[] = TipoAnagrafica::where('name', 'Azienda')->first()->id;
        }

        $tipologie = array_clean($tipologie);

        $previous = $this->tipi()->get();
        $this->tipi()->sync($tipologie);
        $actual = $this->tipi()->get();

        $diff = $actual->diff($previous);

        foreach ($diff as $tipo) {
            $method = 'fix'.$tipo->name;
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
     * Controlla il tipo di anagrafica.
     *
     * @return bool
     */
    public function isTipo($type)
    {
        return $this->tipi()->get()->search(fn ($item, $key) => TipoAnagrafica::find($item->id)->name == $type) !== false;
    }

    #[\Override]
    public function delete()
    {
        if (!$this->isAzienda()) {
            return parent::delete();
        }
    }

    #[\Override]
    public function save(array $options = [])
    {
        $this->fixRagioneSociale();

        if (setting('Geolocalizzazione automatica')) {
            $this->geolocalizzazione();
        }

        return parent::save($options);
    }

    // Attributi Eloquent

    public function getModuleAttribute()
    {
        return 'Anagrafiche';
    }

    public function getPartitaIvaAttribute()
    {
        return $this->attributes['p_iva'];
    }

    public function setPartitaIvaAttribute($value)
    {
        if (in_array($value, ['99999999999', '00000000000'])) {
            $value = null;
        }

        $this->attributes['p_iva'] = trim(strtoupper((string) $value));
    }

    public function setNomeAttribute($value)
    {
        $this->attributes['nome'] = trim((string) $value);
    }

    public function setCognomeAttribute($value)
    {
        $this->attributes['cognome'] = trim((string) $value);
    }

    public function setCodiceFiscaleAttribute($value)
    {
        $this->attributes['codice_fiscale'] = trim(strtoupper((string) $value));
    }

    public function setCodiceDestinatarioAttribute($value)
    {
        if (!empty($this->sedeLegale->nazione) && !(($this->sedeLegale->nazione->iso2 === 'IT') || ($this->sedeLegale->nazione->iso2 === 'SM'))) {
            $value = '';
        }

        $this->attributes['codice_destinatario'] = trim(strtoupper((string) $value));
    }

    public function setCodiceAttribute($value)
    {
        $this->attributes['codice'] = trim((string) $value);
    }

    public function setRagioneSocialeAttribute($value)
    {
        $this->attributes['ragione_sociale'] = trim((string) $value);
    }

    public function setTelefonoAttribute($value)
    {
        $this->attributes['telefono'] = trim((string) $value);
    }

    public function setIndirizzoAttribute($value)
    {
        $this->attributes['indirizzo'] = trim((string) $value);
    }

    public function setCapAttribute($value)
    {
        $this->attributes['cap'] = trim((string) $value);
    }

    public function setCellulareAttribute($value)
    {
        $this->attributes['cellulare'] = trim((string) $value);
    }

    public function setFaxAttribute($value)
    {
        $this->attributes['fax'] = trim((string) $value);
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = trim((string) $value);
    }

    public function setPecAttribute($value)
    {
        $this->attributes['pec'] = trim((string) $value);
    }

    public function setSitewebAttribute($value)
    {
        $this->attributes['sito_web'] = trim((string) $value);
    }

    public function setLuogoNascitaAttribute($value)
    {
        $this->attributes['luogo_nascita'] = trim((string) $value);
    }

    public function setSessoAttribute($value)
    {
        $this->attributes['sesso'] = trim((string) $value);
    }

    public function setNoteAttribute($value)
    {
        $this->attributes['note'] = trim((string) $value);
    }

    public function setProvinciaAttribute($value)
    {
        $this->attributes['provincia'] = trim((string) $value);
    }

    public function setCittaAttribute($value)
    {
        $this->attributes['citta'] = trim((string) $value);
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
        return $this->belongsToMany(TipoAnagrafica::class, 'an_tipianagrafiche_anagrafiche', 'id_anagrafica', 'id_tipo_anagrafica', 'id', 'id');
    }

    public function sedi()
    {
        return $this->hasMany(Sede::class, 'id_anagrafica');
    }

    public function nazione()
    {
        return $this->belongsTo(Nazione::class, 'id_nazione');
    }

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'id_anagrafica');
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
        return $this->hasMany(Ordine::class, 'id_anagrafica');
    }

    public function ddt()
    {
        return $this->hasMany(DDT::class, 'id_anagrafica');
    }

    public function contratti()
    {
        return $this->hasMany(Contratto::class, 'id_anagrafica');
    }

    public function preventivi()
    {
        return $this->hasMany(Preventivo::class, 'id_anagrafica');
    }

    public function dichiarazioni()
    {
        return $this->hasMany(Dichiarazione::class, 'id_anagrafica');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_anagrafica');
    }

    public function pianoScontoVendite()
    {
        return $this->belongsTo(PianoSconto::class, 'id_piano_sconto_vendite');
    }

    public function pianoScontoAcquisti()
    {
        return $this->belongsTo(PianoSconto::class, 'id_piano_sconto_acquisti');
    }

    /**
     * Restituisce il piano di sconto in base alla direzione.
     *
     * @param string $dir
     *
     * @return PianoSconto|null
     */
    public function pianoSconto($dir)
    {
        return ($dir == 'entrata') ? $this->pianoScontoVendite : $this->pianoScontoAcquisti;
    }

    // Metodi statici

    /**
     * Calcola il nuovo codice di anagrafica.
     *
     * @return string
     */
    public static function getNextCodice()
    {
        return getNextNumeroProgressivo('an_anagrafiche', 'codice', null, null, [
            'use_setting' => true,
            'setting_key' => 'Formato codice anagrafica',
            'conditions_extra' => [
                "codice != ''",
                'deleted_at IS NULL',
            ],
        ]);
    }

    protected static function creaConto(Anagrafica $anagrafica, $campo)
    {
        $categoria_conto_id = null;
        if ($campo == 'id_conto_cliente') {
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
                ->where('descrizione', $anagrafica->ragione_sociale)
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
            $database->update('co_pianodeiconti3', ['descrizione' => $this->ragione_sociale], ['id' => $this->id_conto_cliente]);
        }

        if ($this->isTipo('Fornitore')) {
            $database->update('co_pianodeiconti3', ['descrizione' => $this->ragione_sociale], ['id' => $this->id_conto_fornitore]);
        }
    }

    protected function fixRagioneSociale()
    {
        if (!empty($this->cognome) || !empty($this->nome)) {
            $this->ragione_sociale = $this->cognome.' '.$this->nome;
        }
        $this->aggiornaConto();
    }

    protected function geolocalizzazione()
    {
        $new_indirizzo = $this->sedeLegale->indirizzo.', '.$this->sedeLegale->citta.', '.$this->sedeLegale->provincia;
        $prev_indirizzo = $this->sedeLegale->original['indirizzo'].', '.$this->sedeLegale->original['citta'].', '.$this->sedeLegale->original['provincia'];

        if (
            !empty($this->sedeLegale->indirizzo)
            && !empty($this->sedeLegale->citta)
            && !empty($this->sedeLegale->provincia)
            && $new_indirizzo != $prev_indirizzo
        ) {
            $indirizzo = urlencode($this->sedeLegale->indirizzo.', '.$this->sedeLegale->citta.', '.$this->sedeLegale->provincia);

            if (setting('Gestore mappa') == 'OpenStreetMap') {
                // TODO: da riscrivere con Guzzle e spostare su hook
                if (!function_exists('curl_init')) {
                    // cURL non è attivo
                    flash()->error(tr('cURL non attivo, impossibile continuare l\'operazione.'));

                    return false;
                }
                $ch = curl_init();

                $lang = Locale::find(setting('Lingua'))->language_code;
                $url = 'https://nominatim.openstreetmap.org/search.php?q='.$indirizzo.'&format=jsonv2&accept-language='.$lang;
                $user_agent = 'traccar';
                curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                $data = json_decode(curl_exec($ch));

                // Salvataggio informazioni
                $this->gaddress = $data[0]->display_name;
                $this->lat = $data[0]->lat;
                $this->lng = $data[0]->lon;
            } elseif (setting('Gestore mappa') == 'Google Maps') {
                $apiKey = setting('Google Maps API key per Tecnici');
                $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$indirizzo.'&key='.$apiKey;

                // Validazione URL per prevenire SSRF
                $parsed_url = parse_url($url);
                if (!isset($parsed_url['host']) || $parsed_url['host'] !== 'maps.googleapis.com') {
                    return false;
                }

                $context = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                    ],
                ]);

                $response = @file_get_contents($url, false, $context);
                if ($response === false) {
                    return false;
                }

                $data = json_decode($response, true);

                if ($data['status'] == 'OK') {
                    $this->lat = $data['results'][0]['geometry']['location']['lat'];
                    $this->lng = $data['results'][0]['geometry']['location']['lng'];
                    $this->gaddress = $data['results'][0]['formatted_address'];
                }
            }
        }
    }
}
