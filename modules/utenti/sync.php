<?php

include __DIR__.'/../../core.php';

?>
<div class="text-center">
	<h4>Per generare il link per visualizzare il calendario su applicazioni esterne, inserisci username e password qui sotto, poi copia il link nella tua applicazione:</h4>
</div>
<br>

<div class="row fields">
	<div class="col-md-offset-3 col-md-3">
		<div class="form-group">
			<label>Username:</label>
			<input type="text" class="form-control" id="username">
		</div>
	</div>

	<div class="col-md-3">
		<div class="form-group">
			<label>Password:</label>
			<input type="password" class="form-control" id="password">
		</div>
	</div>
</div>


<div class="row fields">
	<div class="col-md-offset-3 col-md-6">
		<div class="form-group">
			<label>LINK:</label>
			<input type="text" class="form-control text-center" id="link" value="">
		</div>
	</div>
</div>


<div class="row fields">
	<div class="col-md-offset-3 col-md-6">
		Per <b>Android</b>, scarica <a href="https://play.google.com/store/apps/details?id=org.kc.and.ical&hl=it" target="_blank"><b>iCalSync2</b></a>.<br><br>

		Per <b>Apple</b>, puoi configurare un nuovo calendario dall'app standard del calendario, specificando l'URL sopra.<br><br>

		Per <b>PC</b>, per altri client di posta, considerare le relative funzionalit&agrave; o eventuali plugin.
	</div>
</div>



<script>
	$('.fields input').on('keyup change', function(){
		$('#link').val( "<?php echo $rootdir ?>/modules/osmsync/sync_interventi.php?username="+$('#username').val()+"&password="+$('#password').val() );
	});

	$('#link').on('click', function(){
		$(this).select();
	});

	$('.fields input').trigger('change');
</script>
