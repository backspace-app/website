	// ajax call for newsletter function
	$newsletter = $('.password-form');

	$newsletter.on( 'submit', function(e) {
		subscribe($newsletter);
		return false;
	});

	$newsletter.find('.btn').click( function() {
		subscribe($newsletter);
	});

	function subscribe(newsletter) {
		$btn = newsletter.find('.btn');

		$.ajax({

			url: 'assets/php/pw.php',
			type: 'POST',
			dataType: 'json',
			cache: false,
			data: {
				username: newsletter.find('input[name="username"]').val(),
				token: newsletter.find('input[name="token"]').val(),
				password: newsletter.find('input[name="password"]').val(),
				verify: newsletter.find('input[name="verify"]').val(),
			},
			beforeSend: function(){
				$btn.addClass('loading');
				$btn.attr('disabled', 'disabled');
			},
			success: function( data, textStatus, XMLHttpRequest ){
				
				var className = '';

				if( data.result == true ){
					className = 'alert-success';
				}else {
					className = 'alert-danger';
				}

				newsletter.find('.alert').html( data.message )
				.removeClass( 'alert-danger alert-success' )
				.addClass( 'alert active ' + className )
				.slideDown(300);

				$btn.removeClass('loading');
				$btn.removeAttr('disabled');
			},
			error: function( XMLHttpRequest, textStatus, errorThrown ){
				console.log("AJAX ERROR: \n" + XMLHttpRequest.responseText + "\n" + textStatus);
			}
			
		});
	}