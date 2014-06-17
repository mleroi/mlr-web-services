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
		$('#edit-ws-wrapper-'+id).show();
		$(this).parents('tr').eq(0).hide();
	});
	
	$('tr.edit-ws-wrapper a.cancel').click(function(e){
		e.preventDefault();
		var form_tr = $(this).parents('tr').eq(0);
		form_tr.hide();
		form_tr.prev('tr').show();
	});
	
	$('#add-new-ws').click(function(e){
		e.preventDefault();
		$('#new-ws-form').slideToggle();
	});
	
	$('#cancel-new-ws').click(function(e){
		e.preventDefault();
		$('#new-ws-form').slideUp();
	});
	
});