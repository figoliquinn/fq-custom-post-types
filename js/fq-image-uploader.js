(function($) {


	$(document).ready(function() {

		// Instantiates the variable that holds the media library frame.
	    var meta_image_frame;

	
	    // Runs when the image button is clicked.
	    $('.meta-image-button').click(function(e){
	        // Prevents the default action from occuring.
	        e.preventDefault();
	        
		    var button = $(this);
// 		    var hidden_field = 
	
	        // If the frame already exists, re-open it.
	        if ( meta_image_frame ) {
	            meta_image_frame.open();
	            return;
	        }
	
	        // Sets up the media library frame
	        meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
// 	            title: meta_image.title,
	            title: 'test',
// 	            button: { text:  meta_image.button },
	            button: { text:  'test-button' },
	            library: { type: 'image' }
	        });
	
	        // Runs when an image is selected.
	        meta_image_frame.on('select', function(){
	
	            // Grabs the attachment selection and creates a JSON representation of the model.
	            var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
	            
	            // Sends the attachment URL to our custom image input field.
	            $(button).prevAll('.meta-image').val(media_attachment.id);
	            
	            console.log(media_attachment);
	            
	            // Add an image preview
	            $(button).prevAll('.fq-preview-image-wrapper').find('img').attr('src', media_attachment.sizes.thumbnail.url);
	            $(button).prevAll('.fq-preview-image-wrapper').show();
	        });
	
	        // Opens the media library frame.
	        meta_image_frame.open();
	    });
	    
	    
	    // Removes the image
	    $('.fq-remove-preview-image').click(function(e) {
			e.preventDefault();
			 
			var imageWrapper = $(this).closest('.fq-preview-image-wrapper');
			$(imageWrapper).find('img').attr('src', '');
			$(imageWrapper).hide();
			$(imageWrapper).nextAll('.meta-image').val('');
	    });
	
	
	});
	
	
})(jQuery)