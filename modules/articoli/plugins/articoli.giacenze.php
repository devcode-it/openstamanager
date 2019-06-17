<?php

$sedi = $dbo->fetchArray( '(SELECT "0" AS id, "Sede legale" AS nomesede) UNION (SELECT id, CONCAT(nomesede, " - ", citta ) AS nomesede FROM an_sedi WHERE idanagrafica='.prepare(setting('Azienda predefinita')).')' );
?>

<div class="row">
  <div class="col-md-6">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h3 class="panel-title"><?php echo tr('Giacenze'); ?></h3>
      </div>
      
      <div class="panel-body">
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
                      // Lettura movimenti della sede
                      $qta_azienda = $dbo->fetchOne("SELECT SUM(mg_movimenti.qta) AS qta, IF(mg_movimenti.idsede_azienda= 0,'Sede legale',(CONCAT_WS(' - ',an_sedi.nomesede,an_sedi.citta))) as sede FROM mg_movimenti LEFT JOIN an_sedi ON an_sedi.id = mg_movimenti.idsede_azienda WHERE mg_movimenti.idarticolo=".prepare($id_record).' AND idsede_azienda='.prepare($sede['id']).' GROUP BY idsede_azienda');

                      // Lettura eventuali movimenti ad una propria sede
                      $qta_controparte = $dbo->fetchOne("SELECT SUM(mg_movimenti.qta) AS qta, IF(mg_movimenti.idsede_controparte= 0,'Sede legale',(CONCAT_WS(' - ',an_sedi.nomesede,an_sedi.citta))) as sede FROM mg_movimenti LEFT JOIN an_sedi ON an_sedi.id = mg_movimenti.idsede_controparte WHERE mg_movimenti.idarticolo=".prepare($id_record)." AND idsede_controparte=".prepare($sede['id'])." GROUP BY idsede_controparte");

                      echo '
                        <tr>
                            <td>'.$sede['nomesede'].'</td>
                            <td class="text-right">'.Translator::numberToLocale($qta_azienda['qta'] - $qta_controparte['qta'] ).'</td>
                        </tr>';
                    } ?>
            </tbody>
          </table>
      </div>
    </div>
  </div>
</div>
