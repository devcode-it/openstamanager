<?php

$base_dir = __DIR__;

// Individuazione di $prefix
$script = $_SERVER['REQUEST_URI'];
$needle = '/'.basename($base_dir).'/';
$pos = strrpos($script, $needle);
if ($pos !== false) {
    $prefix = substr($script, 0, $pos).$needle;
    $suffix = substr($script, $pos + strlen($needle));
} else {
    $prefix = '/';
    $suffix = '';
}
$prefix = rtrim($prefix, '/');
$prefix = str_replace('%2F', '/', rawurlencode($prefix));
$suffix = str_replace('%2F', '/', rawurlencode($suffix));

$url = 'http://'.$_SERVER['HTTP_HOST'].$prefix.'/public/'.$suffix;
$url = str_replace('index.php', '', $url);

header('Location: '.$url);
exit();
