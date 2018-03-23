<?php
    /*deve sempre essere impostato almeno un sezionale*/
    if (empty($_SESSION['m'.$id_module]['id_segment'])) {
        $rs = $dbo->fetchArray('SELECT id  FROM zz_segments WHERE predefined = 1 AND id_module = '.prepare($id_module).'LIMIT 0,1');
        $_SESSION['m'.$id_module]['id_segment'] = $rs[0]['id'];
    }

    if (count($dbo->fetchArray("SELECT id FROM zz_segments WHERE id_module = \"$id_module\"")) > 1) {
        ?>

<div class="row">
	<div class="col-md-4 pull-right">
		{[ "type": "select", "label": "", "name": "id_segment_", "required": 0, "class": "", "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module = '<?php echo $id_module; ?>'", "value": "<?php echo $_SESSION['m'.$id_module]['id_segment']; ?>", "extra": "" ]}
	</div>
</div>


<script>
$(document).ready(function () {

	$("#id_segment_").on("change", function(){
		
		if ($(this).val()<1){
			session_set('<?php echo 'm'.$id_module; ?>,id_segment', '', 1, 1);
		}else{
			session_set('<?php echo 'm'.$id_module; ?>,id_segment', $(this).val(), 0, 1);
		}

  });

});
</script>

<?php
    }
?>