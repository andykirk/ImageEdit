$(function(){
	// Step 1:
	var removeClick = false;
	$('#btn-next').prop('disabled', true);
	$('#remove').click(function(){
		removeClick = true;
	});

	$('#fileinput').change(function(){
		$('#btn-next').prop('disabled', removeClick);
		removeClick = false;
	});

	// Step 2:
	function updateButtonStates()
	{
		// Turn them all on to start:
		$('#toolbar .btn').prop('disabled', false);
		// If image isn't square, disallow save:
		if ($('#editor').width() != $('#editor').height()) {
			$('#btn-save').prop('disabled', true);
		}
		// Disable undo if at histroy start:
		if ($('#version').val() == 1) {
			$('#btn-undo').prop('disabled', true);
		}
		// Disable redo if at history end:
		if ($('#max_version').val() == $('#version').val()) {
			$('#btn-redo').prop('disabled', true);
		}
	}
	updateButtonStates();
	$('#btn-crop').prop('disabled', true);

	$('#editor').Jcrop({
		aspectRatio: 1,
		boxWidth: 450,
		boxHeight: 450,
		onSelect: function(c){
			$('#toolbar .btn').prop('disabled', true);
			$('#btn-crop').prop('disabled', false);
			$('#x').val(c.x);
			$('#y').val(c.y);
			$('#w').val(c.w);
			$('#h').val(c.h);
		},
		onRelease: function(){
			//$('#toolbar .btn:not(#btn-save)').prop('disabled', false);
			updateButtonStates();
			$('#btn-crop').prop('disabled', true);
		}
	});


	$('[data-action]').click(function(e){

		$('#action').val($(e.target).attr('data-action'));
	})
});

