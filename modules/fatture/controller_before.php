<?php

	
	
	
	/*deve sempre essere impostato almeno un sezionale
	if (isset($_SESSION[$dir]['idsezionale'])){
		$idsezionale = $_SESSION[$dir]['idsezionale'];
	}
	else{
		
		if ($dir=='entrata')
			(!empty($_SESSION[$dir]['idsezionale'])) ? $idsezionale = $_SESSION[$dir]['idsezionale'] :$idsezionale = get_var("Sezionale predefinito fatture di vendita");		
		
		if ($dir=='uscita')
			$idsezionale = get_var('Sezionale predefinito fatture di acquisto');		
		
		
		 $_SESSION[$dir]['idsezionale'] = $idsezionale;
	}
	*/
	

?>


<div class="row">
	<div class="col-md-4 pull-right">
		{[ "type": "select", "label": "Sezionale", "name": "idsezionale_", "required": 0, "class": "", "values": "query=SELECT id, nome AS descrizione FROM co_sezionali WHERE dir = '<?php echo $dir; ?>'", "value": "<?php echo $_SESSION[$dir]['idsezionale']; ?>", "extra": "" ]}
	</div>
</div>


<script>
$(document).ready(function () {

	$("#idsezionale_").on("change", function(){

		//alert ('<?php echo $dir; ?>');
		
		if ($(this).val()<1){
			session_set('<?php echo $dir; ?>,idsezionale', '', 1, 1);
		}else{
			session_set('<?php echo $dir; ?>,idsezionale', $(this).val(), 0, 1);
		}

  });

});
</script>