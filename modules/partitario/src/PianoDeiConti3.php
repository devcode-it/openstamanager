<?php

namespace Modules\Partitario;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PianoDeiConti3 extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_piano_dei_conti3';

    protected $fillable = [
        'numero',
        'descrizione',
        'id_piano_dei_conti2',
        'dir',
        'percentuale_deducibile',
    ];

    protected $appends = [
        'id_anagrafica',
        'deleted_at',
    ];

    public function secondoLivello()
    {
        return $this->belongsTo(PianoDeiConti2::class, 'id_piano_dei_conti2');
    }

    public function movimenti()
    {
        return $this->hasMany(Movimento::class, 'id_conto');
    }

    public function scopeWithMovimentiNelPeriodo(Builder $query, $period_start, $period_end)
    {
        return $query->with(['movimenti' => function ($q) use ($period_start, $period_end) {
            $q->whereBetween('data', [$period_start, $period_end])
                ->orWhere(function ($q2) use ($period_start, $period_end) {
                    $q2->whereNotNull('data_inizio_competenza')
                        ->whereNotNull('data_fine_competenza')
                        ->where('data_fine_competenza', '>=', $period_start)
                        ->where('data_inizio_competenza', '<=', $period_end);
                });
        }]);
    }

    public function getIdAnagraficaAttribute()
    {
        return \Modules\Anagrafiche\Anagrafica::where('id_conto_cliente', $this->id)
            ->orWhere('id_conto_fornitore', $this->id)
            ->value('id');
    }

    public function getDeletedAtAttribute()
    {
        return \Modules\Anagrafiche\Anagrafica::where('id_conto_cliente', $this->id)
            ->orWhere('id_conto_fornitore', $this->id)
            ->value('deleted_at');
    }
}