<?php

namespace Plugins\ReceiptFE;

use Common\HookManager;
use Modules;

class ReceiptHook extends HookManager
{
    public function manage()
    {
        $list = Interaction::getReceiptList();

        return $list;
    }

    public function response($results)
    {
        $count = count($results);

        $module = Modules::get('Fatture di vendita');
        $plugin = $module->plugins->first(function ($value, $key) {
            return $value->name == 'Ricevute FE';
        });

        $link = ROOTDIR.'/controller.php?id_module='.$module->id.'#tab_'.$plugin->id;

        if ($count > 0) {
            $message = tr('Ci sono _NUM_ ricevute da importare', [
                '_NUM_' => $count,
            ]);
        } else {
            $message = tr('Nessuna ricevuta da importare');
        }

        return [
            'icon' => 'fa fa-ticket',
            'link' => $link,
            'message' => $message,
            'notify' => !empty($count),
        ];
    }
}
