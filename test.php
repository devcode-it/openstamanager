<?php

require 'core.php';

$user = \App::getUser();
print_r(Models\Module::getHierarchy()->toArray());
exit();
print_r($user->modules()->get()->toArray());
print_r($user->toArray());
$modules = Models\Module::all();

foreach ($modules as $module) {
    print_r($module->title.': '.$module->permission.'<br>');
    //print_r($module->views.'<br><br>');
}

exit();
