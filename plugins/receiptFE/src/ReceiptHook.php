<?php

namespace Plugins\ReceiptFE;

use Common\HookManager;

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

        return [
            'icon' => 'fa fa-dot-circle-o',
            'message' => tr('Ci sono _NUM_ ricevute da importare', [
                '_NUM_' => $count,
            ]),
            'notify' => !empty($count),
        ];
    }
}
