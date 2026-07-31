<?php

namespace Modules\Partitario;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Movimento extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_movimenti';

    protected $fillable = [
        'id_mastrino',
        'data',
        'data_inizio_competenza',
        'data_fine_competenza',
        'id_documento',
        'id_scadenza',
        'is_insoluto',
        'is_apertura',
        'is_chiusura',
        'id_anagrafica',
        'descrizione',
        'note',
        'id_conto',
        'totale',
        'prima_nota',
        'totale_reddito',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'data' => 'date',
        'data_inizio_competenza' => 'date',
        'data_fine_competenza' => 'date',
        'is_insoluto' => 'boolean',
        'is_apertura' => 'boolean',
        'is_chiusura' => 'boolean',
        'prima_nota' => 'boolean',
        'totale' => 'decimal:6',
        'totale_reddito' => 'decimal:6',
    ];

    public function conto()
    {
        return $this->belongsTo(PianoDeiConti3::class, 'id_conto');
    }

    public function scopeNelPeriodo(Builder $query, $period_start, $period_end)
    {
        return $query->whereBetween('data', [$period_start, $period_end])
            ->orWhere(function ($q) use ($period_start, $period_end) {
                $q->whereNotNull('data_inizio_competenza')
                    ->whereNotNull('data_fine_competenza')
                    ->where('data_fine_competenza', '>=', $period_start)
                    ->where('data_inizio_competenza', '<=', $period_end);
            });
    }
}