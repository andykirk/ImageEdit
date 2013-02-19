$(function(){
	$('#next-btn').prop('disabled', true);
	$('#fileinput').change(function(){
		$('#next-btn').prop('disabled', function (_, val) { return ! val; });
	});

	$('#editor').Jcrop();

	/*$('#edit_form').submit(function(e){
		console.log($(e.target));
		return false;
	});*/
	$('[data-action]').click(function(e){

		$('#action').val($(e.target).attr('data-action'));
		/*console.log();
		e.preventDefault();
		e.stopPropagation();
		return false;*/
	})
});

