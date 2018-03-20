<?php
    if ($docroot == '') {
        die(_('Accesso negato!'));
    }
    $records = $dbo->fetchArray("SELECT *, (SELECT COUNT(t.id) FROM co_sezionali t WHERE t.dir = co_sezionali.dir) AS n_sezionali FROM co_sezionali WHERE id='$id_record'");
