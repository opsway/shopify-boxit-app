jQuery(function(){
	jQuery('.method input').change(function(){
		console.info(jQuery(this).val() == 'other')
		if(jQuery(this).val() == 'other')
		{
			jQuery('#checkout').attr('disabled',false);
		} else {
			jQuery('#checkout').attr('disabled',true);
		}
	});
});