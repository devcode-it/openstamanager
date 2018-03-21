<?php
    if ($docroot == '') {
        die(_('Accesso negato!'));
    }
    $records = $dbo->fetchArray("SELECT *, (SELECT options FROM zz_modules WHERE id = zz_segments.id_module) options, (SELECT name FROM zz_modules WHERE id = zz_segments.id_module) AS modulo, (SELECT COUNT(t.id) FROM zz_segments t WHERE t.id_module = zz_segments.id_module) AS n_sezionali FROM zz_segments WHERE id='$id_record'");