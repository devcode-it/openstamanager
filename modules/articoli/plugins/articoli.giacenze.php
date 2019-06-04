<?php

$sedi = $dbo->fetchArray("SELECT SUM(mg_movimenti.qta)AS qta, IF(mg_movimenti.idsede_azienda= 0,'Sede legale',(CONCAT_WS(' - ',an_sedi.nomesede,an_sedi.citta))) as sede FROM mg_movimenti LEFT JOIN an_sedi ON an_sedi.id = mg_movimenti.idsede_azienda WHERE mg_movimenti.idarticolo=".prepare($id_record).' GROUP BY idsede_azienda');

?>

<div class="row">
  <div class="col-md-6">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h3 class="panel-title"><?php echo tr('Giacenze'); ?></h3>
      </div>
      
      <div class="panel-body">
        <?php
        if (empty($sedi)) {
            echo '<div class="alert alert-info">'.tr('Non ci sono ancora movimenti').'</div>';
        } else {
            ?>
          <table class="table table-striped table-condensed table-bordered">
            <thead>
              <tr>
                <th width="400"><?php echo tr('Sede'); ?></th>
                <th width="200"><?php echo tr('Q.tà'); ?></th>
              </tr>
            </thead>

            <tbody>
              <?php
                    foreach ($sedi as $sede) {
                        echo '
                        <tr>
                            <td>'.$sede['sede'].'</td>
                            <td class="text-right">'.Translator::numberToLocale($sede['qta']).'</td>
                        </tr>';
                    } ?>
            </tbody>
          </table>
        <?php
        }
        ?>
      </div>
    </div>
  </div>
</div>
