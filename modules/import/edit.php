<?php

include_once __DIR__.'/../../core.php';

if (empty($id_record)) {
    require $docroot.'/add.php';
} else {
    // Inclusione del file del modulo per eventuale HTML personalizzato
    include $imports[$id_record]['import'];

    $fields = Import::getFields($id_record);

    $select = [];
    $select2 = [];
    foreach ($fields as $key => $value) {
        $select[] = [
            'id' => $key,
            'text' => $value['label'],
        ];

        $select2[] = [
            'id' => $value['field'],
            'text' => $value['label'],
        ];

        if ($value['primary_key']) {
            $primary_key = $value['field'];
        }
    }

    echo '
<form action="" method="post" id="edit-form">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="op" value="import">

    <div class="row">
        <div class="col-md-8">
            {[ "type": "checkbox", "label": "'.tr('Importa prima riga').'", "name": "first_row", "extra":"", "value": "1"  ]}
        </div>
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Chiave primaria').'", "name": "primary_key", "values": '.json_encode($select2).', "value": "'.$primary_key.'" ]}
        </div>
    </div>';

    $rows = Import::getFile($id_record, $record['id'], [
        'limit' => 10,
    ]);

    $count = count($rows[0]);

    echo '
    <div class="row">';

    for ($column = 0; $column < $count; ++$column) {
        echo '
    <div class="col-sm-6 col-lg-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Colonna _NUM_', [
                    '_NUM_' => $column + 1,
                ]).'</h3>
            </div>

            <div class="panel-body">';

        // Individuazione delle corrispondenze
        $selected = null;
        foreach ($fields as $key => $value) {
            if (in_array(str_to_lower($rows[0][$column]), $value['names'])) {
                $first_row = 1;
                $selected = $key;
                break;
            }
        }

        echo '
                {[ "type": "select", "label": "'.tr('Campo').'", "name": "fields[]", "values": '.json_encode($select).', "value": "'.$selected.'" ]}

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>'.tr('#').'</th>
                            <th>'.tr('Valore').'</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($rows as $key => $row) {
            echo '
                        <tr>
                            <td>'.($key + 1).'</td>
                            <td>'.$row[$column].'</td>
                        </tr>';
        }

        echo '

                    </tbody>
                </table>

            </div>
        </div>
    </div>';
    }

    echo '
    </div>
</form>';

    echo '
<script>
$(document).ready(function(){';

    if ($first_row) {
        echo '
    $("#first_row").prop("checked", false).trigger("change");';
    }

    echo '
    $("#save").html("<i class=\"fa fa-flag-checkered\"></i> '.tr('Avvia importazione').'");
});
</script>';
}
