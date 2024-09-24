jQuery(document).ready(function($) {
	$('#contactbutton').click(function(event) {
		event.preventDefault();
		$('#contact-msg').html('<div class="lds-ripple"><div></div><div></div></div> ');
		var message = $('#message').val();
		var name = $('#name').val();
		var email = $('#email').val();
		
		if(name.trim() == ''){
            check=false;
			$('#contact-msg').html("");
			return false;
        }
 

        if(email.trim().match(/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{1,5}|[0-9]{1,3})(\]?)$/) == null) {
            check=false;
			$('#contact-msg').html("");
			return false;
        }

        if(message.trim() == ''){
            check=false;
			$('#contact-msg').html("");
			return false;
        }


	

			$.ajax({
				type: 'POST',
				url: ajax_object.ajax_url,
				data: $('#contactform').serialize(),
				dataType: 'json',
					success: function(response) {
						if (response.status == 'success') {
							$('#contactform')[0].reset();
						}
						$('#contact-msg').html(response.errmessage);
					}
			});
					
				
		
	});
});








