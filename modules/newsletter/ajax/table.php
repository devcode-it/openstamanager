<?php

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;
use Modules\Emails\Mail;
use Modules\ListeNewsletter\Lista;
use Modules\Newsletter\Newsletter;

include_once __DIR__.'/../../../core.php';

$id_newsletter = filter('id_newsletter');
$id_list = filter('id_list');

$newsletter = Newsletter::find($id_newsletter);
$lista = Lista::find($id_list);
$riferimento = $newsletter ?: $lista;

$search = filter('search') ? filter('search')['value'] : null;

// Utilizzo della risorsa destinatari_newsletter per gestire la ricerca
if (!empty($search)) {
    $resource = 'destinatari_newsletter';
    include_once __DIR__.'/select.php';

    $results = collect($results)->mapToGroups(function ($item, $key) {
        list($tipo, $id) = explode('_', $item['id']);

        return [$tipo => $id];
    });

    $destinatari = $riferimento->destinatari()
        ->where(function ($query) use ($results) {
            $query->where('record_type', '=', Anagrafica::class)
                ->whereIn('record_id', !empty($results['anagrafica']) ? $results['anagrafica']->toArray() : []);
        })
        ->orWhere(function ($query) use ($results) {
            $query->where('record_type', '=', Sede::class)
                ->whereIn('record_id', !empty($results['sede']) ? $results['sede']->toArray() : []);
        })
        ->orWhere(function ($query) use ($results) {
            $query->where('record_type', '=', Referente::class)
                ->whereIn('record_id', !empty($results['referente']) ? $results['referente']->toArray() : []);
        });
}
// Elenco di tutti i destinatari
else {
    $destinatari = $riferimento->destinatari();
}

$start = filter('start');
$length = filter('length');

// Filtro dei record richiesti
$destinatari_filtrati = (clone $destinatari)
    ->skip($start)->take($length)
    ->get();

$id_modulo_anagrafiche = (new Module())->getByName('Anagrafiche')->id_record;
$righe = [];
foreach ($destinatari_filtrati as $destinatario) {
    $origine = $destinatario->getOrigine();

    $anagrafica = $origine instanceof Anagrafica ? $origine : $origine->anagrafica;
    $descrizione = $anagrafica->ragione_sociale;

    if ($origine instanceof Sede) {
        $descrizione .= ' ['.$origine->nomesede.']';
    } elseif ($origine instanceof Referente) {
        $descrizione .= ' ['.$origine->nome.']';
    }

    $tipo_anagrafica = $database->fetchOne('SELECT GROUP_CONCAT(`an_tipianagrafiche_lang`.`name`) AS descrizione FROM `an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche`.`id` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(\App::getLang()).') WHERE `an_tipianagrafiche_anagrafiche`.`idanagrafica`='.prepare($anagrafica->id))['descrizione'];

    $riga = [
        Modules::link('Anagrafiche', $anagrafica->id, $descrizione),
        $tipo_anagrafica,
        $anagrafica->tipo,
        '<div class="text-center">'.
        (!empty($origine->email) ?
            input([
                'type' => 'text',
                'name' => 'email',
                'id' => 'email_'.rand(0, 99999),
                'readonly' => '1',
                'class' => 'email-mask',
                'value' => $origine->email,
                'validation' => 'email|'.$id_modulo_anagrafiche.'|'.$destinatario->record_id,
            ]) :
            '<span class="text-danger"><i class="fa fa-close"></i> '.tr('Indirizzo e-mail mancante').'</span>'
        ).'
        </div>',
    ];

    // Informazioni di invio
    if (empty($lista)) {
        $mail_id = $destinatario->id_email;
        $mail = Mail::find($mail_id);
        if (!empty($mail) && !empty($mail->sent_at)) {
            $info_invio = '
            <span class="text-success">
                <i class="fa fa-paper-plane"></i> '.timestampFormat($mail->sent_at).'
            </span>';
        } else {
            $info_invio = '
            <span class="text-info">
                <i class="fa fa-clock-o"></i> '.tr('Non ancora inviata').'
            </span>';
        }

        $riga[] = '<div class="text-center">'.$info_invio.'</div>';
    }

    $riga = array_merge($riga, [
        '<div class="text-center">'.
        (!empty($origine->enable_newsletter) ?
            '<span class="text-success"><i class="fa fa-check"></i> '.tr('Abilitato').'</span>' :
            '<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> '.tr('Disabilitato').'</span>'
        ).'
        </div>',
        '<div class="text-center">'.(empty($lista) && !empty($origine->email) && !empty($origine->enable_newsletter) ? '
            <a class="btn btn-warning btn-xs" data-type="'.get_class($origine).'" data-id="'.$origine->id.'" data-email="'.$origine->email.'" onclick="testInvio(this)">
                <i class="fa fa-paper-plane "></i>
            </a>' : '').'
            <a class="btn btn-danger ask btn-xs" data-backto="record-edit" data-op="remove_receiver" data-type="'.get_class($origine).'" data-id="'.$origine->id.'">
                <i class="fa fa-trash"></i>
            </a>
        </div>',
    ]);

    $righe[] = $riga;
}

// Formattazione dei dati
echo json_encode([
    'data' => $righe,
    'recordsTotal' => $riferimento->destinatari()->count(),
    'recordsFiltered' => $destinatari->count(),
    'draw' => intval(filter('draw')),
]);
