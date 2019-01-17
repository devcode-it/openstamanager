<?php

// Permessi avanzati
$groups = Models\Group::all();

$array = [];
foreach ($groups as $group) {
    $array[$group->id] = [
        'permission_level' => 'rw',
    ];
}

$plugins = Models\Plugin::all();
foreach ($plugins as $element) {
    $element->groups()->sync($array);
}

$widgets = Models\Widget::all();
foreach ($widgets as $element) {
    $element->groups()->sync($array);
}

$segments = Models\Segment::all();
foreach ($segments as $element) {
    $element->groups()->sync($array);
}

$prints = Models\PrintTemplate::all();
foreach ($prints as $element) {
    $element->groups()->sync($array);
}

$admin = Models\Group::where('nome', 'Amministratori')->first();
$modules = Models\Module::all();
foreach ($modules as $element) {
    $element->groups()->syncWithoutDetaching([
        $admin->id => [
            'permission_level' => 'rw',
        ],
    ]);
}
