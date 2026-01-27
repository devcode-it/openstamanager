<?php

include_once __DIR__.'/../../core.php';

use Models\Plugin;

$plugin = Plugin::find($id_plugin);

?>
<div class="row">
	<div class="col-md-12">
		<!-- DANNI -->
		<div class="card card-primary">
			<div class="card-header">
				<h3 class="card-title"><?php echo tr('Danni'); ?></h3>
			</div>

			<div class="card-body">
				<div class="row">
					<div class="col-md-12">
						<?php
                        include $plugin->filepath('row-list-danni.php');
?>

						<div class="pull-left">
							<a class="btn btn-sm btn-primary" data-href="<?php echo $plugin->fileurl('modals/manage_danno.php'); ?>?id_module=<?php echo $id_module; ?>&id_plugin=<?php echo $id_plugin; ?>&id=<?php echo $id_record; ?>" data-card-widget="modal" data-title="<?php echo tr('Aggiungi danno'); ?>"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi danno'); ?></a>
						</div>
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
		</div>	
	</div>
</div>
