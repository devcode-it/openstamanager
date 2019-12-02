<?php

namespace Plugins\ReceiptFE;

use Hooks\CachedManager;
use Modules;

class ReceiptHook extends CachedManager
{
    public function data()
    {
        $list = Interaction::getReceiptList();

        return $list;
    }

    public function response()
    {
        $results = self::getCache()['results'];

        $count = count($results);
        $notify = false;

        $module = Modules::get('Fatture di vendita');
        $plugins = $module->plugins;

        if (!empty($plugins)) {
            $notify = !empty($count);

            $plugin = $plugins->first(function ($value, $key) {
                return $value->name == 'Ricevute FE';
            });

            $link = ROOTDIR.'/controller.php?id_module='.$module->id.'#tab_'.$plugin->id;
        }

        $message = tr('Ci sono _NUM_ ricevute da importare', [
            '_NUM_' => $count,
        ]);

        return [
            'icon' => 'fa fa-ticket text-yellow',
            'link' => $link,
            'message' => $message,
            'show' => $notify,
        ];
    }
}
