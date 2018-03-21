<?php

	
	
	
	/*deve sempre essere impostato almeno un sezionale
	if (isset($_SESSION[$id_module]['id_segment'])){
		$id_segment = $_SESSION[$id_module]['id_segment'];
	}
	else{
		
		if ($id_module=='14')
			(!empty($_SESSION[$id_module]['id_segment'])) ? $id_segment = $_SESSION[$id_module]['id_segment'] :$id_segment = get_var("Sezionale predefinito fatture di vendita");		
		
		if ($id_module=='15')
			$id_segment = get_var('Sezionale predefinito fatture di acquisto');		
		
		
		 $_SESSION[$id_module]['id_segment'] = $id_segment;
	}
	*/
	

?>


<div class="row">
	<div class="col-md-4 pull-right">
		{[ "type": "select", "label": "Sezionale", "name": "idsezionale_", "required": 0, "class": "", "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module = '<?php echo $id_module; ?>'", "value": "<?php echo $_SESSION[$id_module]['id_segment']; ?>", "extra": "" ]}
	</div>
</div>


<script>
$(document).ready(function () {

	$("#idsezionale_").on("change", function(){
		
		if ($(this).val()<1){
			session_set('<?php echo $id_module; ?>,id_segment', '', 1, 1);
		}else{
			session_set('<?php echo $id_module; ?>,id_segment', $(this).val(), 0, 1);
		}

  });

});
</script>