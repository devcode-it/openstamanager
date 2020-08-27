<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $id_module = Modules::get('Articoli')['id'];
    redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record);
}
