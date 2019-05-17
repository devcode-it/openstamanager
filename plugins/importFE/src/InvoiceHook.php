<?php

namespace Plugins\ImportFE;

use Common\HookManager;
use Modules;

class InvoiceHook extends HookManager
{
    public function manage()
    {
        $list = Interaction::listToImport();

        return $list;
    }

    public function response($results)
    {
        $count = count($results);

        $module = Modules::get('Fatture di acquisto');
        $plugin = $module->plugins->first(function ($value, $key) {
            return $value->name == 'Fatturazione Elettronica';
        });

        $link = ROOTDIR.'/controller.php?id_module='.$module->id.'#tab_'.$plugin->id;
        $icon = 'fa fa-file-text-o';

        if ($count > 0) {
            $message = tr('Ci sono _NUM_ fatture passive da importare', [
                '_NUM_' => $count,
            ]);
            $icon_color = 'text-yellow';
        } else {
            $message = tr('Nessuna fattura passiva da importare');
            $icon_color = 'text-green';
            $link = '#';
        }

        return [
            'icon' => $icon.' '.$icon_color,
            'link' => $link,
            'message' => $message,
            'notify' => !empty($count),
        ];
    }
}
