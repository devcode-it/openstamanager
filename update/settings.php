<?php

error_reporting(0);

$skip_permissions = true;
include_once __DIR__.'/../core.php';

$info = Update::getSettings();
$response = json_encode($info);

echo $response;
