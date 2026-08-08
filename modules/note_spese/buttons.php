<?php

include_once __DIR__.'/../../core.php';

if (($structure->permission ?? null) === 'rw' && !empty($id_record)) {
    $duplicate_url = base_path_osm().'/add.php?id_module='.(int) $id_module.'&duplicate_id='.(int) $id_record;
    ?>
    <button type="button"
            class="btn btn-primary"
            data-widget="modal"
            data-title="<?php echo prepareToField(tr('Duplica nota spesa')); ?>"
            data-href="<?php echo prepareToField($duplicate_url); ?>">
        <i class="fa fa-copy"></i> <?php echo tr('Duplica'); ?>
    </button>
    <?php
}
