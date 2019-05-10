<?php

namespace Plugins\ImportFE;

use Common\HookManager;

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

        return [
            'icon' => 'fa fa-file-text-o ',
            'message' => tr('Ci sono _NUM_ fatture remote da importare', [
                '_NUM_' => $count,
            ]),
            'notify' => !empty($count),
        ];
    }
}
