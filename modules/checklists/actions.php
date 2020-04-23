<?php

include_once __DIR__.'/../../core.php';

use Modules\Checklists\Checklist;
use Modules\Checklists\ChecklistItem;

switch (post('op')) {
    case 'add':
        $record = Checklist::build(post('name'));

        $record->id_module = post('module') ?: null;
        $record->id_plugin = post('plugin') ?: null;
        $record->save();

        $id_record = $record->id;

        flash()->info(tr('Nuova checklist creata!'));

        break;

    case 'update':
        $record->name = post('name');

        $record->id_module = post('module') ?: null;
        $record->id_plugin = post('plugin') ?: null;
        $record->save();

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'delete':
        $record->delete();

        flash()->info(tr('Checklist eliminata!'));

        break;

    case 'add_item':
        $content = post('content');
        $parent_id = post('parent') ?: null;
        $item = ChecklistItem::build($record, $content, $parent_id);

        flash()->info(tr('Nuova riga della checklist creata!'));

        break;

    case 'delete_item':
        $item = ChecklistItem::find(post('check_id'));
        $item->delete();

        flash()->info(tr('Riga della checklist eliminata!'));

        break;

    case 'update_position':
        $ids = explode(',', $_POST['order']);
        $order = 0;

        foreach ($ids as $id) {
            $dbo->query('UPDATE `zz_checklist_items` SET `order` = '.prepare($order).' WHERE id = '.prepare($id));
            ++$order;
        }

        break;
}
