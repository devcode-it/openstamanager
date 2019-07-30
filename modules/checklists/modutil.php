<?php

function renderChecklist($check, $level = 0)
{
    $user = auth()->getUser();
    $enabled = $check->assignedUsers->pluck('id')->search($user->id) !== false;

    $result = '
<li id="check_'.$check->id.'" class="check-item'.(!empty($check->checked_at) ? ' done' : '').'" '.(!$enabled ? 'style="opacity: 0.4"' : '').' data-id="'.$check->id.'">    
    <input type="checkbox" value="'.(!empty($check->checked_at) ? '1' : '0').'" '.(!empty($check->checked_at) ? 'checked' : '').'>

    <span class="text">'.$check->content.'</span>
    <span class="badge">'.(!empty($check->checked_at) ? timestampFormat($check->checked_at).' - '.$check->user->username : '').'</span>';

    if ($level == 0) {
        $result .= '
    <span class="handle pull-right">
        <i class="fa fa-ellipsis-v"></i>
        <i class="fa fa-ellipsis-v"></i>
    </span>';
    }

    $result .= '
    <div class="tools">
        <i class="fa fa-trash-o check-delete"></i>
    </div>
    
    <ul class="todo-list">';

    $children = $check->children;
    foreach ($children as $child) {
        $result .= renderChecklist($child, $level + 1);
    }

    $result .= '
    </ul>
</li>';

    return $result;
}
