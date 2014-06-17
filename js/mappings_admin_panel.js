jQuery().ready(function(){
	var $ = jQuery;
	
	$('.add-field').click(function(e){
		e.preventDefault();
		$('#'+ $(this).data('field-template')).clone().appendTo($('#'+ $(this).data('target-table'))).show();
	});
	
	$('.remove-field').click(function(e){
		e.preventDefault();
		$(this).parents('tr').eq(0).remove();
	});
	
	$('a.editinline').click(function(e){
		e.preventDefault();
		var id = $(this).data('edit-id');
		$('#edit-mapping-wrapper-'+id).show();
		$(this).parents('tr').eq(0).hide();
	});
	
	$('tr.edit-mapping-wrapper a.cancel').click(function(e){
		e.preventDefault();
		var form_tr = $(this).parents('tr').eq(0);
		form_tr.hide();
		form_tr.prev('tr').show();
	});
	
	$('#add-new-mapping').click(function(e){
		e.preventDefault();
		$('#new-mapping-form').slideToggle();
	});
	
	$('#cancel-new-mapping').click(function(e){
		e.preventDefault();
		$('#new-mapping-form').slideUp();
	});
	
});