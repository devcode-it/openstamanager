<?php

namespace Modules\Interventi;

use Illuminate\Database\Eloquent\Model;
use Modules\Articoli\Articolo as Original;

class Articolo extends Model
{
    protected $table = 'mg_articoli_interventi';

    /** @var array Opzioni abilitate per la creazione */
    protected $fillable = [
        'idarticolo',
        'idintervento',
        'idautomezzo',
    ];

    /**
     * Crea un nuovo articolo collegato ad un intervento.
     *
     * @param array $attributes
     */
    public static function create(array $attributes = [])
    {
        $model = static::query()->create($attributes);

        $articolo = $model->articolo()->first();
        $qta = $attributes['qta'];

        // Movimento l'articolo
        if (!empty($model->idautomezzo)) {
            $rs = $dbo->fetchArray("SELECT CONCAT_WS(' - ', nome, targa) AS nome FROM dt_automezzi WHERE id=".prepare($model->idautomezzo));
            $nome = $rs[0]['nome'];

            $descrizione = ($qta < 0) ? tr("Carico sull'automezzo _NAME_", [
                '_NAME_' => $nome,
            ]) : tr("Scarico dall'automezzo _NAME_", [
                '_NAME_' => $nome,
            ]);

            $dbo->query('UPDATE mg_articoli_automezzi SET qta = qta + '.$qta.' WHERE idarticolo = '.prepare($articolo->id).' AND idautomezzo = '.prepare($model->idautomezzo));
            $data = date('Y-m-d');

            $articolo->registra(-$qta, $descrizione, $data, false, [
                'idautomezzo' => $model->idautomezzo,
                'idintervento' => $model->idintervento,
            ]);
        } else {
            $intervento = $model->intervento();

            $numero = $intervento->codice;
            $data = database()->fetchOne('SELECT MAX(orario_fine) AS data FROM in_interventi_tecnici WHERE idintervento = :id_intervento', [
                ':id_intervento' => $intervento->id,
            ])['data'];

            $data = $data ?? $intervento->data_richiesta;

            $descrizione = ($qta < 0) ? tr('Ripristino articolo da intervento _NUM_', [
                '_NUM_' => $numero,
            ]) : tr('Scarico magazzino per intervento _NUM_', [
                '_NUM_' => $numero,
            ]);

            $articolo->movimenta(-$qta, $descrizione, $data, false, [
                'idintervento' => $intervento->id,
            ]);
        }

        // Salvataggio delle informazioni
        $model->descrizione = isset($attributes['descrizione']) ? $attributes['descrizione'] : $articolo->descrizione;
        $model->prezzo_acquisto = $articolo->prezzo_acquisto;
        $model->abilita_serial = $articolo->abilita_serial;

        $model->um = $attributes['um'];
        $model->qta = $attributes['qta'];
        $model->prezzo_vendita = isset($attributes['prezzo']) ? $attributes['prezzo'] : $articolo->prezzo_vendita;

        $model->save();

        return $model;
    }

    public function setIVA($id_iva)
    {
        $iva = database()->fetchOne('SELECT * FROM co_iva WHERE id = :id_iva', [
            ':id_iva' => $id_iva,
        ]);
        $descrizione = $iva['descrizione'];

        $valore = (($this->prezzo_vendita * $this->qta) - $this->sconto) * $iva['percentuale'] / 100;

        $this->idiva = $iva['id'];
        $this->desc_iva = $descrizione;
        $this->iva = $valore;

        $this->save();
    }

    public function setSconto($unitario, $tipo)
    {
        $sconto = calcola_sconto([
            'sconto' => $unitario,
            'prezzo' => $this->prezzo_vendita,
            'tipo' => $tipo,
            'qta' => $this->qta,
        ]);

        $this->sconto_unitario = $unitario;
        $this->tipo_sconto = $tipo;
        $this->sconto = $sconto;

        $this->save();
    }

    public function setSerials($serials)
    {
        database()->sync('mg_prodotti', [
            'id_riga_intervento' => $this->id,
            'dir' => 'entrata',
            'id_articolo' => $this->idintervento,
        ], [
            'serial' => $serials,
        ]);
    }

    public function articolo()
    {
        return $this->belongsTo(Original::class, 'idarticolo');
    }

    public function intervento()
    {
        return $this->belongsTo(Intervento::class, 'idintervento');
    }
}
